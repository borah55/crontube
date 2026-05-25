// Tiny client framework.  Vanilla JS, no build step.
(function () {
    'use strict';

    // ---- Theme toggle ------------------------------------------------
    function applyTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        try { localStorage.setItem('cf_theme', theme); } catch (e) { /* ignore */ }
    }
    var saved = null;
    try { saved = localStorage.getItem('cf_theme'); } catch (e) {}
    if (!saved && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        saved = 'dark';
    }
    applyTheme(saved || 'light');

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-theme-toggle]');
        if (btn) {
            var isDark = document.documentElement.classList.contains('dark');
            applyTheme(isDark ? 'light' : 'dark');
        }
    });

    // ---- CSRF helper -------------------------------------------------
    window.CF = window.CF || {};
    CF.csrf = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    CF.post = function (url, data) {
        var fd;
        if (data instanceof FormData) fd = data;
        else {
            fd = new FormData();
            Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
        }
        if (!fd.has('_csrf')) fd.append('_csrf', CF.csrf());
        return fetch(url, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CF.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); });
    };

    // ---- Toast -------------------------------------------------------
    CF.toast = function (msg, type) {
        type = type || 'info';
        var colors = {
            success: 'bg-emerald-600',
            error:   'bg-rose-600',
            info:    'bg-indigo-600',
            warning: 'bg-amber-600',
        };
        var el = document.createElement('div');
        el.className = 'fixed top-5 right-5 z-50 text-white px-4 py-2 rounded-lg shadow-lg cf-pop ' + (colors[type] || colors.info);
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3500);
    };

    // ---- Faucet claim flow ------------------------------------------
    CF.faucet = function (form, btn, timerEl) {
        var coinId = form.querySelector('[name="coin_id"]').value;
        btn.disabled = true; btn.textContent = 'Claiming...';

        var data = { coin_id: coinId };
        var captcha = form.querySelector('[name="g-recaptcha-response"]');
        if (captcha) data['g-recaptcha-response'] = captcha.value;

        CF.post('/faucet/claim', data).then(function (resp) {
            btn.disabled = false;
            if (resp.ok) {
                CF.toast('+' + resp.amount + ' ' + resp.coin + ' credited!', 'success');
                var balEl = document.getElementById('cf-balance');
                if (balEl) balEl.textContent = resp.balance;
                CF.startCountdown(timerEl, resp.next_seconds, btn);
            } else if (resp.error === 'cooldown' && resp.seconds_left) {
                CF.startCountdown(timerEl, resp.seconds_left, btn);
                CF.toast('Wait ' + resp.seconds_left + 's', 'warning');
            } else {
                btn.classList.add('cf-shake');
                setTimeout(function () { btn.classList.remove('cf-shake'); }, 500);
                CF.toast('Claim failed: ' + (resp.error || 'unknown'), 'error');
                btn.textContent = 'Claim';
            }
        });
    };

    CF.startCountdown = function (el, seconds, btn) {
        if (!el) return;
        if (btn) btn.disabled = true;
        var end = Date.now() + seconds * 1000;
        function tick() {
            var left = Math.max(0, Math.round((end - Date.now()) / 1000));
            var m = Math.floor(left / 60), s = left % 60;
            el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            if (left <= 0) {
                if (btn) { btn.disabled = false; btn.textContent = 'Claim'; }
                clearInterval(timer);
                el.textContent = 'Ready!';
            }
        }
        var timer = setInterval(tick, 1000);
        tick();
    };

    // ---- Hi-Lo bet ---------------------------------------------------
    CF.hilo = function (form, prediction, resultBox) {
        var data = new FormData(form);
        data.append('prediction', prediction);
        CF.post('/hilo/bet', data).then(function (resp) {
            if (!resp.ok) {
                CF.toast('Bet failed: ' + (resp.error || 'unknown'), 'error');
                return;
            }
            var bal = document.getElementById('cf-balance');
            if (bal) bal.textContent = resp.balance;
            var color = resp.result === 'win' ? 'text-emerald-500'
                      : resp.result === 'loss' ? 'text-rose-500' : 'text-amber-500';
            resultBox.innerHTML = '<div class="cf-pop ' + color + ' text-3xl font-bold">' +
                'Roll: ' + resp.roll + ' (' + resp.result.toUpperCase() + ')</div>' +
                '<div class="text-sm opacity-80">Payout: ' + resp.payout + ' &times; ' + resp.multiplier + '</div>';
        });
    };

    // ---- PTC timer + claim ------------------------------------------
    CF.ptc = function (timerEl, claimBtn, adId, duration, token) {
        var left = duration;
        timerEl.textContent = left;
        var iv = setInterval(function () {
            left--;
            timerEl.textContent = left;
            if (left <= 0) {
                clearInterval(iv);
                claimBtn.disabled = false;
                claimBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                timerEl.textContent = 'Ready!';
            }
        }, 1000);

        claimBtn.addEventListener('click', function () {
            CF.post('/ptc/claim/' + adId, { token: token }).then(function (resp) {
                if (resp.ok) {
                    CF.toast('+' + resp.amount + ' credited!', 'success');
                    setTimeout(function () { location.href = '/ptc'; }, 1200);
                } else {
                    CF.toast('Failed: ' + (resp.error || 'unknown'), 'error');
                }
            });
        });
    };

    // ---- Daily bonus ------------------------------------------------
    CF.dailyBonus = function (btn) {
        btn.disabled = true;
        CF.post('/bonus/daily', {}).then(function (resp) {
            btn.disabled = false;
            if (resp.ok) {
                CF.toast('Daily bonus +' + resp.amount + '! Streak ' + resp.streak, 'success');
                var bal = document.getElementById('cf-balance');
                if (bal) bal.textContent = resp.balance;
                btn.textContent = 'Claimed today';
                btn.disabled = true;
            } else {
                CF.toast('Already claimed today', 'warning');
            }
        });
    };
})();
