<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private Database $db;
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function attempt(string $login, string $password): bool
    {
        $user = $this->db->fetch(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        );
        if (!$user) return false;

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            Security::logEvent((int)$user['id'], 'login_locked', 'Login attempted on locked account', 'warning');
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->db->run(
                'UPDATE users SET failed_logins = failed_logins + 1,
                    locked_until = IF(failed_logins + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until)
                 WHERE id = ?',
                [$user['id']]
            );
            Security::logEvent((int)$user['id'], 'login_failed', 'Bad password', 'warning');
            return false;
        }

        if ($user['status'] !== 'active') {
            Security::logEvent((int)$user['id'], 'login_blocked', 'Account ' . $user['status'], 'warning');
            return false;
        }

        // Successful login.
        Session::regenerate();
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['login_time'] = time();
        $this->user = $user;
        $this->loaded = true;

        $this->db->update('users', [
            'failed_logins'  => 0,
            'locked_until'   => null,
            'last_login_ip'  => Security::clientIp(),
            'last_login_at'  => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => $user['id']]);

        Security::logEvent((int)$user['id'], 'login_success', 'Login OK', 'info');
        return true;
    }

    public function logout(): void
    {
        if ($id = $this->id()) {
            Security::logEvent($id, 'logout', 'User logged out');
        }
        Session::destroy();
        $this->user = null;
        $this->loaded = true;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        $u = $this->user();
        return $u !== null && $u['role'] === 'admin';
    }

    public function id(): ?int
    {
        $u = $this->user();
        return $u ? (int)$u['id'] : null;
    }

    public function user(): ?array
    {
        if ($this->loaded) return $this->user;
        $this->loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) return $this->user = null;
        $u = $this->db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$u || $u['status'] !== 'active') {
            Session::destroy();
            return $this->user = null;
        }
        return $this->user = $u;
    }

    public function refresh(): void
    {
        $this->loaded = false;
        $this->user   = null;
    }
}
