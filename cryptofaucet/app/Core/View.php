<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public function render(string $template, array $data = [], string $layout = 'app'): string
    {
        $content = $this->capture($template, $data);

        if ($layout === '') {
            return $content;
        }

        $layoutData = array_merge($data, [
            'content' => $content,
            'flash'   => Session::pullFlash(),
            'authUser' => Application::$auth?->user(),
            'siteName' => Setting::get('site_name', 'Crypto Faucet'),
        ]);
        return $this->capture('layouts/' . $layout, $layoutData);
    }

    public function error(int $status, string $message): string
    {
        return $this->render('errors/error', ['status' => $status, 'message' => $message], 'auth');
    }

    private function capture(string $template, array $data): string
    {
        $file = Application::$rootPath . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            return '<!-- view missing: ' . htmlspecialchars($template) . ' -->';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }
}
