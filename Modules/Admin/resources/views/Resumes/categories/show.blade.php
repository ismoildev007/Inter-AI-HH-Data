@extends('admin::components.layouts.master')

@section('content')
    <style>
        .resumes-hero { margin: 1.5rem 1.5rem 1.5rem; padding: 40px 44px; border-radius: 26px; background: #ffffff; color: #0f172a; position: relative; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06); }
        .resumes-hero::before { content: ''; position: absolute; width: 320px; height: 320px; background: rgba(59, 130, 246, 0.18); top: -140px; right: -110px; border-radius: 50%; opacity: 0.22; pointer-events: none; }
        .resumes-hero::after { content: ''; position: absolute; width: 260px; height: 260px; background: rgba(96, 165, 250, 0.16); bottom: -130px; left: -140px; border-radius: 50%; opacity: 0.22; pointer-events: none; }
        .resumes-hero-content { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: 32px; align-items: flex-start; }
        .resumes-hero-left { flex: 1 1 320px; }
        .resumes-hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 999px; background: #eff6ff; font-size: 0.78rem; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 18px; color: #1d4ed8; }
        .resumes-hero-left h1 { margin: 0 0 12px; font-size: clamp(2.0rem, 3vw, 2.6rem); font-weight: 700; letter-spacing: -0.01em; color: #0f172a; }
        .resumes-hero-left p { margin: 0; max-width: 520px; line-height: 1.6; color: #475569; }

        .resumes-table-card { margin: 1.5rem 1.5rem 2rem; border: none; border-radius: 26px; box-shadow: 0 28px 58px rgba(19, 48, 132, 0.16); background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.82)); padding: 26px 30px 32px; overflow: visible; }
        .resumes-table-card .table { margin: 0; border-collapse: separate; border-spacing: 0 14px; }
        .resumes-table-card .table thead th { padding: 0 20px 12px; background: transparent; border: none; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.12em; color: #58618c; }
        .resumes-table-card .table tbody tr { cursor: pointer; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.9); background: #ffffff; box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06); transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
        .resumes-table-card .table tbody tr:hover { border-color: rgba(59, 130, 246, 0.28); box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14); transform: translateY(-3px); }
        .resumes-table-card .table tbody td { padding: 18px 22px; border: none; vertical-align: middle; }
        .resumes-index-pill { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; background: linear-gradient(135deg, #eff3ff, #d9e1ff); border-radius: 14px; font-weight: 600; font-size: 1rem; color: #1f2f7a; box-shadow: 0 10px 20px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85); }
        .resumes-title { font-weight: 600; color: #172655; }
        .resumes-owner { display: flex; gap: 14px; align-items: center; }
        .resumes-owner .avatar-image { width: 48px; height: 48px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 24px rgba(32, 52, 122, 0.18); }
        .resumes-owner .avatar-image img { width: 100%; height: 100%; object-fit: cover; }
        .resumes-owner .name { font-weight: 600; font-size: 1rem; color: #172655; }
        .resumes-owner .meta { font-size: 0.85rem; color: #707a9f; }
        .resumes-created { font-size: 0.9rem; color: #25335f; }
        .resumes-created span { display: block; font-size: 0.78rem; color: #8a94b8; }
        .resumes-pagination { padding: 20px 32px 40px; border-top: 1px solid rgba(15, 35, 87, 0.06); background: #fff; display: flex; justify-content: center; }
        .resumes-pagination nav > ul, .resumes-pagination nav .pagination { display: inline-flex; gap: 12px; padding: 10px 16px; border-radius: 999px; background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88)); box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18); align-items: center; }

        @media (max-width: 991px) { .resumes-hero { margin: 1.5rem 1rem; padding: 32px; border-radius: 24px; } .resumes-table-card { margin: 1.5rem 1rem 2rem; } }
    </style>

    @php
        $isPaginator = $resumes instanceof \Illuminate\Contracts\Pagination\Paginator;
        $items = $isPaginator ? $resumes->getCollection() : collect($resumes);
        $pageCount = $items->count();
        $primaryCount = $items->filter(fn ($resume) => (bool) $resume->is_primary)->count();
        $uniqueAuthors = $items
            ->map(fn ($resume) => optional($resume->user)->id ?? $resume->user_id ?? null)
            ->filter()
            ->unique()
            ->count();
        $latestTimestamp = $items
            ->map(fn ($resume) => $resume->updated_at ?? $resume->created_at)
            ->filter()
            ->max();
        $latestDate = $latestTimestamp ? $latestTimestamp->format('M d, Y') : '—';
        $latestAgo = $latestTimestamp ? $latestTimestamp->diffForHumans() : null;
    @endphp

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Library</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.resumes.index') }}">Resumes</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.resumes.categories') }}">Categories</a></li>
                <li class="breadcrumb-item">{{ $category }}</li>
            </ul>
        </div>
    </div>

    <div class="resumes-hero">
        <div class="resumes-hero-content">
            <div class="resumes-hero-left">
                <span class="resumes-hero-badge">
                    <i class="feather-layers"></i>
                    Category
                </span>
                <h1>{{ $category }}</h1>
                <p>Listing all resumes under this category.</p>
                <!-- <div class="mt-3">
                    <a href="{{ route('admin.resumes.categories') }}" class="btn btn-outline-primary">
                        <i class="feather-arrow-left me-1"></i> Back to categories
                    </a>
                </div> -->
            </div>
            <div class="resumes-stats">
                <div class="resumes-stat-card">
                    <span class="label">Total in category</span>
                    <span class="value">{{ number_format($totalInCategory) }}</span>
                    <span class="hint">All pages</span>
                </div>
                <div class="resumes-stat-card">
                    <span class="label">Currently showing</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">On this page</span>
                </div>
                <div class="resumes-stat-card">
                    <span class="label">Primary resumes</span>
                    <span class="value">{{ number_format($primaryCount) }}</span>
                    <span class="hint">Marked as primary</span>
                </div>
                <div class="resumes-stat-card">
                    <span class="label">Unique authors</span>
                    <span class="value">{{ number_format($uniqueAuthors) }}</span>
                    <span class="hint">Represented on this page</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card resumes-table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted text-center" style="width: 120px;">Listing</th>
                        <th class="text-muted">Resume</th>
                        <th class="text-muted">Owner</th>
                        <th class="text-muted">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumes as $r)
                        <tr onclick="window.location.href='{{ route('admin.resumes.show', $r->id) }}'">
                            <td data-label="#" class="text-center align-middle">
                                <div class="resumes-index-pill">
                                    {{ (method_exists($resumes, 'firstItem') ? ($resumes->firstItem() ?? 1) : 1) + $loop->index }}
                                </div>
                            </td>
                            <td data-label="Resume">
                                <div class="resumes-title">
                                    {{ $r->title ?? '—' }}
                                    @if($r->is_primary)
                                        <span class="badge bg-light text-primary border border-primary">Primary</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Owner">
                                <div class="resumes-owner">
                                    <div class="avatar-image">
                                        <img src="/assets/images/avatar/ava.svg" class="img-fluid" alt="avatar">
                                    </div>
                                    <div>
                                        <div class="name">{{ trim((optional($r->user)->first_name ?? '').' '.(optional($r->user)->last_name ?? '')) ?: '—' }}</div>
                                        <div class="meta">{{ optional($r->user)->email ?? 'No email' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Created">
                                <div class="resumes-created">
                                    {{ optional($r->created_at)->format('M d, Y H:i') ?? '—' }}
                                    @if($r->created_at)
                                        <span>{{ $r->created_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 42px 0;">No resumes found for this category.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($resumes instanceof \Illuminate\Contracts\Pagination\Paginator || $resumes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="resumes-pagination">
                {{ $resumes->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection

