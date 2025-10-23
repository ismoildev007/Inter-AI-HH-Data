@php
    $availableLocales = [
        'uz' => [
            'title' => __('admin.header.language.uz'),
            'short' => 'UZ',
            'flag' => 'fi fi-uz',
        ],
        'ru' => [
            'title' => __('admin.header.language.ru'),
            'short' => 'RU',
            'flag' => 'fi fi-ru',
        ],
        'en' => [
            'title' => __('admin.header.language.en'),
            'short' => 'EN',
            'flag' => 'fi fi-gb',
        ],
    ];
    $currentLocale = app()->getLocale();
    $currentLocaleMeta = $availableLocales[$currentLocale] ?? null;
@endphp

<header class="nxl-header">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="header-right ms-auto d-flex align-items-center gap-3">
            <div class="nxl-h-item d-flex align-items-center gap-2">

                <div class="language-switcher me-4">
                    <button type="button"
                            class="language-switcher__trigger"
                            data-lang-toggle
                            aria-haspopup="true"
                            aria-expanded="false"
                            title="{{ __('admin.header.language.choose') }}">
                        @if ($currentLocaleMeta)
                            <span class="language-switcher__flag {{ $currentLocaleMeta['flag'] }}"></span>
                            <span class="language-switcher__trigger-label">
                                {{ $currentLocaleMeta['short'] }}
                            </span>
                        @else
                        <span class="language-switcher__trigger-label">
                            {{ strtoupper($currentLocale) }}
                        </span>
                        @endif
                        <i class="feather-chevron-down"></i>
                    </button>
                    <div class="language-switcher__menu" data-lang-menu role="menu">
                        @foreach ($availableLocales as $localeCode => $localeMeta)
                            <a href="{{ route('admin.locale.switch', $localeCode) }}"
                               class="language-switcher__option {{ $currentLocale === $localeCode ? 'is-active' : '' }}"
                               role="menuitem"
                               title="{{ __('admin.header.language.switch_to', ['language' => $localeMeta['title']]) }}">
                                <span class="language-switcher__option-flag {{ $localeMeta['flag'] }}"></span>
                                <span class="language-switcher__option-text">
                                    <span class="code">{{ $localeMeta['short'] }}</span>
                                    <span class="name">{{ $localeMeta['title'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <a href="javascript:void(0);"
                   onclick="window.location.reload();"
                   class="nxl-head-link text-black ms-2 me-5"
                   title="{{ __('admin.header.refresh_title') }}">
                   {{ __('admin.header.refresh') }}・
                    <i class="feather-refresh-cw"></i>
                </a>


                <!-- Profil -->
                <a href="{{ route('admin.profile') }}"
                     class="nxl-head-link me-0 text-black"
                    title="{{ __('admin.header.profile_title') }}">
                    {{ __('admin.header.profile') }}・
                    <div class="avatar-text avatar-md">
                        <img src="{{ asset('assets/images/avatar/5.svg') }}"
                            alt=""
                            class="img-fluid">
                    </div>
                </a>

                <!-- Refresh tugma -->


                <!-- Logout -->
                <a href="{{ route('admin.logout') }}"
                    class="nxl-head-link  text-black"
                    title="{{ __('admin.header.logout_title') }}"
                    aria-label="{{ __('admin.header.logout') }}">
                    <i class="feather-log-out text-red"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<style>
    /* Profil */
    .nxl-h-item .nxl-head-link.profile-link {
        background-color: #3b82f6 !important; /* blue-500 */
        transition: background-color 0.3s ease;
    }
    .nxl-h-item .nxl-head-link.profile-link:hover {
        background-color: #1d4ed8 !important; /* blue-700 */
    }

    /* Refresh */
    .nxl-h-item .nxl-head-link.refresh-link {
        background-color: #10b981 !important; /* green-500 */
        transition: background-color 0.3s ease;
    }
    .nxl-h-item .nxl-head-link.refresh-link:hover {
        background-color: #047857 !important; /* green-700 */
    }

    /* Logout */
    .nxl-h-item .nxl-head-link.logout-link {
        background-color: #ef4444 !important; /* red-500 */
        transition: background-color 0.3s ease;
    }
    .nxl-h-item .nxl-head-link.logout-link:hover {
        background-color: #b91c1c !important; /* red-700 */
    }

    .language-switcher {
        position: relative;
    }

    .language-switcher__trigger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 9999px;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        font-weight: 600;
        color: #1f2937;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.05em;
        transition: all 0.2s ease;
    }

    .language-switcher__trigger:hover,
    .language-switcher__trigger[aria-expanded="true"] {
        border-color: #0ea5e9;
        color: #0ea5e9;
        box-shadow: 0 8px 18px rgba(14, 165, 233, 0.1);
    }

    .language-switcher__flag {
        display: inline-block;
        width: 18px;
        height: 12px;
        border-radius: 4px;
        background-size: cover;
        background-position: center;
    }

    .language-switcher__menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 160px;
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
        padding: 8px;
        display: none;
        z-index: 1000;
    }

    .language-switcher__option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 13px;
        color: #1f2937;
        transition: all 0.2s ease;
    }

    .language-switcher__option:hover {
        background: rgba(14, 165, 233, 0.12);
        color: #0ea5e9;
    }

    .language-switcher__option.is-active {
        background: #0ea5e9;
        color: #ffffff;
    }

    .language-switcher__option-flag {
        width: 24px;
        height: 16px;
        border-radius: 5px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }

    .language-switcher__option-text {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
    }

    .language-switcher__option-text .code {
        font-weight: 700;
        font-size: 12px;
        letter-spacing: 0.08em;
    }

    .language-switcher__option-text .name {
        font-size: 11px;
        color: #64748b;
        text-transform: none;
    }

    .language-switcher__option.is-active .language-switcher__option-text .name {
        color: rgba(255, 255, 255, 0.9);
    }

    @media (max-width: 768px) {
        .language-switcher__menu {
            left: 0;
            right: auto;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trigger = document.querySelector('[data-lang-toggle]');
        const menu = document.querySelector('[data-lang-menu]');

        if (!trigger || !menu) {
            return;
        }

        const closeMenu = () => {
            menu.style.display = 'none';
            trigger.setAttribute('aria-expanded', 'false');
        };

        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            const isOpen = menu.style.display === 'block';
            if (isOpen) {
                closeMenu();
            } else {
                menu.style.display = 'block';
                trigger.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target) && !trigger.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    });
</script>
