@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Vacancies by Category</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Vacancies by Category</li>
            </ul>
        </div>
    </div>

    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title"><h6 class="mb-0">All Categories</h6></div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-muted">#</th>
                            <th class="text-muted">Category</th>
                            <th class="text-end text-muted">Vacancies</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $i + 1 }}</td>
                                <td class="text-capitalize">{{ $row->category ?: 'other' }}</td>
                                <td class="text-end fw-bold">{{ $row->c }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

