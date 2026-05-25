<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;

final class AnnouncementsController extends Controller
{
    public function index(): void
    {
        $rows = $this->db->fetchAll('SELECT * FROM announcements ORDER BY id DESC');
        $this->render('admin/announcements', ['rows' => $rows], 'admin');
    }

    public function save(): void
    {
        $id = (int)$this->input('id', 0);
        $data = [
            'title'     => trim((string)$this->input('title')),
            'body'      => trim((string)$this->input('body')),
            'level'     => in_array($this->input('level'), ['info','success','warning','danger'], true) ? (string)$this->input('level') : 'info',
            'is_active' => (int)((bool)$this->input('is_active', 0)),
        ];
        if ($data['title'] === '' || $data['body'] === '') {
            $this->redirect('/admin/announcements', 'Title and body required.', 'error');
        }
        if ($id > 0) {
            $this->db->update('announcements', $data, 'id = :_id', ['_id' => $id]);
            $this->redirect('/admin/announcements', 'Announcement saved.');
        }
        $this->db->insert('announcements', $data + ['created_at' => date('Y-m-d H:i:s')]);
        $this->redirect('/admin/announcements', 'Announcement created.');
    }

    public function delete(string $id): void
    {
        $this->db->run('DELETE FROM announcements WHERE id = ?', [(int)$id]);
        $this->redirect('/admin/announcements', 'Announcement deleted.');
    }
}
