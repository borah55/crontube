<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Coin;

final class PtcController extends Controller
{
    public function index(): void
    {
        $rows = $this->db->fetchAll(
            'SELECT a.*, c.code AS coin_code FROM ptc_ads a
             LEFT JOIN coins c ON c.id = a.coin_id ORDER BY a.id DESC'
        );
        $coins = Coin::active();
        $this->render('admin/ptc', compact('rows','coins'), 'admin');
    }

    public function save(): void
    {
        $id = (int)$this->input('id', 0);
        $data = [
            'title'        => trim((string)$this->input('title')),
            'description'  => trim((string)$this->input('description')),
            'target_url'   => trim((string)$this->input('target_url')),
            'image_url'    => trim((string)$this->input('image_url')),
            'coin_id'      => (int)$this->input('coin_id', 0),
            'reward'       => number_format((float)$this->input('reward', 0), 8, '.', ''),
            'duration'     => max(5, (int)$this->input('duration', 15)),
            'daily_limit'  => max(0, (int)$this->input('daily_limit', 0)),
            'max_views'    => max(0, (int)$this->input('max_views', 0)),
            'status'       => in_array($this->input('status'), ['active','paused','ended'], true) ? (string)$this->input('status') : 'active',
        ];
        if ($data['title'] === '' || !filter_var($data['target_url'], FILTER_VALIDATE_URL) || $data['coin_id'] === 0) {
            $this->redirect('/admin/ptc', 'Title, target URL and coin are required.', 'error');
        }
        if ($id > 0) {
            $this->db->update('ptc_ads', $data, 'id = :_id', ['_id' => $id]);
            $this->redirect('/admin/ptc', 'Ad updated.');
        }
        $this->db->insert('ptc_ads', $data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->redirect('/admin/ptc', 'Ad created.');
    }

    public function delete(string $id): void
    {
        $this->db->run('DELETE FROM ptc_ads WHERE id = ?', [(int)$id]);
        $this->redirect('/admin/ptc', 'Ad deleted.');
    }
}
