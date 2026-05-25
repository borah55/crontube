<?php
/**
 * Single-file installer for the Crypto Faucet platform.
 *
 * Steps:
 *   1. Requirements check
 *   2. Database credentials + admin account
 *   3. Apply schema, create config.php, create admin
 *   4. Success screen with a reminder to delete install.php
 */
declare(strict_types=1);

session_start();

$rootPath   = __DIR__;
$configFile = $rootPath . '/config/config.php';
$schemaFile = $rootPath . '/database/schema.sql';

$step   = (int)($_GET['step'] ?? 1);
$errors = [];
$ok     = [];

// If config exists already, lock the installer unless ?force=1.
if (file_exists($configFile) && !isset($_GET['force'])) {
    $errors[] = 'config/config.php already exists. Delete it first if you want to re-install, or pass ?force=1.';
    $step = 99;
}

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function check_requirements(): array {
    $checks = [
        'PHP >= 8.0'        => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO MySQL'         => extension_loaded('pdo_mysql'),
        'OpenSSL'           => extension_loaded('openssl'),
        'mbstring'          => extension_loaded('mbstring'),
        'JSON'              => extension_loaded('json'),
        'cURL (recommended)' => extension_loaded('curl'),
        'config/ writable'  => is_writable(__DIR__ . '/config'),
        'storage/ writable' => is_writable(__DIR__ . '/storage'),
    ];
    return $checks;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $dbHost = trim((string)($_POST['db_host'] ?? 'localhost'));
    $dbPort = (int)($_POST['db_port'] ?? 3306);
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');

    $adminUser = trim((string)($_POST['admin_user'] ?? ''));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPass  = (string)($_POST['admin_pass'] ?? '');

    if ($dbName === '' || $dbUser === '') $errors[] = 'Database name and user are required.';
    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $adminUser)) $errors[] = 'Admin username must be 3-32 chars (letters, numbers, _).';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Admin email is invalid.';
    if (strlen($adminPass) < 10) $errors[] = 'Admin password must be at least 10 characters.';

    if (!$errors) {
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);

            // Apply the schema. Split on semicolons that end statements.
            $sql = file_get_contents($schemaFile) ?: '';
            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt === '' || str_starts_with($stmt, '--')) continue;
                $pdo->exec($stmt);
            }

            // Create the admin user.
            $now  = date('Y-m-d H:i:s');
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $refCode = strtoupper(bin2hex(random_bytes(4)));

            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $check->execute([$adminUser, $adminEmail]);
            if ($check->fetch()) {
                throw new RuntimeException('A user with that username or email already exists.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO users (username,email,password_hash,role,status,referral_code,register_ip,created_at,updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $adminUser, $adminEmail, $hash, 'admin', 'active', $refCode,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $now, $now,
            ]);

            // Write config/config.php.
            $appKey = bin2hex(random_bytes(32));
            $template = (string)file_get_contents($rootPath . '/config/config.example.php');
            $cfg = strtr($template, [
                '__REPLACE_ME_WITH_RANDOM_64_CHARS__' => $appKey,
                "'localhost'" => var_export($dbHost, true),
                '3306'        => (string)$dbPort,
                "'cf_faucet'" => var_export($dbName, true),
                "'cf_user'"   => var_export($dbUser, true),
                "'change_me'" => var_export($dbPass, true),
            ]);
            if (file_put_contents($configFile, $cfg) === false) {
                throw new RuntimeException('Failed to write config/config.php (check directory permissions).');
            }

            $_SESSION['install_done'] = true;
            header('Location: install.php?step=3');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Install failed: ' . $e->getMessage();
        }
    }
}

if ($step === 3 && empty($_SESSION['install_done'])) {
    $step = 1;
}

?>
<!doctype html>
<html lang="en" class="bg-slate-100">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install - Crypto Faucet</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-indigo-700 to-purple-700">
<div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">Crypto Faucet Installer</h1>
    <div class="flex gap-2 mb-6">
        <?php foreach ([1=>'Requirements', 2=>'Database & Admin', 3=>'Done'] as $s => $label): ?>
            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $step === $s ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600' ?>">
                <?= $s ?>. <?= $label ?>
            </span>
        <?php endforeach; ?>
    </div>

    <?php foreach ($errors as $e): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded mb-4 text-sm"><?= h($e) ?></div>
    <?php endforeach; ?>

    <?php if ($step === 1): ?>
        <h2 class="text-xl font-semibold mb-4">Server requirements</h2>
        <ul class="space-y-2 mb-6">
            <?php foreach (check_requirements() as $name => $pass): ?>
                <li class="flex justify-between p-2 rounded <?= $pass ? 'bg-emerald-50' : 'bg-red-50' ?>">
                    <span class="text-slate-700"><?= h($name) ?></span>
                    <span class="font-bold <?= $pass ? 'text-emerald-700' : 'text-red-700' ?>"><?= $pass ? 'OK' : 'MISSING' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="install.php?step=2" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-indigo-700">Continue</a>

    <?php elseif ($step === 2): ?>
        <h2 class="text-xl font-semibold mb-4">Database credentials &amp; admin account</h2>
        <form method="post" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm text-slate-600">DB host</span>
                    <input name="db_host" value="localhost" class="mt-1 w-full border rounded p-2" required>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-600">DB port</span>
                    <input name="db_port" value="3306" type="number" class="mt-1 w-full border rounded p-2" required>
                </label>
                <label class="block col-span-2">
                    <span class="text-sm text-slate-600">DB name</span>
                    <input name="db_name" class="mt-1 w-full border rounded p-2" required>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-600">DB user</span>
                    <input name="db_user" class="mt-1 w-full border rounded p-2" required>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-600">DB password</span>
                    <input name="db_pass" type="password" class="mt-1 w-full border rounded p-2">
                </label>
            </div>

            <hr class="my-4">

            <div class="grid grid-cols-2 gap-4">
                <label class="block col-span-2">
                    <span class="text-sm text-slate-600">Admin username</span>
                    <input name="admin_user" required class="mt-1 w-full border rounded p-2">
                </label>
                <label class="block col-span-2">
                    <span class="text-sm text-slate-600">Admin email</span>
                    <input name="admin_email" type="email" required class="mt-1 w-full border rounded p-2">
                </label>
                <label class="block col-span-2">
                    <span class="text-sm text-slate-600">Admin password (min 10 characters)</span>
                    <input name="admin_pass" type="password" required class="mt-1 w-full border rounded p-2">
                </label>
            </div>

            <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg">
                Run installation
            </button>
        </form>

    <?php elseif ($step === 3): ?>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-4">Installation complete</h2>
        <ol class="list-decimal pl-5 space-y-2 text-slate-700 mb-6">
            <li>Sign in at <a href="/login" class="text-indigo-600 underline">/login</a> with the admin credentials.</li>
            <li><strong class="text-red-700">Delete <code>install.php</code> from the server.</strong></li>
            <li>Configure FaucetPay API key, reCAPTCHA, SMTP, and coins in <code>/admin/settings</code>.</li>
            <li>Set up the cron job (see INSTALL.md) to run <code>cron.php</code> every 5 minutes.</li>
        </ol>
        <a href="/login" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-indigo-700">Go to login</a>

    <?php else: ?>
        <p class="text-slate-700">Installer locked. Resolve the error above to continue.</p>
    <?php endif; ?>
</div>
</body>
</html>
