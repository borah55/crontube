
<?php
/**
 * @var \App\Core\Router $router
 *
 * Public, auth (regular user), and admin routes registered here.
 * Middleware names: 'auth', 'guest', 'admin', 'csrf'.
 */

// ---- Public ---------------------------------------------------------
$router->get('/',                'HomeController@index');
$router->get('/login',           'AuthController@showLogin',    ['guest']);
$router->post('/login',          'AuthController@login',        ['guest', 'csrf']);
$router->get('/register',        'AuthController@showRegister', ['guest']);
$router->post('/register',       'AuthController@register',     ['guest', 'csrf']);
$router->get('/forgot',          'AuthController@showForgot',   ['guest']);
$router->post('/forgot',         'AuthController@forgot',       ['guest', 'csrf']);
$router->get('/reset/{token}',   'AuthController@showReset',    ['guest']);
$router->post('/reset/{token}',  'AuthController@reset',        ['guest', 'csrf']);
$router->any('/logout',          'AuthController@logout');

// ---- Authenticated user --------------------------------------------
$router->get('/dashboard',          'DashboardController@index',       ['auth']);
$router->get('/profile',            'DashboardController@profile',     ['auth']);
$router->post('/profile/wallet',    'DashboardController@saveWallet',  ['auth', 'csrf']);
$router->post('/profile/password',  'DashboardController@changePassword', ['auth', 'csrf']);
$router->get('/transactions',       'DashboardController@transactions', ['auth']);
$router->get('/referrals',          'DashboardController@referrals',   ['auth']);
$router->get('/leaderboard',        'DashboardController@leaderboard', ['auth']);

$router->get('/faucet',             'FaucetController@index',  ['auth']);
$router->post('/faucet/claim',      'FaucetController@claim',  ['auth', 'csrf']);

$router->get('/withdraw',           'WithdrawController@index',  ['auth']);
$router->post('/withdraw',          'WithdrawController@submit', ['auth', 'csrf']);

$router->get('/hilo',               'HiloController@index', ['auth']);
$router->post('/hilo/bet',          'HiloController@bet',   ['auth', 'csrf']);
$router->get('/hilo/history',       'HiloController@history', ['auth']);

$router->get('/ptc',                'PtcController@index', ['auth']);
$router->get('/ptc/view/{id}',      'PtcController@view',  ['auth']);
$router->post('/ptc/claim/{id}',    'PtcController@claim', ['auth', 'csrf']);

$router->get('/shortlinks',                    'ShortlinkController@index',    ['auth']);
$router->get('/shortlinks/start/{id}',         'ShortlinkController@start',    ['auth']);
$router->get('/shortlinks/return/{token}',     'ShortlinkController@complete', ['auth']);

$router->post('/bonus/daily',       'DashboardController@dailyBonus', ['auth', 'csrf']);

// ---- Admin ----------------------------------------------------------
$router->get('/admin',                      'Admin\\AdminController@dashboard',    ['auth', 'admin']);
$router->get('/admin/users',                'Admin\\UsersController@index',        ['auth', 'admin']);
$router->post('/admin/users/{id}/ban',      'Admin\\UsersController@ban',          ['auth', 'admin', 'csrf']);
$router->post('/admin/users/{id}/unban',    'Admin\\UsersController@unban',        ['auth', 'admin', 'csrf']);
$router->post('/admin/users/{id}/credit',   'Admin\\UsersController@credit',       ['auth', 'admin', 'csrf']);

$router->get('/admin/settings',             'Admin\\SettingsController@index',     ['auth', 'admin']);
$router->post('/admin/settings',            'Admin\\SettingsController@save',      ['auth', 'admin', 'csrf']);

$router->get('/admin/coins',                'Admin\\CoinsController@index',        ['auth', 'admin']);
$router->post('/admin/coins/save',          'Admin\\CoinsController@save',         ['auth', 'admin', 'csrf']);
$router->post('/admin/coins/{id}/delete',   'Admin\\CoinsController@delete',       ['auth', 'admin', 'csrf']);

$router->get('/admin/withdrawals',                  'Admin\\WithdrawalsController@index',  ['auth', 'admin']);
$router->post('/admin/withdrawals/{id}/approve',    'Admin\\WithdrawalsController@approve', ['auth', 'admin', 'csrf']);
$router->post('/admin/withdrawals/{id}/reject',     'Admin\\WithdrawalsController@reject',  ['auth', 'admin', 'csrf']);

$router->get('/admin/ptc',                  'Admin\\PtcController@index',          ['auth', 'admin']);
$router->post('/admin/ptc/save',            'Admin\\PtcController@save',           ['auth', 'admin', 'csrf']);
$router->post('/admin/ptc/{id}/delete',     'Admin\\PtcController@delete',         ['auth', 'admin', 'csrf']);

$router->get('/admin/shortlinks',           'Admin\\ShortlinksController@index',   ['auth', 'admin']);
$router->post('/admin/shortlinks/save',     'Admin\\ShortlinksController@save',    ['auth', 'admin', 'csrf']);
$router->post('/admin/shortlinks/{id}/delete', 'Admin\\ShortlinksController@delete', ['auth', 'admin', 'csrf']);

$router->get('/admin/security',             'Admin\\SecurityController@index',     ['auth', 'admin']);
$router->post('/admin/security/blacklist',  'Admin\\SecurityController@blacklist', ['auth', 'admin', 'csrf']);
$router->post('/admin/security/whitelist',  'Admin\\SecurityController@whitelist', ['auth', 'admin', 'csrf']);

$router->get('/admin/announcements',                'Admin\\AnnouncementsController@index', ['auth', 'admin']);
$router->post('/admin/announcements/save',          'Admin\\AnnouncementsController@save',  ['auth', 'admin', 'csrf']);
$router->post('/admin/announcements/{id}/delete',   'Admin\\AnnouncementsController@delete', ['auth', 'admin', 'csrf']);

$router->post('/admin/maintenance/toggle',  'Admin\\AdminController@toggleMaintenance', ['auth', 'admin', 'csrf']);
$router->get('/admin/backup',               'Admin\\AdminController@backup',            ['auth', 'admin']);
