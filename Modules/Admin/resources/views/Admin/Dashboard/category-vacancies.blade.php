@extends('admin::components.layouts.master')

@section('content')
    @php
        $currentFilter = $filter ?? request('filter', 'all');
        if (!in_array($currentFilter, ['all', 'telegram', 'hh', 'archived'], true)) {
            $currentFilter = 'all';
        }
        $filterLabels = [
            'all' => 'All sources',
            'telegram' => 'Telegram',
            'hh' => 'HeadHunter',
            'archived' => 'Archived',
        ];
        $filterLabel = $filterLabels[$currentFilter] ?? ucfirst($currentFilter);
        $searchTerm = $search ?? request('q', '');
        $dateFilter = $dateFilter ?? ['from' => request('from', ''), 'to' => request('to', '')];
        $dateRangeFrom = $dateFilter['from'] ?? '';
        $dateRangeTo = $dateFilter['to'] ?? '';
        $rangeActive = ($dateRangeFrom !== '') || ($dateRangeTo !== '');
        $rangeSummary = '';
        if ($rangeActive) {
            if ($dateRangeFrom !== '' && $dateRangeTo !== '') {
                $rangeSummary = $dateRangeFrom . ' → ' . $dateRangeTo;
            } elseif ($dateRangeFrom !== '') {
                $rangeSummary = 'From ' . $dateRangeFrom;
            } else {
                $rangeSummary = 'Until ' . $dateRangeTo;
            }
        }
        $totalLabel = $rangeActive ? 'Vacancies in range' : 'All vacancies';
        $rangeHint = $rangeActive ? 'Across current filters' : 'Across pagination';
        $formRouteParams = array_filter([
            'category' => $categorySlug,
            'filter' => $currentFilter !== 'all' ? $currentFilter : null,
            'from' => $dateRangeFrom !== '' ? $dateRangeFrom : null,
            'to' => $dateRangeTo !== '' ? $dateRangeTo : null,
        ], fn ($value) => !is_null($value));
        $indexParams = array_filter([
            'filter' => $currentFilter !== 'all' ? $currentFilter : null,
            'q' => !empty($searchTerm) ? $searchTerm : null,
            'from' => $dateRangeFrom !== '' ? $dateRangeFrom : null,
            'to' => $dateRangeTo !== '' ? $dateRangeTo : null,
        ], fn ($value) => !is_null($value));
        $isPaginator = $vacancies instanceof \Illuminate\Contracts\Pagination\Paginator;
        $collection = $isPaginator ? $vacancies->getCollection() : collect($vacancies);
        $pageCount = $collection->count();
        $totalVacancies = $vacancies instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $vacancies->total() : $collection->count();
        $latestPublished = optional($collection->filter(fn ($vacancy) => $vacancy->created_at)->max('created_at'));
        $latestFormatted = $latestPublished ? $latestPublished->format('M d, Y') : '—';
    @endphp

    <style>
        .category-vacancies-hero {
            margin: 1.5rem 1.5rem 1.5rem;
            padding: 42px 46px;
            border-radius: 26px;
            background: #ffffff;
            color: #0f172a;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
        }

        .category-vacancies-hero::before,
        .category-vacancies-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .category-vacancies-hero::before {
            width: 320px;
            height: 320px;
            background: rgba(59, 130, 246, 0.18);
            top: -150px;
            right: -120px;
        }

        .category-vacancies-hero::after {
            width: 260px;
            height: 260px;
            background: rgba(96, 165, 250, 0.16);
            bottom: -140px;
            left: -140px;
        }

        .category-vacancies-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .category-vacancies-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            background: #eff6ff;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #1d4ed8;
        }

        .category-vacancies-hero__title {
            margin: 18px 0 0;
            font-size: clamp(2.1rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .category-vacancies-hero__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }

        .category-vacancies-hero__meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            background: #f8fafc;
            font-size: 0.9rem;
            font-weight: 500;
            color: #475569;
        }

        .category-vacancies-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        .category-vacancies-stat-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            backdrop-filter: none;
        }

        .category-vacancies-stat-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .category-vacancies-stat-card .value {
            display: block;
            margin-top: 6px;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
        }

        .category-vacancies-stat-card .hint {
            display: block;
            margin-top: 8px;
            font-size: 0.82rem;
            color: #94a3b8;
        }

        .category-vacancies-card {
            margin: 1.5rem 1.5rem 2rem;
            border: none;
            border-radius: 26px;
            box-shadow: 0 28px 58px rgba(21, 37, 97, 0.14);
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.8));
            overflow: visible;
        }

        .category-vacancies-card .card-header {
            padding: 26px 32px;
            border-bottom: 1px solid rgba(15, 35, 87, 0.06);
        }

        .category-vacancies-card .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .category-vacancies-card .search-form {
            display: flex;
            flex-grow: 1;
            max-width: 420px;
        }

        .category-vacancies-card .search-form .input-group {
            background: #f5f7ff;
            border-radius: 16px;
            padding: 4px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .category-vacancies-card .search-form input {
            border: none;
            background: transparent;
            padding: 10px 16px;
        }

        .category-vacancies-card .search-form input:focus { box-shadow: none; }

        .category-vacancies-card .table-responsive {
            padding: 0 32px 32px;
        }

        .category-vacancies-card .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0 14px;
        }

        .category-vacancies-card .table thead th {
            padding: 0 20px 12px;
            background: transparent;
            border: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #58618c;
        }

        .category-vacancies-card .table thead th.actions-cell,
        .category-vacancies-card .table tbody td.actions-cell {
            width: 1%;
            text-align: right;
            white-space: nowrap;
            padding-right: 12px;
        }

        .category-vacancies-card .table tbody td {
            padding: 18px 22px;
            border: none;
            vertical-align: middle;
        }

        .category-vacancies-list-row {
            cursor: pointer;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .category-vacancies-list-row:hover {
            border-color: rgba(59, 130, 246, 0.28);
            box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14);
            transform: translateY(-3px);
        }

        .category-vacancies-list-row td:first-child {
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .category-vacancies-list-row td:last-child {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .category-vacancies-index-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eff3ff, #d9e1ff);
            font-weight: 600;
            font-size: 0.95rem;
            color: #1f2f7a;
            box-shadow: 0 8px 18px rgba(31, 51, 126, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .category-vacancies-list-row td:first-child {
            width: 70px;
        }

        .category-vacancies-empty {
            padding: 42px 0;
            font-size: 0.95rem;
            color: #7d88ad;
        }

        .category-vacancies-pagination {
            padding: 20px 32px 34px;
            background: #fff;
            display: flex;
            justify-content: center;
        }

        .category-vacancies-pagination nav > ul,
        .category-vacancies-pagination nav > div > ul,
        .category-vacancies-pagination nav > div > div > ul,
        .category-vacancies-pagination nav .pagination {
            display: inline-flex;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
            box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
            align-items: center;
        }

        .category-vacancies-pagination nav > ul li a,
        .category-vacancies-pagination nav > ul li span,
        .category-vacancies-pagination nav > div > ul li a,
        .category-vacancies-pagination nav > div > ul li span,
        .category-vacancies-pagination nav > div > div > ul li a,
        .category-vacancies-pagination nav > div > div > ul li span,
        .category-vacancies-pagination nav .pagination li a,
        .category-vacancies-pagination nav .pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.95rem;
            color: #1a2f70;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .category-vacancies-pagination nav > ul li a:hover,
        .category-vacancies-pagination nav > div > ul li a:hover,
        .category-vacancies-pagination nav > div > div > ul li a:hover,
        .category-vacancies-pagination nav .pagination li a:hover {
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
            transform: translateY(-2px);
        }

        .category-vacancies-pagination nav > ul li span[aria-current="page"],
        .category-vacancies-pagination nav > div > ul li span[aria-current="page"],
        .category-vacancies-pagination nav > div > div > ul li span[aria-current="page"],
        .category-vacancies-pagination nav .pagination li span[aria-current="page"] {
            background: linear-gradient(135deg, #4a76ff, #265bff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
        }

        .category-vacancies-pagination nav > ul li:first-child a,
        .category-vacancies-pagination nav > ul li:last-child a,
        .category-vacancies-pagination nav > div > ul li:first-child a,
        .category-vacancies-pagination nav > div > ul li:last-child a,
        .category-vacancies-pagination nav > div > div > ul li:first-child a,
        .category-vacancies-pagination nav > div > div > ul li:last-child a,
        .category-vacancies-pagination nav .pagination li:first-child a,
        .category-vacancies-pagination nav .pagination li:last-child a {
            width: auto;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        @media (max-width: 991px) {
            .category-vacancies-hero {
                margin: 1.5rem 1rem;
                padding: 32px;
                border-radius: 24px;
            }

            .category-vacancies-card {
                margin: 1.5rem 1rem;
                padding-bottom: 26px;
            }

            .category-vacancies-card .table-responsive {
                padding: 0 20px 24px;
            }

            .category-vacancies-card .table {
                border-spacing: 0;
            }

            .category-vacancies-card .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .category-vacancies-card .search-form {
                max-width: 100%;
            }

            .category-vacancies-card .table thead {
                display: none;
            }

            .category-vacancies-card .table tbody tr {
                display: block;
                margin-bottom: 18px;
                border-radius: 20px;
                border: 1px solid rgba(226, 232, 240, 0.9);
                padding: 18px;
                background: #ffffff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
                transform: none !important;
            }

            .category-vacancies-card .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: 12px 18px;
                border: none;
                gap: 12px;
            }

            .category-vacancies-card .table tbody td:first-child,
            .category-vacancies-card .table tbody td:last-child {
                border-radius: 0;
            }

            .category-vacancies-card .table tbody td::before {
                content: attr(data-label);
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                color: #8a94b8;
                margin-right: 12px;
            }

            .category-vacancies-pagination nav > ul,
            .category-vacancies-pagination nav > div > ul,
            .category-vacancies-pagination nav > div > div > ul,
            .category-vacancies-pagination nav .pagination {
                gap: 6px;
                padding: 8px 10px;
            }

            .category-vacancies-pagination nav > ul li a,
            .category-vacancies-pagination nav > ul li span,
            .category-vacancies-pagination nav > div > ul li a,
            .category-vacancies-pagination nav > div > ul li span,
            .category-vacancies-pagination nav > div > div > ul li a,
            .category-vacancies-pagination nav > div > div > ul li span,
            .category-vacancies-pagination nav .pagination li a,
            .category-vacancies-pagination nav .pagination li span {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .category-vacancies-pagination nav > ul li:first-child a,
            .category-vacancies-pagination nav > ul li:last-child a,
            .category-vacancies-pagination nav > div > ul li:first-child a,
            .category-vacancies-pagination nav > div > ul li:last-child a,
            .category-vacancies-pagination nav > div > div > ul li:first-child a,
            .category-vacancies-pagination nav > div > div > ul li:last-child a,
            .category-vacancies-pagination nav .pagination li:first-child a,
            .category-vacancies-pagination nav .pagination li:last-child a {
                padding: 0 12px;
                font-size: 0.75rem;
            }
        }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Vacancies</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.vacancies.categories', $indexParams) }}">Categories</a></li>
                <li class="breadcrumb-item text-capitalize">{{ $category }}</li>
            </ul>
        </div>
    </div>

    <div class="category-vacancies-hero">
        <div class="category-vacancies-hero__content">
            <div>
                <span class="category-vacancies-hero__badge">
                    <i class="feather-briefcase"></i>
                    {{ $filterLabel }}
                </span>
                <h1 class="category-vacancies-hero__title text-capitalize">{{ $category }}</h1>
                <div class="category-vacancies-hero__meta">
                    <span class="category-vacancies-hero__meta-item"><i class="feather-hash"></i>ID {{ $categorySlug }}</span>
                    <span class="category-vacancies-hero__meta-item"><i class="feather-layers"></i>Total vacancies: {{ number_format($count) }}</span>
                    @if($rangeActive)
                        <span class="category-vacancies-hero__meta-item"><i class="feather-calendar"></i>{{ $rangeSummary }}</span>
                    @endif
                </div>
            </div>
            <div class="category-vacancies-stats">
                <div class="category-vacancies-stat-card">
                    <span class="label">Vacancies on this page</span>
                    <span class="value">{{ number_format($pageCount) }}</span>
                    <span class="hint">Current page size</span>
                </div>
                <div class="category-vacancies-stat-card">
                    <span class="label">{{ $totalLabel }}</span>
                    <span class="value">{{ number_format($totalVacancies) }}</span>
                    <span class="hint">{{ $rangeHint }}</span>
                </div>
                <div class="category-vacancies-stat-card">
                    <span class="label">Latest published</span>
                    <span class="value">{{ $latestFormatted }}</span>
                    <span class="hint">Most recent created</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card category-vacancies-card">
        <div class="card-header">
            <div class="filters">
                <div>
                    <h6 class="mb-1">Vacancies list</h6>
                    <span class="text-muted small">Explore titles within this category</span>
                </div>

            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-muted">ID</th>
                        
                        <th class="text-muted">Title</th>
                        <th class="text-muted">Created</th>
                        <th class="text-muted text-end actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $firstNumber = method_exists($vacancies, 'firstItem') ? ($vacancies->firstItem() ?? 1) : 1; @endphp
                    @forelse($vacancies as $index => $vacancy)
                        @php
                            $viewParams = array_filter([
                                'id' => $vacancy->id,
                                'filter' => $currentFilter !== 'all' ? $currentFilter : null,
                                'from' => $dateRangeFrom !== '' ? $dateRangeFrom : null,
                                'to' => $dateRangeTo !== '' ? $dateRangeTo : null,
                            ], fn ($value) => !is_null($value));
                        @endphp
                        <tr class="category-vacancies-list-row" onclick="window.location.href='{{ route('admin.vacancies.show', $viewParams) }}'">
                            <td class="text-center" data-label="#">
                                <div class="category-vacancies-index-pill">{{ $firstNumber + $index }}</div>
                            </td>
                            
                            <td class="fw-semibold text-dark" data-label="Title">
                                {{ $vacancy->title ?? '—' }}
                                @if(($vacancy->status ?? '') === \App\Models\Vacancy::STATUS_QUEUED)
                                    <span class="badge bg-warning text-dark ms-2" title="Queued for delivery">queued</span>
                                @endif
                            </td>
                            <td class="text-nowrap" data-label="Created">{{ optional($vacancy->created_at)->format('M d, Y') ?? '—' }}</td>
                            <td class="text-end actions-cell text-nowrap" data-label="Actions">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#vacancyCategoryModal"
                                        data-vacancy-id="{{ $vacancy->id }}"
                                        data-vacancy-title="{{ $vacancy->title ?? '' }}"
                                        data-vacancy-category="{{ $vacancy->category ?? '' }}"
                                        onclick="event.stopPropagation();">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center category-vacancies-empty">No vacancies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vacancies instanceof \Illuminate\Contracts\Pagination\Paginator || $vacancies instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="category-vacancies-pagination">
                {{ $vacancies->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="vacancyCategoryModal" tabindex="-1" aria-labelledby="vacancyCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" data-action-template="{{ route('admin.vacancies.update_category', ['vacancy' => '__VACANCY__']) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="vacancyCategoryModalLabel">Update vacancy category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3 text-muted small">Editing: <span class="fw-semibold" data-vacancy-title></span></p>
                        <div class="mb-3">
                            <label class="form-label" for="vacancy-category-select">Category</label>
                            <select class="form-select" id="vacancy-category-select" name="category" required>
                                <option value="" disabled selected>Select a category</option>
                                @foreach(($categoryOptions ?? []) as $label)
                                    <option value="{{ $label }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('vacancyCategoryModal');
    if (!modalEl) {
        return;
    }
    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }
    const form = modalEl.querySelector('form');
    const actionTemplate = form ? form.getAttribute('data-action-template') : '';
    const titleHolder = modalEl.querySelector('[data-vacancy-title]');
    const categorySelect = modalEl.querySelector('select[name="category"]');

    modalEl.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }
        const vacancyId = trigger.getAttribute('data-vacancy-id') || '';
        const vacancyTitle = trigger.getAttribute('data-vacancy-title') || '';
        const currentCategory = trigger.getAttribute('data-vacancy-category') || '';

        if (form && actionTemplate) {
            form.action = vacancyId ? actionTemplate.replace('__VACANCY__', vacancyId) : '#';
        }
        if (titleHolder) {
            titleHolder.textContent = vacancyTitle;
        }
        if (categorySelect) {
            categorySelect.value = currentCategory;
            if (!categorySelect.value) {
                categorySelect.selectedIndex = 0;
            }
        }
    });
});
</script>
@endpush
