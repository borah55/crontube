<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Security;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->render('auth/login', [], 'auth');
    }

    public function login(): void
    {
        $ip = Security::clientIp();
        if (!RateLimiter::hit('login:' . $ip, 10, 600)) {
            $this->redirect('/login', 'Too many attempts. Try again later.', 'error');
        }

        $login    = trim((string)$this->input('login', ''));
        $password = (string)$this->input('password', '');

        if ($login === '' || $password === '') {
            $this->redirect('/login', 'Username and password are required.', 'error');
        }

        if (!$this->auth->attempt($login, $password)) {
            $this->redirect('/login', 'Invalid credentials.', 'error');
        }
        $this->redirect('/dashboard', 'Welcome back!');
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/', 'You have been logged out.');
    }

    public function showRegister(): void
    {
        $referral = $_GET['ref'] ?? '';
        $this->render('auth/register', ['referral' => $referral], 'auth');
    }

    public function register(): void
    {
        $ip = Security::clientIp();
        if (!RateLimiter::hit('register:' . $ip, 5, 600)) {
            $this->redirect('/register', 'Too many registration attempts. Wait a bit.', 'error');
        }
        if (Security::isIpBlocked($ip)) {
            Security::logEvent(null, 'register_blocked', 'IP on blacklist or VPN', 'warning');
            $this->redirect('/register', 'Registration not allowed from this network.', 'error');
        }

        $username = strtolower(trim((string)$this->input('username', '')));
        $email    = strtolower(trim((string)$this->input('email', '')));
        $password = (string)$this->input('password', '');
        $confirm  = (string)$this->input('password_confirm', '');
        $referralCode = trim((string)$this->input('referral', ''));
        $captcha = (string)$this->input('g-recaptcha-response', '');

        $v = (new Validator(compact('username','email','password','confirm')))
            ->required('username')->regex('username', '/^[a-z0-9_]{3,32}$/', 'username must be 3-32 chars (a-z, 0-9, _)')
            ->required('email')->email('email')
            ->required('password')->length('password', 8, 72)
            ->matches('confirm', 'password', 'passwords do not match');

        if ($v->fails()) {
            $this->redirect('/register', $v->firstError() ?? 'Invalid input.', 'error');
        }
        if (!Security::verifyRecaptcha($captcha)) {
            $this->redirect('/register', 'Captcha failed.', 'error');
        }

        if ($this->db->column('SELECT 1 FROM users WHERE username = ? OR email = ?', [$username, $email])) {
            $this->redirect('/register', 'Username or email already taken.', 'error');
        }

        $referredBy = null;
        if ($referralCode !== '') {
            $referredBy = $this->db->column('SELECT id FROM users WHERE referral_code = ?', [$referralCode]);
            $referredBy = $referredBy ? (int)$referredBy : null;
        }

        $userId = $this->db->insert('users', [
            'username'       => $username,
            'email'          => $email,
            'password_hash'  => password_hash($password, PASSWORD_BCRYPT),
            'role'           => 'user',
            'status'         => 'active',
            'referral_code'  => User::uniqueReferralCode(),
            'referred_by'    => $referredBy,
            'register_ip'    => $ip,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        Security::logEvent($userId, 'register', 'New account', 'info');

        // Auto-login the new user.
        $this->auth->attempt($username, $password);
        $this->redirect('/dashboard', 'Welcome! Your account has been created.');
    }

    public function showForgot(): void
    {
        $this->render('auth/forgot', [], 'auth');
    }

    public function forgot(): void
    {
        $ip = Security::clientIp();
        RateLimiter::hit('forgot:' . $ip, 5, 900);

        $email = strtolower(trim((string)$this->input('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/forgot', 'Invalid email.', 'error');
        }
        $user = $this->db->fetch('SELECT id, email, username FROM users WHERE email = ?', [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $this->db->insert('password_resets', [
                'token_hash' => hash('sha256', $token),
                'user_id'    => $user['id'],
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $resetUrl = \App\Core\Helpers::url('/reset/' . $token);
            $this->sendResetEmail($user['email'], $user['username'], $resetUrl);
        }
        // Always show generic message (do not leak which emails are registered).
        $this->redirect('/forgot', 'If that email is registered, a reset link has been sent.');
    }

    public function showReset(string $token): void
    {
        $row = $this->validResetRow($token);
        if (!$row) {
            $this->redirect('/forgot', 'Invalid or expired reset link.', 'error');
        }
        $this->render('auth/reset', ['token' => $token], 'auth');
    }

    public function reset(string $token): void
    {
        $row = $this->validResetRow($token);
        if (!$row) {
            $this->redirect('/forgot', 'Invalid or expired reset link.', 'error');
        }
        $password = (string)$this->input('password', '');
        $confirm  = (string)$this->input('password_confirm', '');
        if (strlen($password) < 8 || $password !== $confirm) {
            $this->redirect('/reset/' . $token, 'Password too short or does not match.', 'error');
        }
        $this->db->update('users', [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'failed_logins' => 0,
            'locked_until'  => null,
        ], 'id = :_id', ['_id' => $row['user_id']]);
        $this->db->run('UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?', [hash('sha256', $token)]);
        Security::logEvent((int)$row['user_id'], 'password_reset', 'Password reset via email link');
        $this->redirect('/login', 'Password updated. You can sign in now.');
    }

    private function validResetRow(string $token): ?array
    {
        $hash = hash('sha256', $token);
        return $this->db->fetch(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
            [$hash]
        );
    }

    private function sendResetEmail(string $to, string $username, string $resetUrl): void
    {
        $host = (string)\App\Core\Setting::get('smtp_host', '');
        if ($host === '') {
            // SMTP not configured — log the link to the storage log so admins can recover it.
            @file_put_contents(
                Application::$rootPath . '/storage/logs/password-resets.log',
                date('c') . " {$to} {$resetUrl}\n",
                FILE_APPEND
            );
            return;
        }
        $from = (string)\App\Core\Setting::get('smtp_from', 'noreply@example.com');
        $subject = 'Password reset for ' . \App\Core\Setting::get('site_name', 'Crypto Faucet');
        $body = "Hi {$username},\n\nReset your password using this link (expires in 1 hour):\n{$resetUrl}\n\nIf you did not request this, ignore this email.\n";
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=utf-8\r\n";
        @mail($to, $subject, $body, $headers);
    }
}
