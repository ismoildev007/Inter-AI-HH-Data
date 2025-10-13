@php
    $appName = config('app.name', 'Inter-AI');
    $logoPath = asset('assets/images/avatar/5.svg');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Inte-AI — 404 Not Found</title>
        <link rel="icon" type="image/svg+xml" href="{{ $logoPath }}" />
        <style>
            :root {
                color-scheme: light dark;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: clamp(1.5rem, 3vw, 3rem);
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI',
                    sans-serif;
                background: linear-gradient(155deg, #f7faff 0%, #e1e9ff 45%, #d3ddff 100%);
                color: #10224d;
            }

            main.error-card {
                width: min(760px, 100%);
                padding: clamp(2.8rem, 4vw, 4.2rem);
                border-radius: 36px;
                background: rgba(250, 252, 255, 0.92);
                box-shadow: 0 30px 80px rgba(26, 46, 118, 0.18);
                position: relative;
                overflow: hidden;
                border: 1px solid rgba(112, 140, 255, 0.2);
                backdrop-filter: blur(24px);
            }

            main.error-card::before,
            main.error-card::after {
                content: '';
                position: absolute;
                border-radius: 50%;
                opacity: 0.22;
                filter: blur(0);
                z-index: 0;
            }

            main.error-card::before {
                width: 420px;
                height: 420px;
                background: radial-gradient(circle at 30% 30%, rgba(94, 132, 255, 0.58), rgba(94, 132, 255, 0));
                top: -220px;
                right: -110px;
            }

            main.error-card::after {
                width: 360px;
                height: 360px;
                background: radial-gradient(circle at 60% 70%, rgba(138, 170, 255, 0.42), rgba(138, 170, 255, 0));
                bottom: -180px;
                left: -160px;
            }

            .content {
                position: relative;
                z-index: 1;
                display: grid;
                gap: clamp(1.5rem, 3vw, 2.1rem);
            }

            .brand-mark {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .brand-mark figure {
                width: clamp(80px, 12vw, 110px);
                height: clamp(80px, 12vw, 110px);
                display: grid;
                place-items: center;
                border-radius: 50%;
                background: linear-gradient(135deg, rgba(238, 243, 255, 0.95), rgba(208, 220, 255, 0.75));
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 20px 30px rgba(32, 52, 122, 0.2);
                border: 3px solid rgba(118, 146, 255, 0.35);
                padding: clamp(0.75rem, 2vw, 1.2rem);
            }

            .brand-mark figure img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                border-radius: 50%;
            }

            .brand-mark .badge {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.55rem 1.2rem;
                border-radius: 999px;
                background: rgba(112, 140, 255, 0.16);
                color: rgba(16, 34, 77, 0.75);
                font-size: 0.85rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .code-display {
                display: grid;
                gap: 0.7rem;
            }

            .code-display .numeral {
                font-size: clamp(3.8rem, 12vw, 5.8rem);
                font-weight: 700;
                letter-spacing: -0.04em;
                color: #0f2b7a;
                text-shadow: 0 18px 45px rgba(23, 45, 108, 0.28);
            }

            .code-display h1 {
                margin: 0;
                font-size: clamp(2.2rem, 6vw, 3.4rem);
                letter-spacing: -0.018em;
                line-height: 1.05;
            }

            p {
                margin: 0;
                color: rgba(16, 34, 77, 0.78);
                font-size: clamp(1rem, 2.3vw, 1.15rem);
                line-height: 1.7;
                max-width: 54ch;
            }

            footer {
                font-size: 0.85rem;
                color: rgba(16, 32, 72, 0.55);
            }

            @media (max-width: 600px) {
                .brand-mark {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .brand-mark figure {
                    width: 84px;
                    height: 84px;
                }
            }

            @media (prefers-color-scheme: dark) {
                body {
                    background: radial-gradient(circle at 20% 20%, #050c20 0%, #07122c 45%, #081536 100%);
                    color: #e6ecff;
                }

                main.error-card {
                    background: rgba(10, 16, 32, 0.92);
                    border-color: rgba(96, 126, 255, 0.28);
                    box-shadow: 0 30px 80px rgba(6, 14, 48, 0.68);
                }

                .brand-mark figure {
                    background: linear-gradient(135deg, rgba(26, 38, 86, 0.85), rgba(14, 22, 58, 0.92));
                    border-color: rgba(96, 122, 255, 0.32);
                    box-shadow: inset 0 1px 0 rgba(196, 210, 255, 0.24), 0 20px 35px rgba(6, 12, 38, 0.75);
                }

                .brand-mark .badge {
                    background: rgba(84, 114, 255, 0.2);
                    color: rgba(210, 222, 255, 0.75);
                }

                .code-display .numeral {
                    color: #c6d2ff;
                    text-shadow: 0 22px 55px rgba(10, 22, 68, 0.7);
                }

                p {
                    color: rgba(214, 226, 255, 0.72);
                }

                footer {
                    color: rgba(196, 210, 255, 0.45);
                }
            }
        </style>
    </head>
    <body>
        <main class="error-card" role="main">
            <div class="content">
                <header class="brand-mark">
                    <figure>
                        <img src="{{ $logoPath }}" alt="{{ $appName }} logo" />
                    </figure>
                    <span class="badge">Inter-AI Uz</span>
                </header>

                <div class="code-display">
                    <span class="numeral">404</span>
                    <h1>Sahifa topilmadi</h1>
                </div>

                <p>
                    Ushbu manzil jamoamiz tomonidan o‘chirib tashlangan bo‘lishi mumkin yoki siz eski havoladan
                    foydalandingiz. Iltimos, kerakli ma’lumotni olish uchun menyu yoki qidiruvdan foydalaning.
                </p>

                <footer>
                    Agar bu nosozlik deb o‘ylasangiz, iltimos tizim administratori bilan bog‘laning.
                </footer>
            </div>
        </main>
    </body>
</html>
