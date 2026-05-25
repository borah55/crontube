<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;

final class CoinsController extends Controller
{
    public function index(): void
    {
        $rows = $this->db->fetchAll('SELECT * FROM coins ORDER BY display_order, code');
        $this->render('admin/coins', ['rows' => $rows], 'admin');
    }

    public function save(): void
    {
        $id = (int)$this->input('id', 0);
        $data = [
            'code'                 => strtoupper(trim((string)$this->input('code'))),
            'name'                 => trim((string)$this->input('name')),
            'faucetpay_token'      => strtoupper(trim((string)$this->input('faucetpay_token'))),
            'min_reward'           => number_format((float)$this->input('min_reward', 0), 8, '.', ''),
            'max_reward'           => number_format((float)$this->input('max_reward', 0), 8, '.', ''),
            'min_withdraw'         => number_format((float)$this->input('min_withdraw', 0), 8, '.', ''),
            'withdraw_fee'         => number_format((float)$this->input('withdraw_fee', 0), 8, '.', ''),
            'withdraw_fee_percent' => number_format((float)$this->input('withdraw_fee_percent', 0), 2, '.', ''),
            'is_active'            => (int)((bool)$this->input('is_active', 0)),
            'display_order'        => (int)$this->input('display_order', 0),
        ];
        if ($data['code'] === '' || $data['name'] === '') {
            $this->redirect('/admin/coins', 'Code and name are required.', 'error');
        }
        if ($id > 0) {
            $this->db->update('coins', $data, 'id = :_id', ['_id' => $id]);
            $this->redirect('/admin/coins', 'Coin updated.');
        }
        $this->db->insert('coins', $data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->redirect('/admin/coins', 'Coin added.');
    }

    public function delete(string $id): void
    {
        $this->db->run('DELETE FROM coins WHERE id = ?', [(int)$id]);
        $this->redirect('/admin/coins', 'Coin removed.');
    }
}
