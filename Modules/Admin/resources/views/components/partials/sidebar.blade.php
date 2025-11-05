@php
    $primaryLinks = [];

    $navigationGroups = [
        [
            'label' => __('admin.sidebar.groups.dashboards.label'),
            'items' => [
                [
                    'label' => __('admin.sidebar.items.default_dashboard'),
                    'icon' => 'airplay',
                    'route' => 'admin.dashboard',
                    'match' => ['admin.dashboard'],
                    'hint' => __('admin.sidebar.hints.overview_metrics'),
                ],
                [
                    'label' => __('admin.sidebar.items.billing_dashboard'),
                    'icon' => 'pie-chart',
                    'route' => 'admin.dashboard.billing',
                    'match' => ['admin.dashboard.billing'],
                    'hint' => __('admin.sidebar.hints.revenue_insights'),
                ],
            ],
        ],
        [
            'label' => __('admin.sidebar.groups.users.label'),
            'items' => [
                [
                    'label' => __('admin.sidebar.items.users'),
                    'icon' => 'users',
                    'route' => 'admin.users.index',
                    'match' => ['admin.users.*'],
                    'hint' => __('admin.sidebar.hints.directory_profiles'),
                ],
                [
                    'label' => __('admin.sidebar.items.resumes'),
                    'icon' => 'file-text',
                    'route' => 'admin.resumes.index',
                    'match' => ['admin.resumes.*'],
                    'hint' => __('admin.sidebar.hints.talent_archive'),
                ],
                [
                    'label' => __('admin.sidebar.items.applications'),
                    'icon' => 'briefcase',
                    'route' => 'admin.applications.index',
                    'match' => ['admin.applications.*'],
                    'hint' => __('admin.sidebar.hints.recruiting_pipeline'),
                ],
            ],
        ],
        [
            'label' => __('admin.sidebar.groups.vacancies_channels.label'),
            'items' => [
                [
                    'label' => __('admin.sidebar.items.telegram_channels'),
                    'icon' => 'send',
                    'route' => 'admin.telegram_channels.index',
                    'match' => ['admin.telegram_channels.*'],
                    'hint' => __('admin.sidebar.hints.broadcast_control'),
                ],
                [
                    'label' => __('admin.sidebar.items.all_vacancies'),
                    'icon' => 'clipboard',
                    'route' => 'admin.vacancies.categories',
                    'match' => ['admin.vacancies.*'],
                    'hint' => __('admin.sidebar.hints.broadcast_control'),
                ],
            ],
        ],
        [
            'label' => __('admin.sidebar.groups.billing.label'),
            'items' => [
                [
                    'label' => __('admin.sidebar.items.plans'),
                    'icon' => 'credit-card',
                    'route' => 'admin.plans.index',
                    'match' => ['admin.plans.*'],
                    'hint' => __('admin.sidebar.hints.pricing_blueprints'),
                ],
                [
                    'label' => __('admin.sidebar.items.subscriptions'),
                    'icon' => 'repeat',
                    'route' => 'admin.subscriptions.index',
                    'match' => ['admin.subscriptions.*'],
                    'hint' => __('admin.sidebar.hints.revenue_stream'),
                ],
                [
                    'label' => __('admin.sidebar.items.transactions'),
                    'icon' => 'credit-card',
                    'route' => 'admin.transactions.index',
                    'match' => ['admin.transactions.*'],
                    'hint' => __('admin.sidebar.hints.payments_ledger'),
                ],
            ],
        ],
    ];
@endphp

<nav class="nxl-navigation admin-sidebar">
    <div class="admin-sidebar__inner">
        <div class="admin-sidebar__brand">
            <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand-link">
  
                    <img src="https://www.inter-ai.uz/Logo1.svg" alt="InterAI" class="img-fluid">

            </a>
        </div>
        <div class="admin-sidebar__body">
            @if (!empty($primaryLinks))
                <div class="admin-sidebar__singles">
                    @foreach ($primaryLinks as $link)
                        @php
                            $patterns = (array)($link['match'] ?? $link['route']);
                            $isActiveSingle = request()->routeIs(...$patterns);
                        @endphp
                        <a href="{{ route($link['route']) }}" class="admin-sidebar__single-link {{ $isActiveSingle ? 'is-active' : '' }}">
                            <span class="admin-sidebar__single-icon">
                                <i class="feather-{{ $link['icon'] }}"></i>
                            </span>
                            <span class="admin-sidebar__single-text">
                                <span class="label">{{ $link['label'] }}</span>
                                @if (!empty($link['hint']))
                                    <span class="hint">{{ $link['hint'] }}</span>
                                @endif
                            </span>
                            <span class="admin-sidebar__single-arrow">
                                <i class="feather-chevron-right"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="admin-sidebar__content">
                @foreach ($navigationGroups as $index => $group)
                    @php
                        $groupItems = collect($group['items']);
                        $groupIsOpen = $groupItems->contains(function ($item) {
                            $patterns = (array)($item['match'] ?? $item['route']);
                            return request()->routeIs(...$patterns);
                        });
                    @endphp
                    <div class="admin-sidebar__section {{ $groupIsOpen ? 'is-open' : '' }}" data-group="{{ $index }}">
                        <button type="button" class="admin-sidebar__section-toggle" aria-expanded="{{ $groupIsOpen ? 'true' : 'false' }}">
                            <span class="admin-sidebar__section-label">{{ $group['label'] }}</span>
                            <span class="admin-sidebar__section-arrow">
                                <i class="feather-chevron-down"></i>
                            </span>
                        </button>
                        <ul class="admin-sidebar__list">
                            @foreach ($group['items'] as $item)
                                @php
                                    $patterns = (array)($item['match'] ?? $item['route']);
                                    $isActive = request()->routeIs(...$patterns);
                                @endphp
                                <li class="admin-sidebar__item {{ $isActive ? 'is-active' : '' }}">
                                    <a href="{{ route($item['route']) }}" class="admin-sidebar__link">
                                        <span class="admin-sidebar__icon">
                                            <i class="feather-{{ $item['icon'] }}"></i>
                                        </span>
                                        <span class="admin-sidebar__text">
                                            <span class="admin-sidebar__label">{{ $item['label'] }}</span>
                                            @if (!empty($item['hint']))
                                                <span class="admin-sidebar__hint">{{ $item['hint'] }}</span>
                                            @endif
                                        </span>
                                        <span class="admin-sidebar__chevron">
                                            <i class="feather-chevron-right"></i>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</nav>

<style>
    .admin-sidebar {
        background: #ffffff;
        color: #1f2937;
        box-shadow: 6px 0 18px rgba(15, 23, 42, 0.08);
    }
    .admin-sidebar__inner {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 100vh;
        padding: 18px 16px;
        gap: 22px;
        overflow: hidden;
    }
    .admin-sidebar__body {
        flex: 1 1 auto;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 22px;
        padding-right: 6px;
        margin-right: -6px;
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
    }
    .admin-sidebar__body::-webkit-scrollbar {
        width: 6px;
    }
    .admin-sidebar__body::-webkit-scrollbar-track {
        background: transparent;
    }
    .admin-sidebar__body::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.35);
        border-radius: 9999px;
    }
    .admin-sidebar__body:hover::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.45);
    }
    .admin-sidebar__brand {
        padding: 12px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        box-shadow: none;
        flex-shrink: 0;
    }
    .admin-sidebar__brand-link {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
    }
    .admin-sidebar__brand-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #e2e8f0;
        display: grid;
        place-items: center;
        overflow: hidden;
    }
    .admin-sidebar__brand-meta {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .admin-sidebar__brand-title {
        font-weight: 600;
        letter-spacing: 0.04em;
    }
.admin-sidebar__brand-sub {
    font-size: 0.78rem;
    color: #6b7280;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.admin-sidebar__singles {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.admin-sidebar__content {
    display: flex;
    flex-direction: column;
    gap: 14px;
    flex: 1 1 auto;
    padding-bottom: 24px;
}
.admin-sidebar__single-link {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 12px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    background: rgba(248, 250, 252, 0.7);
    text-decoration: none;
    color: #334155;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease, background 0.18s ease;
}
.admin-sidebar__single-link:hover {
    border-color: rgba(59, 130, 246, 0.35);
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
    background: rgba(238, 242, 255, 0.7);
}
.admin-sidebar__single-link.is-active {
    border-color: rgba(59, 130, 246, 0.45);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.04));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 10px 18px rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
}
.admin-sidebar__single-icon {
    width: 32px;
    height: 32px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
    font-size: 1rem;
}
.admin-sidebar__single-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.admin-sidebar__single-text .label {
    font-weight: 500;
    font-size: 0.9rem;
}
.admin-sidebar__single-text .hint {
    font-size: 0.78rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}
.admin-sidebar__single-arrow {
    color: #94a3b8;
    transition: transform 0.18s ease, color 0.18s ease;
}
.admin-sidebar__single-link:hover .admin-sidebar__single-arrow,
.admin-sidebar__single-link.is-active .admin-sidebar__single-arrow {
    transform: translateX(4px);
    color: #1d4ed8;
}
    .admin-sidebar__section {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .admin-sidebar__section-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 10px 14px;
        color: #475569;
        font-size: 0.68rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        font-weight: 600;
        cursor: pointer;
        border-radius: 12px;
        transition: color 0.18s ease, border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
    }
    .admin-sidebar__section-toggle:hover {
        color: #1d4ed8;
        border-color: rgba(59, 130, 246, 0.35);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
        background: #f1f5ff;
    }
    .admin-sidebar__section.is-open .admin-sidebar__section-toggle {
        color: #1f2937;
        border-color: rgba(59, 130, 246, 0.45);
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.05));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 10px 18px rgba(59, 130, 246, 0.12);
    }
    .admin-sidebar__section-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: transform 0.18s ease;
    }
    .admin-sidebar__section.is-open .admin-sidebar__section-arrow {
        transform: rotate(180deg);
    }
    .admin-sidebar__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-4px);
        transition: max-height 0.2s ease, opacity 0.2s ease, transform 0.2s ease;
    }
    .admin-sidebar__section.is-open .admin-sidebar__list {
        max-height: 480px;
        opacity: 1;
        transform: translateY(0);
    }
    .admin-sidebar__item {
        position: relative;
    }
    .admin-sidebar__link {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 10px 12px;
        border-radius: 12px;
        color: #334155;
        text-decoration: none;
        background: transparent;
        border: 1px solid transparent;
        transition: all 0.16s ease;
    }
    .admin-sidebar__item.is-active .admin-sidebar__link,
    .admin-sidebar__link:hover {
        color: #1d4ed8;
        background: #eef2ff;
        border-color: #c7d2fe;
        box-shadow: none;
    }
    .admin-sidebar__icon {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: #e2e8f0;
        color: #1f2937;
        font-size: 1.05rem;
    }
    .admin-sidebar__item.is-active .admin-sidebar__icon {
        background: #c7d2fe;
        color: #1d4ed8;
    }
    .admin-sidebar__text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .admin-sidebar__label {
        font-weight: 600;
        font-size: 0.95rem;
        color: inherit;
    }
    .admin-sidebar__hint {
        font-size: 0.78rem;
        color: #94a3b8;
    }
    .admin-sidebar__chevron {
        opacity: 0;
        color: #94a3b8;
        transition: transform 0.18s ease, opacity 0.18s ease;
    }
    .admin-sidebar__item.is-active .admin-sidebar__chevron,
    .admin-sidebar__link:hover .admin-sidebar__chevron {
        opacity: 1;
        transform: translateX(6px);
    }

    @media (max-width: 1199px) {
        .admin-sidebar__inner {
            padding: 16px 12px;
            gap: 18px;
        }
        .admin-sidebar__body {
            padding-right: 4px;
            margin-right: -4px;
        }
        .admin-sidebar__label {
            font-size: 0.88rem;
        }
        .admin-sidebar__hint {
            font-size: 0.74rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const storageKey = 'adminSidebarGroups';
        let persisted = {};

        try {
            persisted = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
        } catch (error) {
            persisted = {};
        }

        document.querySelectorAll('.admin-sidebar__section').forEach((section) => {
            const key = section.dataset.group;
            const toggle = section.querySelector('.admin-sidebar__section-toggle');
            if (!toggle) {
                return;
            }

            const serverOpen = section.classList.contains('is-open');

            if (Object.prototype.hasOwnProperty.call(persisted, key)) {
                const shouldOpen = serverOpen || !!persisted[key];
                section.classList.toggle('is-open', shouldOpen);
                toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            } else {
                toggle.setAttribute('aria-expanded', serverOpen ? 'true' : 'false');
            }

            toggle.addEventListener('click', () => {
                const nextState = !section.classList.contains('is-open');
                section.classList.toggle('is-open', nextState);
                toggle.setAttribute('aria-expanded', nextState ? 'true' : 'false');

                persisted[key] = nextState;
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(persisted));
                } catch (error) {
                    // noop
                }
            });
        });
    });
</script>
