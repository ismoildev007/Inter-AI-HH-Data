@php
    $navigationGroups = [
        [
            'label' => 'Overview',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'airplay',
                    'route' => 'admin.dashboard',
                    'match' => ['admin.dashboard', 'admin.visits.*'],
                    'hint' => 'Metrics & analytics',
                ],
            ],
        ],
        [
            'label' => 'People',
            'items' => [
                [
                    'label' => 'Users',
                    'icon' => 'users',
                    'route' => 'admin.users.index',
                    'match' => ['admin.users.*'],
                    'hint' => 'Directory & profiles',
                ],
                [
                    'label' => 'Resumes',
                    'icon' => 'file-text',
                    'route' => 'admin.resumes.index',
                    'match' => ['admin.resumes.*'],
                    'hint' => 'Talent archive',
                ],
                [
                    'label' => 'Applications',
                    'icon' => 'briefcase',
                    'route' => 'admin.applications.index',
                    'match' => ['admin.applications.*'],
                    'hint' => 'Recruiting pipeline',
                ],
            ],
        ],
        [
            'label' => 'Automation',
            'items' => [
                [
                    'label' => 'Telegram Channels',
                    'icon' => 'send',
                    'route' => 'admin.telegram_channels.index',
                    'match' => ['admin.telegram_channels.*'],
                    'hint' => 'Broadcast control',
                ],
                                [
                    'label' => 'All Vacancies',
                    'icon' => 'clipboard',
                    'route' => 'admin.vacancies.categories',
                    'match' => ['admin.vacancies.*'],
                    'hint' => 'Broadcast control',
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

        <div class="admin-sidebar__content">
            @foreach ($navigationGroups as $group)
                <div class="admin-sidebar__section">
                    <p class="admin-sidebar__section-label">{{ $group['label'] }}</p>
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
        padding: 18px 16px;
        gap: 22px;
    }
    .admin-sidebar__brand {
        padding: 12px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        box-shadow: none;
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
    .admin-sidebar__section {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .admin-sidebar__section-label {
        margin: 0;
        padding-inline: 4px;
        font-size: 0.68rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .admin-sidebar__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
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
        }
        .admin-sidebar__label {
            font-size: 0.88rem;
        }
        .admin-sidebar__hint {
            font-size: 0.74rem;
        }
    }
</style>
