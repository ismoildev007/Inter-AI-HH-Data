@extends('admin::components.layouts.master')

@section('content')
    <style>
        .admin-check-wrapper {
            margin: 1.5rem 1.5rem 0;
        }

        .admin-check-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .admin-check-summary-card {
            border-radius: 18px;
            padding: 22px 26px;
            background: linear-gradient(135deg, rgba(241, 245, 255, 0.85), rgba(224, 231, 255, 0.9));
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 18px 42px rgba(79, 70, 229, 0.12);
        }

        .admin-check-summary-card .label {
            text-transform: uppercase;
            font-size: 0.76rem;
            letter-spacing: 0.14em;
            color: #475569;
            margin-bottom: 8px;
            display: inline-block;
        }

        .admin-check-summary-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
        }

        .admin-check-summary-card .hint {
            margin-top: 6px;
            font-size: 0.86rem;
            color: #64748b;
        }

        .admin-check-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 22px;
        }

        .admin-check-card {
            border-radius: 22px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 52px rgba(30, 41, 59, 0.16);
            display: flex;
            flex-direction: column;
            min-height: 520px;
        }

        .admin-check-card__header {
            padding: 24px 26px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        .admin-check-card__header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
            color: #0f172a;
        }

        .admin-check-card__header p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.86rem;
        }

        .admin-check-card__body {
            flex: 1 1 auto;
            padding: 0;
            overflow-y: auto;
            max-height: 540px;
        }

        .admin-check-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .admin-check-item {
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 18px;
            background: #ffffff;
            transition: background 0.2s ease, transform 0.18s ease;
        }

        .admin-check-item__main {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .admin-check-item:hover {
            background: rgba(248, 250, 252, 0.95);
            transform: translateY(-2px);
        }

        .admin-check-item--working {
            background: linear-gradient(135deg, rgba(236, 253, 245, 0.8), rgba(209, 250, 229, 0.6));
        }

        .admin-check-item--unsatisfied {
            background: linear-gradient(135deg, rgba(254, 242, 242, 0.8), rgba(254, 226, 226, 0.6));
        }

        .admin-check-item__title {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
        }

        .admin-check-item__meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 6px;
        }

        .admin-check-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            color: #1f2f7a;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            box-shadow: 0 10px 20px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .admin-check-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .admin-check-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .admin-check-chip--status {
            background: #eef2ff;
            color: #4338ca;
        }

        .admin-check-chip--status-working {
            background: rgba(4, 120, 87, 0.12);
            color: #047857;
        }

        .admin-check-chip--status-unsatisfied {
            background: rgba(248, 113, 113, 0.14);
            color: #b91c1c;
        }

        .admin-check-chip--verified {
            background: rgba(59, 130, 246, 0.16);
            color: #1d4ed8;
        }

        .admin-check-chip--unsatisfied {
            background: rgba(220, 38, 38, 0.18);
            color: #7f1d1d;
        }

        .admin-check-actions {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
        }

        .admin-check-empty {
            padding: 48px 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.95rem;
        }

        @media (max-width: 991px) {
            .admin-check-wrapper {
                margin: 1.5rem 1rem 0;
            }

            .admin-check-card {
                min-height: 0;
            }

            .admin-check-card__body {
                max-height: none;
            }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Verification</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="breadcrumb-item">Admin check</li>
            </ul>
        </div>
    </div>

    <div class="admin-check-wrapper">
        <div class="admin-check-summary">
            <div class="admin-check-summary-card">
                <span class="label">Total users</span>
                <div class="value">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="hint">All records in directory</div>
            </div>
            <div class="admin-check-summary-card">
                <span class="label">Working status</span>
                <div class="value">{{ number_format($stats['working'] ?? 0) }}</div>
                <div class="hint">Users requiring quick review</div>
            </div>
            <div class="admin-check-summary-card">
                <span class="label">Not working</span>
                <div class="value">{{ number_format($stats['notWorking'] ?? 0) }}</div>
                <div class="hint">Awaiting updates</div>
            </div>
            <div class="admin-check-summary-card">
                <span class="label">Admin checked</span>
                <div class="value">{{ number_format($stats['adminChecked'] ?? 0) }}</div>
                <div class="hint">Profiles cleared by admins</div>
            </div>
        </div>

        <div class="admin-check-columns">
            <div class="admin-check-card">
                <div class="admin-check-card__header">
                    <h5>All users</h5>
                    <p>Working profiles are pinned to the top for faster access.</p>
                </div>
                <div class="admin-check-card__body">
                    <ul class="admin-check-list">
                        @forelse($allUsers as $user)
                            @php
                                $status = (string) ($user->status ?? 'unknown');
                                $statusLower = strtolower($status);
                                $isWorking = $statusLower === 'working';
                                $isChecked = (bool) $user->admin_check_status;
                                $fullName = trim(collect([$user->first_name, $user->last_name])->filter()->implode(' '));
                            @endphp
                            <li class="admin-check-item {{ $isWorking ? 'admin-check-item--working' : '' }}">
                                <div class="admin-check-item__main">
                                    <span class="admin-check-pill">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="admin-check-item__title">
                                            {{ $fullName !== '' ? $fullName : ($user->email ?? 'User #' . $user->id) }}
                                        </div>
                                        <div class="admin-check-item__meta">
                                            {{ $user->email ?? '—' }} &bullet; ID: {{ $user->id }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="admin-check-chips">
                                        <span class="admin-check-chip admin-check-chip--status {{ $isWorking ? 'admin-check-chip--status-working' : '' }}">
                                            {{ \Illuminate\Support\Str::title($status !== '' ? $status : 'Unknown') }}
                                        </span>
                                        @if($isChecked)
                                            <span class="admin-check-chip admin-check-chip--verified">
                                                <i class="feather-check-circle"></i> Checked
                                            </span>
                                        @endif
                                    </div>
                                    <div class="admin-check-actions">
                                        <a href="{{ route('admin.users.admin_check.show', $user) }}" class="btn btn-sm btn-primary">
                                            Admin check
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="admin-check-empty">
                                No users available for review.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="admin-check-card">
                <div class="admin-check-card__header">
                    <h5>Working &amp; verified</h5>
                    <p>Status is “working” and admin check flag is confirmed.</p>
                </div>
                <div class="admin-check-card__body">
                    <ul class="admin-check-list">
                        @forelse($verifiedWorkingUsers as $user)
                            @php
                                $fullName = trim(collect([$user->first_name, $user->last_name])->filter()->implode(' '));
                            @endphp
                            <li class="admin-check-item admin-check-item--working">
                                <div class="admin-check-item__main">
                                    <span class="admin-check-pill">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="admin-check-item__title">
                                            {{ $fullName !== '' ? $fullName : ($user->email ?? 'User #' . $user->id) }}
                                        </div>
                                        <div class="admin-check-item__meta">
                                            {{ $user->email ?? '—' }} &bullet; ID: {{ $user->id }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="admin-check-chips">
                                        <span class="admin-check-chip admin-check-chip--status admin-check-chip--status-working">
                                            Working
                                        </span>
                                        <span class="admin-check-chip admin-check-chip--verified">
                                            <i class="feather-check-circle"></i> Checked
                                        </span>
                                    </div>
                                    <div class="admin-check-actions">
                                        <a href="{{ route('admin.users.admin_check.show', $user) }}" class="btn btn-sm btn-primary">
                                            Admin check
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="admin-check-empty">
                                No working users have been cleared by admins yet.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="admin-check-card">
                <div class="admin-check-card__header">
                    <h5>Checked but unsatisfied</h5>
                    <p>Admin review completed, profile marked as “not working”.</p>
                </div>
                <div class="admin-check-card__body">
                    <ul class="admin-check-list">
                        @forelse($checkedButNotWorkingUsers as $user)
                            @php
                                $fullName = trim(collect([$user->first_name, $user->last_name])->filter()->implode(' '));
                            @endphp
                            <li class="admin-check-item admin-check-item--unsatisfied">
                                <div class="admin-check-item__main">
                                    <span class="admin-check-pill">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="admin-check-item__title">
                                            {{ $fullName !== '' ? $fullName : ($user->email ?? 'User #' . $user->id) }}
                                        </div>
                                        <div class="admin-check-item__meta">
                                            {{ $user->email ?? '—' }} &bullet; ID: {{ $user->id }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="admin-check-chips">
                                        <span class="admin-check-chip admin-check-chip--status admin-check-chip--status-unsatisfied">
                                            Not working
                                        </span>
                                        <span class="admin-check-chip admin-check-chip--verified">
                                            <i class="feather-check-circle"></i> Checked
                                        </span>
                                        <span class="admin-check-chip admin-check-chip--unsatisfied">
                                            Qoniqarsiz
                                        </span>
                                    </div>
                                    <div class="admin-check-actions">
                                        <a href="{{ route('admin.users.admin_check.show', $user) }}" class="btn btn-sm btn-primary">
                                            Admin check
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="admin-check-empty">
                                No admin-reviewed users have been marked unsatisfactory yet.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
