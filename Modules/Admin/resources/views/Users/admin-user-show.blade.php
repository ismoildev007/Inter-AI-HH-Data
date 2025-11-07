@extends('admin::components.layouts.master')

@section('content')
    <style>
        .user-profile-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 44px 48px;
            border-radius: 28px;
            background: linear-gradient(135deg, #1f3cfd, #5f82ff);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 28px 68px rgba(25, 49, 160, 0.28);
        }

        .user-profile-hero::before,
        .user-profile-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
        }

        .user-profile-hero::before {
            width: 340px;
            height: 340px;
            background: rgba(255, 255, 255, 0.4);
            top: -150px;
            right: -120px;
        }

        .user-profile-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.24);
            bottom: -130px;
            left: -140px;
        }

        .user-profile-hero__content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: center;
        }

        .user-profile-hero__avatar {
            width: 120px;
            height: 120px;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 20px 48px rgba(17, 35, 102, 0.32);
            border: 3px solid rgba(255, 255, 255, 0.35);
        }

        .user-profile-hero__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-profile-hero__main {
            flex: 1 1 320px;
            min-width: 280px;
        }

        .user-profile-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .user-profile-hero__name {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 3.2vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .user-profile-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 24px;
        }

        .user-profile-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .user-profile-stats {
            flex: 1 1 260px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .user-profile-stat-card {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
        }

        .user-profile-stat-card span.label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255, 255, 255, 0.74);
        }

        .user-profile-stat-card span.value {
            display: block;
            margin-top: 6px;
            font-size: 1.9rem;
            font-weight: 700;
        }

        .user-profile-stat-card span.hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .user-profile-sections {
            margin: 1.5rem 1.5rem 2rem;
        }

        .user-profile-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(21, 37, 97, 0.12);
            overflow: hidden;
        }

        .user-profile-card .card-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .user-summary-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            padding: 24px 28px;
        }

        .user-summary-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 14px 18px;
            border-radius: 18px;
            background: #f4f6ff;
            border: 1px solid rgba(82, 97, 172, 0.12);
        }

        .user-summary-item .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8a94b8;
        }

        .user-summary-item .value {
            font-size: 1rem;
            font-weight: 600;
            color: #172655;
        }

        .user-summary-item .value a {
            color: inherit;
            text-decoration: none;
        }

        .user-summary-item .value a:hover {
            text-decoration: underline;
            color: #2140ff;
        }

        .user-summary-action {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px solid transparent;
            background: #f8fafc;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .user-summary-action--warn {
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.9), rgba(253, 230, 138, 0.85));
            border-color: rgba(217, 119, 6, 0.35);
        }

        .user-summary-action--success {
            background: linear-gradient(135deg, rgba(187, 247, 208, 0.9), rgba(134, 239, 172, 0.85));
            border-color: rgba(22, 163, 74, 0.35);
        }

        .user-summary-action--disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .user-summary-action__title {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .user-summary-action__hint {
            font-size: 0.85rem;
            color: #374151;
        }

        .user-summary-action__button {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 999px;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.8rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            color: #1f2937;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 18px rgba(31, 41, 55, 0.15);
        }

        .user-summary-action__button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        .user-summary-action__button:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(31, 41, 55, 0.2);
        }

        .user-billing-card .card-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .billing-overview {
            padding: 22px 28px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            background: #f4f6ff;
            border-bottom: 1px solid rgba(82, 97, 172, 0.12);
        }

        .billing-stat-card {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 16px 18px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(82, 97, 172, 0.12);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
        }

        .billing-stat-card span.label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8a94b8;
        }

        .billing-stat-card span.value {
            font-weight: 700;
            font-size: 1.35rem;
            color: #172655;
        }

        .billing-stat-card span.hint {
            font-size: 0.82rem;
            color: #99a8d3;
        }

        .billing-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 0;
        }

        .billing-section {
            padding: 24px 28px;
            border-right: 1px solid rgba(226, 232, 240, 0.7);
        }

        .billing-section:last-child {
            border-right: none;
        }

        .billing-section__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .billing-section__header h6 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .user-subscription-table,
        .user-transaction-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .user-subscription-table tbody tr,
        .user-transaction-table tbody tr {
            background: #f8f9ff;
            border-radius: 14px;
            overflow: hidden;
        }

        .user-subscription-table tbody tr td,
        .user-transaction-table tbody tr td {
            padding: 12px 16px;
            border-top: 1px solid rgba(226, 232, 240, 0.6);
        }

        .user-subscription-table tbody tr td:first-child,
        .user-transaction-table tbody tr td:first-child {
            border-left: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 12px 0 0 12px;
        }

        .user-subscription-table tbody tr td:last-child,
        .user-transaction-table tbody tr td:last-child {
            border-right: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 0 12px 12px 0;
        }

        .subscription-status-pill,
        .transaction-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .subscription-status-pill.active { background: rgba(20, 184, 166, 0.18); color: #047857; }
        .subscription-status-pill.pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
        .subscription-status-pill.expired { background: rgba(148, 163, 184, 0.2); color: #4b5563; }
        .transaction-status-pill.success { background: rgba(20, 184, 166, 0.18); color: #047857; }
        .transaction-status-pill.failed { background: rgba(239, 68, 68, 0.22); color: #b91c1c; }
        .transaction-status-pill.pending { background: rgba(251, 191, 36, 0.2); color: #b45309; }
        .transaction-status-pill.cancelled { background: rgba(148, 163, 184, 0.22); color: #475569; }

        .billing-empty {
            padding: 18px;
            border-radius: 14px;
            background: #f8f9ff;
            border: 1px dashed rgba(148, 163, 184, 0.5);
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 991px) {
            .user-profile-hero {
                margin: 1.5rem 1rem;
                padding: 32px;
                border-radius: 24px;
            }

            .user-profile-sections {
                margin: 1.5rem 1rem 2rem;
            }

            .user-summary-item {
                padding: 12px 14px;
            }

            .billing-sections {
                grid-template-columns: 1fr;
                border-top: 1px solid rgba(226, 232, 240, 0.7);
            }

            .billing-section {
                border-right: none;
                border-bottom: 1px solid rgba(226, 232, 240, 0.7);
            }

            .billing-section:last-child {
                border-bottom: none;
            }
        }
    </style>

    @php
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '—';
        $roleName = $user->role->name ?? 'Member';
        $email = $user->email ?? '—';
        $phone = $user->phone ?? null;
        $joinedAt = optional($user->created_at);
        $joinedFormatted = $joinedAt ? $joinedAt->format('M d, Y H:i') : '—';
        $joinedAgo = $joinedAt ? $joinedAt->diffForHumans() : null;
        $resumeCollection = $user->resumes ?? collect();
        $resumeCount = $resumeCollection->count();
        $primaryResumeCount = $resumeCollection->filter(fn ($resume) => (bool) $resume->is_primary)->count();
        $primaryResume = $resumeCollection->first();
        $latestResume = $resumeCollection
            ->map(fn ($resume) => $resume->updated_at ?? $resume->created_at)
            ->filter()
            ->max();
        $latestResumeFormatted = $latestResume ? $latestResume->format('M d, Y H:i') : '—';
        $latestResumeAgo = $latestResume ? $latestResume->diffForHumans() : null;
        $matchedVacancyCount = $matchedVacancyCount ?? 0;
        $statusValue = mb_strtolower((string) ($user->status ?? 'unknown'), 'UTF-8');
        $isWorkingStatus = $statusValue === 'working';
        $isAdminChecked = (bool) $user->admin_check_status;
        $yellowDisabled = !$isWorkingStatus;
        $greenDisabled = !$isWorkingStatus || $isAdminChecked;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Verification</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.admin_check') }}">Admin check</a></li>
                <li class="breadcrumb-item">{{ $user->id }}</li>
            </ul>
        </div>
    </div>

    <div class="user-profile-hero">
        <div class="user-profile-hero__content">
            <div class="user-profile-hero__avatar">
                <img src="{{ $user->avatar_path ? asset($user->avatar_path) : asset('assets/images/avatar/ava.svg') }}" alt="{{ $fullName }}">
            </div>
            <div class="user-profile-hero__main">
                <span class="user-profile-hero__badge">
                    <i class="feather-user"></i>
                    {{ strtoupper($roleName) }}
                </span>
                <h1 class="user-profile-hero__name">{{ $fullName }}</h1>
                <div class="user-profile-hero__meta">
                    <!-- <div class="user-profile-hero__meta-item">
                        <i class="feather-mail"></i>
                        {{ $email }}
                    </div> -->
                    <div class="user-profile-hero__meta-item">
                        <i class="feather"></i>
                        User ID: {{ $user->id }}
                    </div>
                    @if($phone)
                        <div class="user-profile-hero__meta-item">
                            <i class="feather-phone"></i>
                            +998{{ $phone }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="user-profile-stats">
                <div class="user-profile-stat-card">
                    <span class="label">Joined</span>
                    <span class="value">{{ $joinedFormatted }}</span>
                    <span class="hint">{{ $joinedAgo ? 'Active since ' . $joinedAgo : '—' }}</span>
                </div>

                <!-- <div class="user-profile-stat-card">
                    <span class="label">Last resume update</span>
                    <span class="value">{{ $latestResumeFormatted }}</span>
                    <span class="hint">{{ $latestResumeAgo ? 'Updated ' . $latestResumeAgo : 'No resumes yet' }}</span>
                </div> -->

                <!-- <div class="user-profile-stat-card">
                    <span class="label">Matched vacancies</span>
                    <span class="value">{{ $matchedVacancyCount }}</span>
                    <div class="hint">
                        @if($matchedVacancyCount > 0)
                            <a href="{{ route('admin.users.vacancies.index', $user->id) }}" class="btn btn-sm btn-primary shadow-sm">
                                <i class="feather-external-link me-1"></i> View matches
                            </a>
                        @else
                            No matches yet
                        @endif
                    </div>
                </div> -->
            </div>
        </div>
    </div>

    <div class="user-profile-sections">
        <div class="user-profile-card card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Account Summary</h6>
            </div>
            <div class="user-summary-grid">
                <div class="user-summary-item">
                    <span class="label">Full name</span>
                    <span class="value">{{ $fullName }}</span>
                </div>
                <!-- <div class="user-summary-item">
                    <span class="label">Email</span>
                    <span class="value">
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </span>
                </div> -->
                <div class="user-summary-item">
                    <span class="label">Phone</span>
                    <span class="value">
                        @if($phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">+998{{ $phone }}</a>
                        @else
                            <span class="text-muted">Not provided</span>
                        @endif
                    </span>
                </div>
                <!-- <div class="user-summary-item">
                    <span class="label">Role</span>
                    <span class="value">{{ ucfirst($roleName) }}</span>
                </div> -->
                <!-- <div class="user-summary-item">
                    <span class="label">Created at</span>
                    <span class="value">{{ $joinedFormatted }}</span>
                </div> -->
                <!-- <div class="user-summary-item">
                    <span class="label">Last activity</span>
                    <span class="value">{{ optional($user->updated_at)->format('M d, Y H:i') ?? '—' }}</span>
                </div> -->
                <div class="user-summary-item">
                    <span class="label">Matched vacancies</span>
                    <div class="value d-flex flex-column gap-2">
                        @if($matchedVacancyCount > 0)
                            <span>{{ $matchedVacancyCount }} vacancies linked to this user</span>
                            <span class="text-muted small">View detailed matches via the vacancies page</span>
                            <div>
                                <a href="{{ route('admin.users.vacancies.index', $user->id) }}" class="btn btn-sm btn-primary shadow-sm">
                                    <i class="feather-briefcase me-1"></i> View matched vacancies
                                </a>
                            </div>
                        @else
                            <span class="text-muted">No matches yet</span>
                        @endif
                    </div>
                </div>
                <div class="user-summary-item">
                    <span class="label">Resume</span>
                    <div class="value d-flex flex-column gap-2">
                        @if($primaryResume)
                            <span>{{ $primaryResume->title ?? 'Untitled resume' }}</span>
                            <span class="text-muted small">
                                Created {{ optional($primaryResume->created_at)->format('M d, Y H:i') ?? '—' }}
                            </span>
                            <div>
                                <a href="{{ route('admin.resumes.show', $primaryResume->id) }}" class="btn btn-sm btn-primary shadow-sm">
                                    <i class="feather-eye me-1"></i> View resume
                                </a>
                            </div>
                        @else
                            <span class="text-muted">No resume uploaded</span>
                        @endif
                    </div>
                </div>

                <div class="user-summary-action user-summary-action--warn {{ $yellowDisabled ? 'user-summary-action--disabled' : '' }}">
                    <div class="user-summary-action__title">Ish holatini o‘zgartirish</div>
                    <div class="user-summary-action__hint">
                        {{ $isWorkingStatus ? 'Working holatidagi foydalanuvchini “not working”ga o‘tkazish va qaytarish.' : 'Holat allaqachon “not working”.' }}
                    </div>
                    <button
                        type="button"
                        class="user-summary-action__button"
                        data-bs-toggle="modal"
                        data-bs-target="#markNotWorkingModal"
                        {{ $yellowDisabled ? 'disabled' : '' }}
                    >
                        <i class="feather-edit-2"></i>
                        Qaytarish
                    </button>
                </div>

                <div class="user-summary-action user-summary-action--success {{ $greenDisabled ? 'user-summary-action--disabled' : '' }}">
                    <div class="user-summary-action__title">Admin tekshiruvi</div>
                    <div class="user-summary-action__hint">
                        @if(!$isWorkingStatus)
                            Status working bo‘lganda tasdiqlash mumkin.
                        @elseif($isAdminChecked)
                            Bu foydalanuvchi allaqachon tasdiqlangan.
                        @else
                            Working holatidagi foydalanuvchini admin tekshiruvdan o‘tkazish va tasdiqlash.
                        @endif
                    </div>
                    <button
                        type="button"
                        class="user-summary-action__button"
                        data-bs-toggle="modal"
                        data-bs-target="#verifyUserModal"
                        {{ $greenDisabled ? 'disabled' : '' }}
                    >
                        <i class="feather-check"></i>
                        Tasdiqlash
                    </button>
                </div>
            </div>
        </div>

        <div class="user-profile-card card user-billing-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0">Billing overview</h6>
                    <span class="text-muted small">Plans, subscriptions, and payment activity</span>
                </div>
            </div>
            <div class="billing-overview">
                <div class="billing-stat-card">
                    <span class="label">Subscriptions</span>
                    <span class="value">{{ $subscriptionStats['total'] ?? 0 }}</span>
                    <span class="hint">{{ ($subscriptionStats['active'] ?? 0) }} active &middot; {{ ($subscriptionStats['pending'] ?? 0) }} pending</span>
                </div>
                <div class="billing-stat-card">
                    <span class="label">Active plans</span>
                    <span class="value">{{ $subscriptionStats['active'] ?? 0 }}</span>
                    <span class="hint">{{ $primaryResumeCount }} primary resumes</span>
                </div>
                <div class="billing-stat-card">
                    <span class="label">Remaining credits</span>
                    <span class="value">{{ $subscriptionStats['remainingCredits'] ?? 0 }}</span>
                    <span class="hint">Across all subscriptions</span>
                </div>
                <div class="billing-stat-card">
                    <span class="label">Transactions</span>
                    <span class="value">{{ $transactionStats['totalCount'] ?? 0 }}</span>
                    <span class="hint">{{ ($transactionStats['successCount'] ?? 0) }} successful payments</span>
                </div>
            </div>
            <div class="billing-sections">
                <div class="billing-section">
                    <div class="billing-section__header">
                        <h6>Subscription history</h6>
                        <a href="{{ route('admin.users.subscriptions.index', $user->id) }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                    @if(($subscriptions ?? collect())->isEmpty())
                        <div class="billing-empty">
                            This user has no subscriptions yet.
                        </div>
                    @else
                        <table class="user-subscription-table">
                            <tbody>
                            @foreach($subscriptions->take(5) as $subscription)
                                @php
                                    $status = strtolower($subscription->status ?? 'unknown');
                                    $statusClass = match ($status) {
                                        'active' => 'active',
                                        'pending' => 'pending',
                                        'expired' => 'expired',
                                        default => 'pending',
                                    };
                                    $planName = $subscription->plan->name ?? '—';
                                @endphp
                                <tr>
                                    <td style="width: 40%;">
                                        <div class="fw-semibold">{{ $planName }}</div>
                                        <div class="text-muted small">{{ $subscription->id }}</div>
                                    </td>
                                    <td style="width: 30%;">
                                        <span class="subscription-status-pill {{ $statusClass }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td style="width: 30%;">
                                        <div>
                                            <div class="text-muted small">Started</div>
                                            <div class="fw-semibold">{{ optional($subscription->starts_at)->format('M d, Y') ?? '—' }}</div>
                                        </div>
                                    </td>
                                    <td style="width: 30%;">
                                        <div>
                                            <div class="text-muted small">Ends</div>
                                            <div class="fw-semibold">{{ optional($subscription->ends_at)->format('M d, Y') ?? '—' }}</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                <div class="billing-section">
                    <div class="billing-section__header">
                        <h6>Transaction summary</h6>
                        <a href="{{ route('admin.users.transactions.index', $user->id) }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Total volume</span>
                            <span class="fw-semibold">{{ number_format((float) ($transactionStats['totalVolume'] ?? 0), 2, '.', ' ') }} UZS</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Successful</span>
                            <span class="fw-semibold text-success">{{ number_format((float) ($transactionStats['successVolume'] ?? 0), 2, '.', ' ') }} UZS</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Pending</span>
                            <span class="fw-semibold text-warning">{{ number_format((float) ($transactionStats['pendingCount'] ?? 0), 0, '.', ' ') }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Failed</span>
                            <span class="fw-semibold text-danger">{{ number_format((float) ($transactionStats['failedCount'] ?? 0), 0, '.', ' ') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-profile-card card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0">Admin check history</h6>
                    <span class="text-muted small">Recent approvals and rejections with notes</span>
                </div>
            </div>
            <div class="card-body">
                @php $notes = ($adminCheckNotes ?? collect()); @endphp
                @if($notes->isEmpty())
                    <div class="text-muted">No admin check notes recorded yet.</div>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($notes as $log)
                            @php
                                $isVerify = ($log->action ?? '') === 'verify';
                                $pillClass = $isVerify ? 'bg-success text-white' : 'bg-warning text-dark';
                                $label = $isVerify ? 'Verified' : 'Not working';
                                $adminName = optional($log->admin)->first_name || optional($log->admin)->last_name
                                    ? trim((optional($log->admin)->first_name ?? '') . ' ' . (optional($log->admin)->last_name ?? ''))
                                    : (optional($log->admin)->email ?? 'Admin');
                            @endphp
                            <div class="p-3 rounded" style="background:#f8f9ff;border:1px solid rgba(82,97,172,0.12);">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $pillClass }}">{{ $label }}</span>
                                        <span class="text-muted small">by {{ $adminName }}</span>
                                    </div>
                                    <div class="text-muted small">
                                        {{ optional($log->created_at)->format('M d, Y H:i') ?? '—' }}
                                        <span class="ms-1">{{ optional($log->created_at)->diffForHumans() }}</span>
                                    </div>
                                </div>
                                @if($log->note)
                                    <div class="text-dark">{{ $log->note }}</div>
                                @else
                                    <div class="text-muted fst-italic">No note provided.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- <div class="user-profile-card card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Recent transactions</h6>
                <a href="{{ route('admin.users.transactions.index', $user->id) }}" class="btn btn-sm btn-outline-primary">
                    View all
                </a>
            </div>
            <div class="card-body p-0">
                @if(($recentTransactions ?? collect())->isEmpty())
                    <div class="p-4 text-center text-muted">
                        No transactions recorded yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%;">Transaction</th>
                                    <th style="width: 20%;">Status</th>
                                    <th style="width: 30%;">Amount</th>
                                    <th style="width: 20%;">Created at</th>
                                    <th style="width: 20%;">Plan</th>
                                    <th style="width: 80px;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($recentTransactions as $transaction)
                                @php
                                    $txStatus = strtolower($transaction->payment_status ?? 'unknown');
                                    $txStatus = in_array($txStatus, ['success', 'failed', 'pending', 'cancelled'], true) ? $txStatus : 'pending';
                                    $linkedPlan = optional($transaction->subscription)->plan->name ?? '—';
                                @endphp
                                <tr>
                                    <td style="width: 30%;">
                                        <div class="fw-semibold">#{{ $transaction->id }}</div>
                                    </td>
                                    <td style="width: 20%;">
                                        <span class="transaction-status-pill {{ $txStatus }}">
                                            {{ ucfirst($transaction->payment_status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td style="width: 30%;">
                                        <div class="fw-semibold">{{ number_format((float) $transaction->amount, 2, '.', ' ') }} {{ strtoupper($transaction->currency ?? 'UZS') }}</div>
                                        <div class="text-muted small">{{ ucfirst($transaction->payment_method ?? '—') }}</div>
                                    </td>
                                    <td style="width: 20%;">
                                        <div>{{ optional($transaction->create_time)->format('M d, Y • H:i') ?? '—' }}</div>
                                        <div class="text-muted small">{{ optional($transaction->create_time)->diffForHumans() }}</div>
                                    </td>
                                    <td style="width: 20%;">
                                        <div class="text-muted small">Plan</div>
                                        <div class="fw-semibold">{{ $linkedPlan }}</div>
                                    </td>
                                    <td class="text-end" style="width: 80px;">
                                        <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div> -->
    </div>
@endsection

@push('scripts')
<script>
    // Ensure modals are appended to body (similar to other modals in the admin UI)
    document.addEventListener('DOMContentLoaded', function () {
        ['markNotWorkingModal', 'verifyUserModal'].forEach(function(id){
            const el = document.getElementById(id);
            if (el && el.parentNode !== document.body) {
                document.body.appendChild(el);
            }
        });
    });
</script>
@endpush

<!-- Mark Not Working Modal -->
<div class="modal fade" id="markNotWorkingModal" tabindex="-1" aria-labelledby="markNotWorkingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.admin_check.mark_not_working', $user) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="markNotWorkingModalLabel">Holatni yangilash (Not working)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="mark-not-working-note">Description</label>
                        <textarea class="form-control" id="mark-not-working-note" name="note" rows="4" placeholder="Qisqacha izoh kiriting" required></textarea>
                    </div>
                    <p class="text-muted small mb-0">Bu izoh tarix sifatida saqlanadi va o‘chirilmaydi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Save & Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verify User Modal -->
<div class="modal fade" id="verifyUserModal" tabindex="-1" aria-labelledby="verifyUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.admin_check.verify', $user) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyUserModalLabel">Admin tekshiruvi (Tasdiqlash)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="verify-user-note">Description</label>
                        <textarea class="form-control" id="verify-user-note" name="note" rows="4" placeholder="Qisqacha izoh kiriting" required></textarea>
                    </div>
                    <p class="text-muted small mb-0">Bu izoh tarix sifatida saqlanadi va o‘chirilmaydi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save & Verify</button>
                </div>
            </form>
        </div>
    </div>
</div>
