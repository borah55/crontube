<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Coin;

final class ShortlinksController extends Controller
{
    public function index(): void
    {
        $rows = $this->db->fetchAll(
            'SELECT s.*, c.code AS coin_code FROM shortlinks s
             LEFT JOIN coins c ON c.id = s.coin_id ORDER BY s.id DESC'
        );
        $coins = Coin::active();
        $this->render('admin/shortlinks', compact('rows','coins'), 'admin');
    }

    public function save(): void
    {
        $id = (int)$this->input('id', 0);
        $data = [
            'name'        => trim((string)$this->input('name')),
            'target_url'  => trim((string)$this->input('target_url')),
            'coin_id'     => (int)$this->input('coin_id', 0),
            'reward'      => number_format((float)$this->input('reward', 0), 8, '.', ''),
            'daily_limit' => max(0, (int)$this->input('daily_limit', 5)),
            'is_active'   => (int)((bool)$this->input('is_active', 0)),
        ];
        if ($data['name'] === '' || $data['target_url'] === '' || $data['coin_id'] === 0) {
            $this->redirect('/admin/shortlinks', 'Name, target URL, coin required.', 'error');
        }
        if ($id > 0) {
            $this->db->update('shortlinks', $data, 'id = :_id', ['_id' => $id]);
            $this->redirect('/admin/shortlinks', 'Shortlink updated.');
        }
        $this->db->insert('shortlinks', $data + ['created_at' => date('Y-m-d H:i:s')]);
        $this->redirect('/admin/shortlinks', 'Shortlink added.');
    }

    public function delete(string $id): void
    {
        $this->db->run('DELETE FROM shortlinks WHERE id = ?', [(int)$id]);
        $this->redirect('/admin/shortlinks', 'Shortlink deleted.');
    }
}
