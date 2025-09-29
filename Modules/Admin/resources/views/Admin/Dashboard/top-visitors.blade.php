@extends('admin::components.layouts.master')

@section('content')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Top Visitors</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Top Visitors</li>
        </ul>
    </div>
</div>

<div class="main-content">
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Users by Total Visits</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>User</th>
                            <th>Email</th>
                            <th class="text-end">Visits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = ($rows->currentPage() - 1) * $rows->perPage(); @endphp
                        @forelse($rows as $u)
                            <tr>
                                <td>{{ ++$i }}</td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <div class="avatar-text bg-primary text-white"><i class="feather-user"></i></div>
                                        <div><b>{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? 'User #'.$u->id) }}</b></div>
                                    </div>
                                </td>
                                <td>{{ $u->email }}</td>
                                <td class="text-end"><b>{{ $u->visits_count }}</b></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No visitor data found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection

