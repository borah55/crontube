<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Core\Setting;
use App\Models\Coin;
use App\Models\User;

/**
 * Provably-fair Hi-Lo:
 *   server_seed       (32 random bytes)        - revealed AFTER bet
 *   server_seed_hash  = SHA-256(server_seed)   - shown to user BEFORE bet
 *   client_seed       supplied by user (defaults to random)
 *   nonce             increments each round per server_seed
 *
 * Roll value: HMAC-SHA256(server_seed, client_seed:nonce) -> first 4 hex
 * digits as int, modulo 100, gives a value in [0,99].
 *
 * Outcome:
 *   target_value=50 (configurable). If predict 'high', win when roll > target.
 *   If predict 'low', win when roll < target. Equal -> push.
 *
 * Multiplier = (1 - house_edge%) * 100 / chance%
 *   chance% is naturally 49 (high or low) before the edge.
 */
final class HiloController extends Controller
{
    private const TARGET = 50;

    public function index(): void
    {
        $u = $this->auth->user();
        $coins = Coin::active();

        // Maintain a session-based provably-fair seed pair.
        $pair = $this->ensureSeedPair();

        $history = $this->db->fetchAll(
            'SELECT h.*, c.code AS coin_code FROM hilo_games h
             LEFT JOIN coins c ON c.id = h.coin_id
             WHERE h.user_id = ? ORDER BY h.id DESC LIMIT 15',
            [$u['id']]
        );
        $edge = (float)Setting::get('hilo_house_edge_percent', 3);
        $multiplier = max(1.01, round((1 - $edge / 100) * 100 / 49, 4));

        $this->render('hilo/index', [
            'u' => $u, 'coins' => $coins, 'history' => $history,
            'serverSeedHash' => $pair['hash'],
            'clientSeed'     => $pair['client_seed'],
            'nonce'          => $pair['nonce'],
            'edge'           => $edge,
            'multiplier'     => $multiplier,
            'target'         => self::TARGET,
        ]);
    }

    public function bet(): void
    {
        $u = $this->auth->user();
        if (!RateLimiter::hit('hilo:' . $u['id'], 60, 60)) {
            $this->json(['ok' => false, 'error' => 'rate_limited'], 429);
            return;
        }

        $coinId    = (int)$this->input('coin_id', 0);
        $betAmount = (float)$this->input('amount', 0);
        $prediction = (string)$this->input('prediction', '');
        $clientSeedIn = trim((string)$this->input('client_seed', ''));

        if (!in_array($prediction, ['high', 'low'], true)) {
            $this->json(['ok' => false, 'error' => 'bad_prediction'], 400);
            return;
        }
        $coin = Coin::find($coinId);
        if (!$coin || (int)$coin['is_active'] !== 1) {
            $this->json(['ok' => false, 'error' => 'invalid_coin'], 400);
            return;
        }
        $minBet = max((float)$coin['min_reward'] * 2, 0.00000001);
        if ($betAmount < $minBet) {
            $this->json(['ok' => false, 'error' => 'min_bet', 'min_bet' => $minBet], 400);
            return;
        }

        $pair = $this->ensureSeedPair();
        if ($clientSeedIn !== '' && strlen($clientSeedIn) <= 64) {
            $pair['client_seed'] = preg_replace('/[^A-Za-z0-9_\-]/', '', $clientSeedIn);
            Session::set('hilo_pair', $pair);
        }

        // Debit bet.
        $afterDebit = User::debit((int)$u['id'], 'hilo_bet', $betAmount, (int)$coin['id'], ['nonce' => $pair['nonce']]);
        if ($afterDebit === false) {
            $this->json(['ok' => false, 'error' => 'insufficient_balance'], 400);
            return;
        }

        // Compute provably-fair roll.
        $msg = $pair['client_seed'] . ':' . $pair['nonce'];
        $hex = hash_hmac('sha256', $msg, $pair['server_seed']);
        $roll = hexdec(substr($hex, 0, 4)) % 100; // 0..99

        $edge = (float)Setting::get('hilo_house_edge_percent', 3);
        $multiplier = max(1.01, round((1 - $edge / 100) * 100 / 49, 4));

        $payout = 0.0;
        $result = 'loss';
        if ($roll === self::TARGET) {
            $result = 'push';
            $payout = $betAmount; // refund.
        } elseif ($prediction === 'high' && $roll > self::TARGET) {
            $result = 'win';
            $payout = round($betAmount * $multiplier, 8);
        } elseif ($prediction === 'low' && $roll < self::TARGET) {
            $result = 'win';
            $payout = round($betAmount * $multiplier, 8);
        }

        if ($payout > 0) {
            User::credit((int)$u['id'], 'hilo_win', $payout, (int)$coin['id'], [
                'roll' => $roll, 'prediction' => $prediction, 'multiplier' => $multiplier, 'result' => $result,
            ]);
        }

        $this->db->insert('hilo_games', [
            'user_id'         => $u['id'],
            'coin_id'         => $coin['id'],
            'bet_amount'      => number_format($betAmount, 8, '.', ''),
            'prediction'      => $prediction,
            'roll_value'      => $roll,
            'target_value'    => self::TARGET,
            'result'          => $result,
            'payout'          => number_format($payout, 8, '.', ''),
            'multiplier'      => $multiplier,
            'server_seed'     => $pair['server_seed'],
            'server_seed_hash'=> $pair['hash'],
            'client_seed'     => $pair['client_seed'],
            'nonce'           => $pair['nonce'],
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // Increment nonce; rotate seed every 100 rounds.
        $pair['nonce']++;
        if ($pair['nonce'] % 100 === 0) {
            $pair = $this->newSeedPair();
        }
        Session::set('hilo_pair', $pair);

        $balance = (float)$this->db->column('SELECT balance FROM users WHERE id = ?', [$u['id']]);

        $this->json([
            'ok'         => true,
            'result'     => $result,
            'roll'       => $roll,
            'target'     => self::TARGET,
            'payout'     => $payout,
            'multiplier' => $multiplier,
            'balance'    => $balance,
            'fairness'   => [
                'server_seed_hash' => $pair['hash'],   // upcoming round
                'next_nonce'       => $pair['nonce'],
                'last_revealed'    => [
                    'server_seed' => $pair === $this->ensureSeedPair() ? null : null, // revealed only on rotation
                ],
            ],
        ]);
    }

    public function history(): void
    {
        $u = $this->auth->user();
        $rows = $this->db->fetchAll(
            'SELECT h.*, c.code AS coin_code FROM hilo_games h
             LEFT JOIN coins c ON c.id = h.coin_id
             WHERE h.user_id = ? ORDER BY h.id DESC LIMIT 100',
            [$u['id']]
        );
        $this->render('hilo/history', ['rows' => $rows]);
    }

    private function ensureSeedPair(): array
    {
        $pair = Session::get('hilo_pair');
        if (!is_array($pair) || empty($pair['server_seed'])) {
            $pair = $this->newSeedPair();
            Session::set('hilo_pair', $pair);
        }
        return $pair;
    }

    private function newSeedPair(): array
    {
        $serverSeed = bin2hex(random_bytes(32));
        return [
            'server_seed' => $serverSeed,
            'hash'        => hash('sha256', $serverSeed),
            'client_seed' => bin2hex(random_bytes(8)),
            'nonce'       => 0,
        ];
    }
}
