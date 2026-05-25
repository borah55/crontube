# Crypto Faucet Platform

A self-hosted cryptocurrency faucet platform for cPanel shared hosting.
Pure PHP 8 + MySQL — **no Node.js, no Composer, no build step required**.

## Highlights

- **User system** — registration, login, forgot/reset password, referrals,
  leaderboard, daily bonus, transaction ledger.
- **Faucet** — multi-coin claims with per-coin cooldown, daily caps, random
  reward, reCAPTCHA, IP/device fingerprint multi-account guard, optional
  proxy/VPN blocking via [proxycheck.io](https://proxycheck.io).
- **FaucetPay payouts** — admin-approved or fully automated via cron;
  address verification, refund-on-failure, configurable flat + percent fees,
  per-IP-per-day withdrawal cap.
- **Hi-Lo game** — provably fair (HMAC-SHA256 with revealed server-seed
  hash, rotating every 100 nonces), admin-tunable house edge.
- **PTC ads** — admin creates ads with reward, duration, daily and total
  caps; per-view session token blocks instant claims and bots.
- **Shortlinks** — rotating token round-trip with 10s minimum dwell.
- **Admin panel** — dashboard with 15+ KPIs, user management, coin
  management, withdrawal queue, PTC and shortlink CRUD, security log,
  IP blacklist, announcement system, settings, **PHP-only DB backup**,
  one-click maintenance mode toggle.
- **Security** — CSRF tokens on every POST, hardened sessions
  (HttpOnly, SameSite, fingerprint, regeneration), prepared statements
  everywhere, password hashing with bcrypt, 5-fail lockout, sliding-window
  rate limits, IP blacklist, security audit log.
- **UI** — Tailwind via CDN, dark/light toggle, mobile responsive.

## Project layout

```
cryptofaucet/
├── index.php             Front controller
├── install.php           One-time installer (3 steps)
├── cron.php              cPanel cron entry point
├── .htaccess             Routing + security headers
├── app/
│   ├── Core/             Framework (router, db, auth, security, …)
│   ├── Controllers/      Route handlers (HomeController, AuthController, …)
│   ├── Controllers/Admin/ Admin panel controllers
│   ├── Models/           Tiny PDO models
│   └── Views/            PHP templates (Tailwind via CDN)
├── assets/
│   ├── css/app.css       Animations / extra utility classes
│   └── js/app.js         Vanilla JS (theme toggle, AJAX flows)
├── config/
│   ├── config.example.php Template the installer copies
│   └── routes.php        Route table
├── database/
│   └── schema.sql        Full MySQL schema + seed data
└── storage/
    ├── logs/             Cron, errors, password-reset fallback
    ├── cache/
    └── backups/
```

## Quickstart

1. Upload everything in `cryptofaucet/` into a folder under `public_html/`
   (or directly to `public_html/` itself).
2. Browse to `/install.php` and follow the wizard.
3. **Delete `install.php`** when the wizard finishes.
4. Sign in at `/login` and set up FaucetPay, reCAPTCHA, SMTP, and coins
   in `/admin/settings`.
5. Add a cron job (cPanel → Cron Jobs):

    ```
    */5 * * * * php /home/<your-user>/public_html/cryptofaucet/cron.php >/dev/null 2>&1
    ```

See [INSTALL.md](INSTALL.md) for cPanel-specific deployment steps.

## What is *not* yet implemented

The foundation here is production-grade, but the following enhancements
were intentionally left as extension points (controllers/routes are not
wired up):

- **2FA for admins** (column `users.two_factor_secret` already exists).
- **Telegram notifications** (settings keys exist; just plug in your
  webhook).
- **Lucky wheel / achievement badges**.
- **Crypto price API integration** (e.g. CoinGecko).
- **Multi-language**.
- **Email backend with PHPMailer** (currently uses native `mail()` and
  falls back to a log file when SMTP is unset).
- **Offerwall integration** — pick a provider (CPX Research, AdGate,
  OfferToro) and wire it up similarly to shortlinks.

## Compliance & licensing

This software is provided as-is for self-hosting your own faucet. **You
are responsible for:** complying with cryptocurrency, gambling, advertising,
KYC/AML, and tax regulations in every jurisdiction where you operate or
where your users access the site, and for honoring FaucetPay's terms of
service. Many jurisdictions require a licence to operate gambling
products such as Hi-Lo.
