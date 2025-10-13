@extends('admin::components.layouts.master')

@section('content')
    <style>
        .telegram-hero {
            margin: 0 1.5rem 1.5rem;
            padding: 40px 44px;
            border-radius: 26px;
            background: linear-gradient(135deg, #0c4ffd, #5ba6ff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 62px rgba(10, 46, 139, 0.28);
        }

        .telegram-hero::before,
        .telegram-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
        }

        .telegram-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.35);
            top: -140px;
            right: -110px;
        }

        .telegram-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.2);
            bottom: -130px;
            left: -140px;
        }

        .telegram-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: flex-start;
        }

        .telegram-hero-left {
            flex: 1 1 320px;
        }

        .telegram-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .telegram-hero-left h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 3vw, 2.9rem);
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .telegram-hero-left p {
            margin: 0;
            max-width: 440px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.82);
        }

        .telegram-stats {
            flex: 1 1 300px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .telegram-stat-card {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
        }

        .telegram-stat-card .label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.72);
        }

        .telegram-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.9rem;
            font-weight: 700;
        }

        .telegram-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .telegram-alert {
            margin: 0 1.5rem 1rem;
            border-radius: 18px;
            padding: 14px 20px;
            background: rgba(46, 204, 113, 0.12);
            border: 1px solid rgba(46, 204, 113, 0.32);
            color: #1b8b54;
            font-weight: 500;
        }

        .telegram-filter-card {
            margin: 0 1.5rem 1.5rem;
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(15, 46, 122, 0.12);
            overflow: hidden;
        }

        .telegram-filter-card .card-body {
            padding: 26px 32px;
        }

        .telegram-filter-header {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .telegram-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .telegram-search-form .input-group {
            flex: 1 1 320px;
            background: #f1f4ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .telegram-search-form .input-group-text {
            border: none;
            background: transparent;
            color: #2a6bff;
        }

        .telegram-search-form .form-control {
            border: none;
            background: transparent;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .telegram-search-form .form-control:focus {
            box-shadow: none;
        }

        .telegram-search-form .btn {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .telegram-search-form .clear-btn {
            color: #8a96b8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            text-decoration: none;
        }

        .telegram-search-form .clear-btn:hover {
            color: #1f3cfd;
        }

        .telegram-add-btn {
            border-radius: 14px;
            padding: 10px 22px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .telegram-table-card {
            margin: 0 1.5rem 2rem;
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 50px rgba(14, 46, 128, 0.14);
            overflow: hidden;
        }

        .telegram-table-card .table {
            margin: 0;
        }

        .telegram-table-card .table thead th {
            padding: 18px 20px;
            background: rgba(12, 79, 253, 0.08);
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .telegram-table-card .table tbody td {
            padding: 20px;
            border-top: 1px solid rgba(15, 35, 87, 0.06);
            vertical-align: middle;
        }

        .telegram-table-card .table tbody tr:hover {
            background: rgba(80, 118, 255, 0.08);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .telegram-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            border-radius: 18px;
            font-weight: 700;
            font-size: 1.2rem;
            color: #1f2f7a;
            box-shadow: 0 14px 28px rgba(31, 51, 126, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .telegram-channel-id {
            font-weight: 600;
            color: #172655;
            font-size: 1rem;
        }

        .telegram-username a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2350ff;
            text-decoration: none;
            font-weight: 600;
        }

        .telegram-username a:hover {
            text-decoration: underline;
        }

        .telegram-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .telegram-role--target {
            background: rgba(46, 204, 113, 0.14);
            color: #1d9b5c;
        }

        .telegram-role--source {
            background: rgba(80, 118, 255, 0.16);
            color: #2f4bc8;
        }

        .telegram-role--generic {
            background: rgba(143, 155, 185, 0.16);
            color: #4a516a;
        }

        .telegram-action {
            display: flex;
            justify-content: flex-end;
        }

        .telegram-action form {
            margin: 0;
        }

        .telegram-action .btn {
            border-radius: 999px;
            padding-inline: 18px;
            font-weight: 600;
        }

        .telegram-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        @media (max-width: 991px) {
            .telegram-hero {
                margin-inline: 1rem;
                border-radius: 22px;
                padding: 30px;
            }

            .telegram-filter-card,
            .telegram-table-card {
                margin-inline: 1rem;
            }

            .telegram-table-card .table thead {
                display: none;
            }

            .telegram-table-card .table tbody tr {
                display: block;
                border-radius: 20px;
                margin-bottom: 18px;
                padding: 18px;
                border: 1px solid rgba(15, 35, 87, 0.08);
                background: #fff;
                transform: none !important;
            }

            .telegram-table-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border: none;
            }

            .telegram-table-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .telegram-table-card .table tbody td:first-child {
                display: block;
                margin-bottom: 12px;
            }

            .telegram-table-card .table tbody td:first-child::before {
                content: '';
            }

            .telegram-action {
                justify-content: flex-start;
            }
        }
    </style>

    @php
        $searchTerm = $search ?? request('q');
        $isPaginator = $channels instanceof \Illuminate\Contracts\Pagination\Paginator;
        $items = $isPaginator ? $channels->getCollection() : collect($channels);
        $totalChannels = $channels instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $channels->total() : $items->count();
        $pageCount = $items->count();
        $targetCount = $items->filter(fn ($channel) => (bool) $channel->is_target)->count();
        $sourceCount = $items->filter(fn ($channel) => (bool) $channel->is_source)->count();
        $otherCount = max($pageCount - $targetCount - $sourceCount, 0);
        $latestTimestamp = $items
            ->map(fn ($channel) => $channel->created_at ?? $channel->updated_at)
            ->filter()
            ->max();
        $latestDate = $latestTimestamp ? $latestTimestamp->format('M d, Y') : '—';
        $latestAgo = $latestTimestamp ? $latestTimestamp->diffForHumans() : null;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Broadcast</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Telegram Channels</li>
            </ul>
        </div>
    </div>

    <div class="telegram-hero">
        <div class="telegram-hero-content">
            <div class="telegram-hero-left">
                <span class="telegram-hero-badge">
                    <i class="feather-send"></i>
                    Channel control
                </span>
                <h1>Telegram distribution</h1>
                <p>Manage all source and target channels from a single dashboard, keep messaging organised, and
                    stay ready for the next campaign push.</p>
            </div>
            <div class="telegram-stats">
                <div class="telegram-stat-card">
                    <span class="label">Total channels</span>
                    <span class="value">{{ number_format($totalChannels) }}</span>
                    <span class="hint">Across the entire platform</span>
                </div>
                <div class="telegram-stat-card">
                    <span class="label">Currently showing</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">On this page</span>
                </div>
                <div class="telegram-stat-card">
                    <span class="label">Targets / Sources</span>
                    <span class="value">{{ number_format($targetCount) }} / {{ number_format($sourceCount) }}</span>
                    <span class="hint">This page breakdown</span>
                </div>
                <div class="telegram-stat-card">
                    <span class="label">Last added</span>
                    <span class="value">{{ $latestDate }}</span>
                    <span class="hint">{{ $latestAgo ? 'Created ' . $latestAgo : 'No recent additions' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="telegram-alert">
            <i class="feather-check-circle me-1"></i> {{ session('status') }}
        </div>
    @endif

    <div class="card telegram-filter-card">
        <div class="card-body">
            <div class="telegram-filter-header">
                <div>
                    <h6 class="mb-1">Search &amp; manage</h6>
                    <p class="mb-0">Look up channels by Telegram ID or username and adjust their roles instantly.</p>
                </div>
                <a href="{{ route('admin.telegram_channels.create') }}" class="btn btn-primary telegram-add-btn shadow-sm">
                    <i class="feather-plus"></i>
                    Add channel
                </a>
            </div>
            <form method="GET" class="telegram-search-form">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="feather-search"></i>
                    </span>
                    <input
                        type="search"
                        name="q"
                        value="{{ $searchTerm }}"
                        class="form-control"
                        placeholder="Search channels (ID, username)">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        Search
                    </button>
                </div>
                @if(!empty($searchTerm))
                    <a href="{{ route('admin.telegram_channels.index') }}" class="clear-btn">
                        <i class="feather-x-circle"></i>
                        Clear search
                    </a>
                @endif
                <div class="text-muted small w-100 mt-2">
                    Other channels on this page: <strong>{{ number_format($otherCount) }}</strong>
                </div>
            </form>
        </div>
    </div>

    <div class="card telegram-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted text-center" style="width: 120px;">Listing</th>
                        <th class="text-muted">Channel ID</th>
                        <th class="text-muted">Username</th>
                        <th class="text-muted">Role</th>
                        <th class="text-end text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($channels as $ch)
                        <tr>
                            <td data-label="#" class="text-center align-middle">
                                <div class="telegram-index-pill">
                                    {{ (method_exists($channels, 'firstItem') ? ($channels->firstItem() ?? 1) : 1) + $loop->index }}
                                </div>
                            </td>
                            <td data-label="Channel ID">
                                <div class="telegram-channel-id">
                                    {{ $ch->channel_id }}
                                </div>
                            </td>
                            <td data-label="Username">
                                <div class="telegram-username">
                                    @if($ch->username)
                                        <a href="https://t.me/{{ ltrim($ch->username, '@') }}" target="_blank" rel="noopener">
                                            <i class="feather-external-link"></i>
                                            {{ '@' . ltrim($ch->username, '@') }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Role">
                                @if($ch->is_target)
                                    <span class="telegram-role telegram-role--target">
                                        <i class="feather-target"></i>
                                        Target
                                    </span>
                                @elseif($ch->is_source)
                                    <span class="telegram-role telegram-role--source">
                                        <i class="feather-upload-cloud"></i>
                                        Source
                                    </span>
                                @else
                                    <span class="telegram-role telegram-role--generic">
                                        <i class="feather-minus-circle"></i>
                                        Unassigned
                                    </span>
                                @endif
                            </td>
                            <td data-label="Actions" class="telegram-action">
                                <form action="{{ route('admin.telegram_channels.destroy', $ch->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete channel #{{ $ch->id }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger shadow-sm">
                                        <i class="feather-trash-2 me-1"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center telegram-empty">
                                No channels found. Try adjusting your filters or search keywords.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
