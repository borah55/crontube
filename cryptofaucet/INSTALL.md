# cPanel Deployment Guide

## 1. Prerequisites

- PHP 8.0 or newer (8.1+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- PHP extensions: `pdo_mysql`, `openssl`, `mbstring`, `json`,
  `curl` (recommended)
- mod_rewrite enabled in Apache (default on most cPanel hosts)

## 2. Upload the files

Use cPanel **File Manager** or **FTP/SFTP** to upload the contents of
`cryptofaucet/` into either:

- `public_html/` directly (site at `https://yourdomain.com/`), or
- `public_html/cryptofaucet/` (site at `https://yourdomain.com/cryptofaucet/`).

If you use a subdirectory, edit the line `# RewriteBase /` in the root
`.htaccess` to `RewriteBase /cryptofaucet/`.

## 3. Create the database

In cPanel **MySQL Databases**:

1. Create a new database (e.g. `<cpaneluser>_faucet`).
2. Create a new user with a strong password.
3. Add the user to the database with **ALL PRIVILEGES**.

Note the database name, username, and password.

## 4. Run the installer

Browse to `https://yourdomain.com/install.php` (or
`/cryptofaucet/install.php` if in a subdirectory).

- **Step 1** verifies PHP version, extensions, and folder permissions.
- **Step 2** asks for the database credentials and your admin account.
  The installer will:
  - Apply `database/schema.sql`.
  - Create your admin user with the bcrypt-hashed password.
  - Write `config/config.php` with a freshly generated `app_key`.
- **Step 3** confirms success.

**Then delete `install.php` from the server.** The installer will
refuse to re-run while a `config/config.php` exists, but removing the
file is still the right hygiene.

## 5. Set folder permissions

These folders must be writable by the PHP user. cPanel usually does this
automatically, but verify:

```
chmod 775 storage storage/logs storage/cache storage/backups
chmod 775 config
```

## 6. Configure the platform

Sign in at `/login` with the admin account and visit `/admin/settings`:

| Setting                               | What to set                          |
|---------------------------------------|--------------------------------------|
| `site_name`, `site_url`               | Your branding                        |
| `faucet_cooldown_seconds`             | e.g. `300` (5 minutes)               |
| `faucet_daily_limit`                  | Max claims per user per day          |
| `referral_percent`                    | e.g. `10` (10% of referred earnings) |
| `hilo_house_edge_percent`             | e.g. `3`                             |
| `recaptcha_site_key`, `recaptcha_secret` | From https://google.com/recaptcha |
| `faucetpay_api_key`                   | From your FaucetPay merchant account |
| `proxycheck_api_key`                  | From https://proxycheck.io (optional)|
| `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_from` | Your email relay (optional) |

In `/admin/coins`, edit the seeded coins (LTC, DOGE, TRX) or add new
ones. The `faucetpay_token` column must match the FaucetPay currency
code exactly (e.g. `LTC`, `DOGE`, `TRX`, `BTC`, `BCH`, `BNB`, `USDT`,
`ETH`, `SOL`, `DASH`, `DGB`, `ZEC`).

## 7. FaucetPay setup

1. Sign in to https://faucetpay.io and open **Merchant → API Keys**.
2. Generate an API key. Copy it and paste it into the `faucetpay_api_key`
   setting.
3. Whitelist your server's outbound IP if FaucetPay shows one.
4. Top up the FaucetPay balance for each coin you support.
5. Test a small payout by approving a withdrawal in `/admin/withdrawals`.

The `Approve` button calls `FaucetPay::send`. On HTTP 200 it stores the
returned `payout_id`. On any other status the failed message is logged
and the user's balance is automatically refunded.

## 8. Cron job

In cPanel → **Cron Jobs**, add a new entry running every 5 minutes:

```
*/5 * * * * php /home/<your-user>/public_html/cryptofaucet/cron.php >/dev/null 2>&1
```

What `cron.php` does:

- Auto-processes up to 25 pending withdrawals via FaucetPay.
- Auto-fails withdrawals stuck in `processing` for over 1 hour and
  refunds the user.
- Prunes old `rate_limits`, `password_resets`, and 90+ day
  `security_logs` rows.
- Marks PTC ads as `ended` once their `max_views` is reached.
- Expires unredeemed shortlink tokens older than 15 minutes.

If your host disables CLI cron, you can hit `cron.php?cron_token=…`
through an external scheduler (e.g. cron-job.org), but PHP-CLI mode is
recommended.

## 9. Hardening checklist

- [ ] Force HTTPS (cPanel → Domains → "Force HTTPS Redirect").
- [ ] Disable directory browsing (already done by `.htaccess`).
- [ ] Confirm `app/`, `config/`, `database/`, `storage/` return 403 when
      browsed directly.
- [ ] Set strong passwords on the cPanel and database users.
- [ ] Configure SMTP so password resets actually email — otherwise the
      reset link is written to `storage/logs/password-resets.log`
      for manual recovery.
- [ ] Schedule weekly `/admin/backup` exports off-server.
- [ ] Review `/admin/security` after launch to watch for
      `multi_account`, `claim_blocked_ip`, `login_failed` patterns.

## 10. Updating

To deploy a new version:

1. Upload new files (do **not** overwrite `config/config.php` or
   `storage/`).
2. Run any new migration SQL manually (none ship with v1).
3. Verify by browsing `/admin` and `/`.

## Troubleshooting

| Symptom                                           | Fix                                                   |
|---------------------------------------------------|-------------------------------------------------------|
| 500 error, blank page                             | Check `storage/logs/exception.log` and `php-error.log` |
| `mod_rewrite` not working                         | Add `RewriteBase /` or `/your-subdir/` to `.htaccess` |
| Admin can't log in (locked out 5 fails)           | `UPDATE users SET failed_logins=0, locked_until=NULL` |
| FaucetPay payouts return `Invalid api key`        | Re-paste API key; no whitespace; whitelist server IP  |
| Reset emails not sending                          | Set SMTP settings or read `storage/logs/password-resets.log` |
| Tailwind classes look broken                      | Make sure the CDN is reachable; otherwise self-host   |
