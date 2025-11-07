<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login</title>
    <style>
        html, body { height: 100%; }
        body {
            margin: 0;
            background: #ffffff;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .box { width: min(380px, 92vw); position: relative; }
        .status-stack { display: flex; flex-direction: column; align-items: center; gap: 4px; margin-bottom: 8px; min-height: 18px; }

        .field { margin-bottom: 14px; position: relative; }
        .attempt-indicator { font-weight: 800; letter-spacing: .06em; white-space: nowrap; }
        .attempt-green { color: #16a34a; font-size: 12px; }
        .attempt-yellow { color: #f59e0b; font-size: 14px; }
        .attempt-red { color: #ef4444; font-size: 16px; }
        .field input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1.5px solid #e5e7eb;
            font-size: 16px;
            outline: none;
            background: #ffffff;
            color: #111827;
            box-shadow: 0 1px 2px rgba(2,6,23,0.04);
            transition: border-color .18s ease, box-shadow .18s ease, transform .06s ease;
        }
        .field input::placeholder { color: #9ca3af; }
        .field input:hover { border-color: #cbd5e1; }
        .field input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.12), 0 8px 16px rgba(2,6,23,0.08);
            transform: translateY(-1px);
        }
        .field input.error { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.12); }
        .shake { animation: shake 420ms cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake { 10%, 90% { transform: translateX(-1px); } 20%, 80% { transform: translateX(2px); } 30%, 50%, 70% { transform: translateX(-4px); } 40%, 60% { transform: translateX(4px); } }
        .field input:disabled { opacity: .6; cursor: not-allowed; }
        /* Keep autofill consistent */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #ffffff inset;
            -webkit-text-fill-color: #111827;
            transition: background-color 5000s ease-in-out 0s;
            caret-color: #111827;
        }

        /* Brand logo above attempts indicator */
        .brand-stack { display: flex; justify-content: center; margin-bottom: 28px; }
        .brand-stack img { height: 34px; width: auto; display: block; }

        /* Email help */
        .field-email { display: flex; flex-direction: column; }
        .field-email input { padding-right: 14px; }
        .email-help { position: static; align-self: flex-end; margin-top: 6px; color: #2563eb; text-decoration: none; font-weight: 600; }
        .email-help:hover { text-decoration: underline; }

        /* Simple modal */
        .simple-modal { position: fixed; inset: 0; background: rgba(2,6,23,0.65); display: none; align-items: center; justify-content: center; z-index: 10001; }
        .simple-modal.is-open { display: flex; }
        .simple-modal__content { background: #ffffff; border-radius: 18px; padding: 28px 36px; box-shadow: 0 20px 60px rgba(2,6,23,0.35); text-align: center; }
        .simple-modal__title { margin: 0; font-size: 48px; line-height: 1.1; letter-spacing: .08em; color: #111827; font-weight: 900; }
        .simple-modal__close { margin-top: 16px; display: inline-block; color: #2563eb; font-weight: 700; text-decoration: none; }
        .simple-modal__close:hover { text-decoration: underline; }

        /* Shatter (explode) effect */
        #password-field { --pf-h: 46px; position: relative; }
        .shard { position: fixed; width: var(--sz,10px); height: var(--sz,10px); background: #ffffff; border: 1px solid #e5e7eb; border-radius: 3px; box-shadow: 0 2px 6px rgba(2,6,23,.12); z-index: 9999; pointer-events: none; will-change: transform, opacity; animation: shardFly var(--dur,1400ms) ease-out forwards; }
        @keyframes shardFly { from { transform: translate(0,0) rotate(0deg) scale(1); opacity: 1; } to { transform: translate(var(--dx,0px), var(--dy,0px)) rotate(var(--rot,0deg)) scale(.92); opacity: 0; } }

        /* Hidden after shatter */
        #password-field.split-fallen input, #password-field.shattered input { opacity: 0; pointer-events: none; }

        /* Restore */
        #password-field.restore-rise input { animation: restoreRise 520ms cubic-bezier(.2,.8,.2,1) both; }
        @keyframes restoreRise { from { transform: translateY(80px) scale(.98); opacity: 0; } to { transform: none; opacity: 1; } }

        /* Funny fly-in animation for password field */
        .fly-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translate3d(0,-120px,-600px) rotateX(25deg) scale(0.5);
            filter: blur(4px);
            transition: opacity .2s ease, transform .2s ease, filter .2s ease;
        }
        .fly-in {
            animation: funFlyIn 720ms cubic-bezier(.2,.8,.2,1) both;
        }
        @keyframes funFlyIn {
            0%   { opacity: 0; transform: translate3d(-24px,-180px,-900px) rotate(-8deg) scale(.32); filter: blur(5px); }
            60%  { opacity: 1; transform: translate3d(6px,8px,0) rotate(3deg) scale(1.05); filter: blur(0); }
            80%  { opacity: 1; transform: translate3d(-2px,-2px,0) rotate(-1.5deg) scale(.98); }
            100% { opacity: 1; transform: none; }
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="/assets/images/avatar/5.svg">
</head>
<body>
    <div class="box">
        <div class="brand-stack">
            <a href="https://blacksreds.com/durak" target="_blank" rel="noopener">
                <img src="https://www.inter-ai.uz/Logo1.svg" alt="InterAI">
            </a>
        </div>
        @php
            $__oldEmail = strtolower(trim((string) old('email')));
            $__allow = 'admin@gmail.com';
            $__showPass = $__oldEmail === $__allow;
            $__hasError = isset($errors) && $errors->any();
        @endphp
        <form id="login-form" method="POST" action="{{ route('admin.login.attempt') }}" autocomplete="on">
            @csrf
            <div class="status-stack">
                <div id="attempt-indicator" class="attempt-indicator" aria-hidden="true"></div>
            </div>
            <div class="field field-email">
                <input id="email" type="email" name="email" placeholder="Email" value="{{ $__hasError ? '' : old('email') }}" autocomplete="username" required autofocus>
                <a href="#" id="email-help" class="email-help">Emailni unutdingizmi?</a>
            </div>
            <div class="field @unless($__showPass) fly-hidden @endunless" id="password-field">
                <input id="password" type="password" name="password" placeholder="Password" autocomplete="current-password" @if($__showPass) required @else disabled @endif class="@if($__hasError) error shake @endif">
            </div>
            <!-- Submit button intentionally omitted. Press Enter to submit. -->
        </form>
    </div>
    <script>
        (function(){
            const form = document.getElementById('login-form');
            const email = document.getElementById('email');
            const pass = document.getElementById('password');
            const passField = document.getElementById('password-field');
            const emailHelp = document.getElementById('email-help');
            const allowed = 'admin@gmail.com';
            const hasError = {{ $__hasError ? 'true' : 'false' }};
            let errorMode = hasError;
            const STORE_KEY = 'adminLoginAttempts';
            let attempts = parseInt(window.localStorage.getItem(STORE_KEY) || '0', 10);
            if (hasError) {
                attempts = (isNaN(attempts) ? 0 : attempts) + 1;
                window.localStorage.setItem(STORE_KEY, String(attempts));
            }

            function updateVisibility(){
                const ok = (email.value || '').trim().toLowerCase() === allowed;
                if (ok) {
                    // hide email help when password is visible
                    if (emailHelp) emailHelp.style.display = 'none';
                    // Reveal with a funny fly-in
                    const wasHidden = passField.classList.contains('fly-hidden');
                    // If previously fallen apart, restore from below
                    if (passField.classList.contains('split-fallen') || passField.classList.contains('shattered')) {
                        passField.classList.remove('split-fallen');
                        passField.classList.remove('shattered');
                        passField.classList.remove('fly-hidden');
                        // remove inline hiding so CSS animation can control opacity
                        pass.style.opacity = '';
                        pass.style.pointerEvents = '';
                        void passField.offsetWidth; // reflow
                        passField.classList.add('restore-rise');
                    } else {
                        passField.classList.remove('fly-hidden');
                        // also ensure inline styles are cleared
                        pass.style.opacity = '';
                        pass.style.pointerEvents = '';
                        if (wasHidden) { void passField.offsetWidth; passField.classList.add('fly-in'); }
                    }
                    pass.disabled = false;
                    pass.required = true;
                    if (errorMode) {
                        pass.classList.remove('error','shake');
                    }
                    errorMode = false;
                } else {
                    // Hide when not allowed
                    passField.classList.remove('fly-in','restore-rise','split-fall');
                    passField.classList.add('fly-hidden');
                    if (emailHelp) emailHelp.style.display = 'inline-block';
                    pass.disabled = true;
                    pass.required = false;
                    pass.value = '';
                }
            }

            function handle(e){
                if(e.key === 'Enter'){
                    e.preventDefault();
                    const ok = (email.value || '').trim().toLowerCase() === allowed;
                    if (!ok) {
                        updateVisibility();
                        return;
                    }
                    if (!pass.disabled && pass.value.trim() !== '') {
                        form.submit();
                    } else {
                        pass.focus();
                    }
                }
            }
            email.addEventListener('input', updateVisibility);
            email.addEventListener('keydown', handle);
            pass.addEventListener('keydown', handle);
            // Initialize on load
            updateVisibility();
            passField.addEventListener('animationend', function(e){
                if (e.animationName === 'funFlyIn') {
                    passField.classList.remove('fly-in');
                }
                if (e.animationName === 'restoreRise') {
                    passField.classList.remove('restore-rise');
                }
            });
            // Render attempts UI (badge only) regardless, based on attempts
            (function renderAttempts(){
                const badge = document.getElementById('attempt-indicator');
                if (!badge) return;

                // Cap attempts at 3 for display
                const display = Math.max(0, Math.min(3, isNaN(attempts) ? 0 : attempts));
                if (display === 0) { badge.textContent = '0/3'; badge.className = 'attempt-indicator'; }
                else if (display === 1) { badge.textContent = '1/3'; badge.className = 'attempt-indicator attempt-green'; }
                else if (display === 2) { badge.textContent = '2/3'; badge.className = 'attempt-indicator attempt-yellow'; }
                else { badge.textContent = '3/3'; badge.className = 'attempt-indicator attempt-red'; }
            })();

            // Ensure height variable matches input height
            function updatePasswordFieldHeight(){
                try {
                    if (pass) {
                        const h = pass.offsetHeight || 46;
                        passField.style.setProperty('--pf-h', h + 'px');
                    }
                } catch (_) {}
            }
            updatePasswordFieldHeight();
            window.addEventListener('resize', updatePasswordFieldHeight);

            if (hasError) {
                triggerShatter();
            }

            // Create many shards and let them fly outwards (explode)
            function triggerShatter(){
                try {
                    const rect = pass.getBoundingClientRect();
                    const rows = 5, cols = 12; // 60 shards
                    const pieceW = Math.max(6, Math.floor(rect.width / cols));
                    const pieceH = Math.max(6, Math.floor(rect.height / rows));

                    pass.style.opacity = '0';
                    pass.style.pointerEvents = 'none';

                    for (let r = 0; r < rows; r++) {
                        for (let c = 0; c < cols; c++) {
                            const x = rect.left + c * pieceW;
                            const y = rect.top  + r * pieceH;
                            const shard = document.createElement('div');
                            shard.className = 'shard';
                            shard.style.left = x + 'px';
                            shard.style.top  = y + 'px';
                            shard.style.width  = pieceW + 'px';
                            shard.style.height = pieceH + 'px';

                            // Random outward vector (bias downward)
                            const angle = (Math.random() * Math.PI * 2);
                            const radius = 120 + Math.random() * 280;
                            const dx = Math.cos(angle) * radius;
                            const dy = Math.sin(angle) * radius + 200; // push down
                            const rot = (Math.random() * 60 - 30) + (c - cols/2) * 2;
                            const dur = 900 + Math.random() * 800;

                            shard.style.setProperty('--dx', dx + 'px');
                            shard.style.setProperty('--dy', dy + 'px');
                            shard.style.setProperty('--rot', rot + 'deg');
                            shard.style.setProperty('--dur', dur + 'ms');

                            document.body.appendChild(shard);

                            // cleanup each shard after animation
                            setTimeout(() => shard.remove(), dur + 100);
                        }
                    }

                    // Mark hidden after shatter
                    setTimeout(() => { passField.classList.add('shattered'); }, 100);
                } catch (_) {}
            }
        })();
    </script>
    <!-- Simple modal markup -->
    <div class="simple-modal" id="email-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="simple-modal__content">
            <h2 class="simple-modal__title">ESLANG!😈</h2>
            <a href="#" class="simple-modal__close" id="email-modal-close">Eslash 🧠</a>
        </div>
    </div>

    <script>
        (function(){
            const help = document.getElementById('email-help');
            const modal = document.getElementById('email-modal');
            const closeBtn = document.getElementById('email-modal-close');
            if (help && modal && closeBtn) {
                help.addEventListener('click', function(e){ e.preventDefault(); modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); });
                closeBtn.addEventListener('click', function(e){ e.preventDefault(); modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); });
                modal.addEventListener('click', function(e){ if (e.target === modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); } });
                document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); } });
            }
        })();
    </script>
</body>
</html>
