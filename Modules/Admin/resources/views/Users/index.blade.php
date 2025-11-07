@extends('admin::components.layouts.master')

@section('content')
    <style>
        .users-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 40px 44px;
            border-radius: 26px;
            background: #ffffff;
            color: #0f172a;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
        }

        .users-hero::before,
        .users-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .users-hero::before {
            width: 340px;
            height: 340px;
            background: rgba(59, 130, 246, 0.15);
            top: -160px;
            right: -100px;
        }

        .users-hero::after {
            width: 240px;
            height: 240px;
            background: rgba(99, 102, 241, 0.15);
            bottom: -120px;
            left: -120px;
        }

        .users-hero-content {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: flex-start;
            position: relative;
            z-index: 1;
        }

        .users-hero-left {
            flex: 1 1 320px;
        }

        .users-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: #eff6ff;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 18px;
            color: #1d4ed8;
        }

        .users-hero-left h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 3vw, 2.9rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .users-hero-left p {
            margin: 0;
            max-width: 420px;
            line-height: 1.6;
            color: #475569;
        }

        .users-stats {
            flex: 1 1 280px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .users-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 18px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .users-stat-card--link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .users-stat-card--link:hover {
            text-decoration: none;
        }

        .users-stat-card--compact .value {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .users-stat-card .label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .users-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.9rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .users-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .users-filter-card {
            margin: 1.5rem 1.5rem 1.5rem;
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(31, 51, 126, 0.12);
            overflow: hidden;
        }

        .users-filter-card .card-body {
            padding: 26px 32px;
        }

        .users-filter-header {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .users-filter-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .users-filter-header p {
            margin: 4px 0 0;
            color: #5e6a85;
            font-size: 0.9rem;
        }

        .users-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .users-search-form .input-group {
            flex: 1 1 320px;
            background: #f5f7ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .users-search-form .input-group-text {
            border: none;
            background: transparent;
            color: #4f6bff;
        }

        .users-search-form .form-control {
            border: none;
            background: transparent;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .users-search-form .form-control:focus {
            box-shadow: none;
        }

        .users-search-form .btn {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .users-search-form .clear-btn {
            color: #8a96b8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            text-decoration: none;
        }

        .users-search-form .clear-btn:hover {
            color: #1f3cfd;
        }

        .users-table-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 26px;
            box-shadow: 0 28px 58px rgba(21, 37, 97, 0.16);
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(236, 240, 255, 0.8));
            padding: 26px 30px 32px;
            overflow: visible;
        }

        .users-table-card .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .users-table-card .table thead th {
            padding: 0 20px 12px;
            background: transparent;
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .users-table-card .table tbody tr {
            cursor: pointer;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .users-table-card .table tbody tr:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-3px);
        }

        .users-table-card .table tbody td {
            padding: 18px 22px;
            border: none;
            vertical-align: middle;
        }

        .users-table-card .table tbody tr td:first-child {
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .users-table-card .table tbody tr td:last-child {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .users-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            color: #1f2f7a;
            box-shadow: 0 10px 20px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .users-user-block {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .users-user-block .avatar-image {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(32, 52, 122, 0.18);
        }

        .users-user-block .avatar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .users-user-block .name {
            font-weight: 600;
            font-size: 1rem;
            color: #172655;
        }

        .users-user-block .meta {
            font-size: 0.85rem;
            color: #6a7397;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .users-email {
            font-size: 0.95rem;
            font-weight: 500;
            color: #1a2c63;
        }

        .users-email a {
            color: inherit;
            text-decoration: none;
        }

        .users-email a:hover {
            text-decoration: underline;
            color: #2546ff;
        }

        .users-created {
            font-size: 0.9rem;
            color: #25335f;
        }

        .users-created span {
            display: block;
            font-size: 0.78rem;
            color: #8a94b8;
        }

        .users-action .btn {
            border-radius: 999px;
            padding-inline: 18px;
            font-weight: 600;
        }

        .users-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        @media (max-width: 991px) {
            .users-hero {
                margin: 1.5rem 1rem;
                border-radius: 22px;
                padding: 30px;
            }

            .users-filter-card,
            .users-table-card {
                margin: 1.5rem 1rem 2rem;
                padding: 24px 20px 26px;
            }

            .users-table-card .table {
                border-spacing: 0;
            }

            .users-table-card .table thead {
                display: none;
            }

            .users-table-card .table tbody tr {
                display: block;
                border-radius: 20px;
                margin-bottom: 18px;
                padding: 18px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                background: #ffffff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
                transform: none !important;
            }

            .users-table-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                border: none;
            }

            .users-table-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .users-table-card .table tbody td:first-child {
                display: block;
                margin-bottom: 12px;
            }

            .users-table-card .table tbody td:first-child::before {
                content: '';
            }

            .users-action {
                justify-content: flex-start;
            }

    </style>

    @php
        $searchTerm = $search ?? request('q');
        $isPaginator = $users instanceof \Illuminate\Contracts\Pagination\Paginator;
        $items = $isPaginator ? $users->getCollection() : collect($users);
        $totalUsers = $users instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $users->total() : $items->count();
        $pageCount = $items->count();
        $lastJoinedAt = $items->filter(fn ($user) => isset($user->created_at))->max('created_at');
        $lastJoinedDate = $lastJoinedAt ? $lastJoinedAt->format('M d, Y') : '—';
        $lastJoinedAgo = $lastJoinedAt ? $lastJoinedAt->diffForHumans() : null;
        $searchDisplay = $searchTerm ? \Illuminate\Support\Str::limit($searchTerm, 28) : 'None';
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Directory</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Users</li>
            </ul>
        </div>
    </div>

    <div class="users-hero">
        <div class="users-hero-content">
            <div class="users-hero-left">
                <span class="users-hero-badge">
                    <i class="feather-users"></i>
                    Team overview
                </span>
                <h1>Users directory</h1>
                <p>Browse every registered member, keep track of new sign-ups, and jump directly into detailed
                    profiles with a single click.</p>
            </div>
            <div class="users-stats">
                <div class="users-stat-card">
                    <span class="label">Total users</span>
                    <span class="value">{{ number_format($totalUsers) }}</span>
                    <span class="hint">Across the entire platform</span>
                </div>
              
                <div class="users-stat-card">
                    <span class="label">Last registration</span>
                    <span class="value">{{ $lastJoinedDate }}</span>
                    <span class="hint">{{ $lastJoinedAgo ? 'Joined ' . $lastJoinedAgo : 'No recent sign-ups' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card users-filter-card">
        <div class="card-body">
            <div class="users-filter-header">
                <div>
                    <h6>Search &amp; filter</h6>
                    <p class="mb-0">Find people by name, email address, or phone number.</p>
                </div>
                @if($searchTerm)
                    <div class="text-muted small">Showing results for “{{ $searchTerm }}”</div>
                @endif
            </div>
            <form method="GET" class="users-search-form">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="feather-search"></i>
                    </span>
                    <input
                        type="search"
                        name="q"
                        value="{{ $searchTerm }}"
                        class="form-control"
                        placeholder="Search users (name, email, phone)">
                    <button type="submit" class="btn btn-primary shadow-sm">
                        Search
                    </button>
                </div>
                @if(!empty($searchTerm))
                    <a href="{{ route('admin.users.index') }}" class="clear-btn">
                        <i class="feather-x-circle"></i>
                        Clear search
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card users-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted text-center" style="width: 120px;">Listing</th>
                        <th class="text-muted">User</th>
                        
                        <th class="text-muted">Joined</th>
                        <th class="text-muted">Actions</th>
                      
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr onclick="window.location.href='{{ route('admin.users.show', $u->id) }}'">
                            <td data-label="#" class="text-center align-middle">
                                <div class="users-index-pill">
                                    {{ (method_exists($users, 'firstItem') ? ($users->firstItem() ?? 1) : 1) + $loop->index }}
                                </div>
                            </td>
                            <td data-label="User">
                                <div class="users-user-block">
                                    <div class="avatar-image">
                                        <img src="/assets/images/avatar/ava.svg" class="img-fluid" alt="avatar">
                                    </div>
                                    <div>
                                        <div class="name">{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: '—' }}</div>
                                        <div class="meta">
                                            <i class="feather-phone"></i>
                                            +998{{ $u->phone ?: 'No phone on file' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                          
                            <td data-label="Joined">
                                <div class="users-created">
                                    {{ optional($u->created_at)->format('M d, Y') ?? '—' }}
                                    @if($u->created_at)
                                        <span>{{ $u->created_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Actions" class="users-action">
                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="event.stopPropagation(); return confirm('Foydalanuvchini o\'chirmoqchimisiz?');" onclick="event.stopPropagation();">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="feather-trash-2"></i> Delete
                                    </button>
                                </form>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center users-empty">
                                No users found. Try adjusting your filters or search keywords.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin::components.pagination', ['paginator' => $users])
    </div>
@endsection
