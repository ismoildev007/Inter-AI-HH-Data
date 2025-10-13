@extends('admin::components.layouts.master')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Telegram Channels</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Telegram Channels</li>
            </ul>
        </div>

    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @php($searchTerm = $search ?? request('q'))
    <div class="card stretch mt-4 ms-4 me-4">
        <div class="card-header align-items-center justify-content-between">
            <div class="card-title">
                <h6 class="mb-0">Channels</h6>
            </div>
            
    <form method="GET" class="d-flex justify-content-center flex-grow-1">
        <div class="input-group input-group-sm w-100" style="max-width: 400px;">
            <input type="search"
                   name="q"
                   value="{{ $searchTerm }}"
                   class="form-control flex-grow-1"
                   placeholder="Search channels (ID, username)">
            @if(!empty($searchTerm))
                <a href="{{ route('admin.telegram_channels.index') }}" class="btn btn-outline-secondary">
                    <i class="feather-x"></i>
                </a>
            @endif
            <button type="submit" class="btn btn-primary">
                <i class="feather-search"></i>
            </button>
        </div>
    </form>
            <div>
        <a href="{{ route('admin.telegram_channels.create') }}" class="btn btn-primary">
            <i class="feather-plus me-1"></i> Add Channel
        </a>
    </div>    
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            
                            <th class="text-muted text-center" style="width: 90px;">ID</th>
                            <th class="text-muted">Channel ID</th>
                            <th class="text-muted">Username</th>
                            <th class="text-muted">Role</th>
                            <th class="text-end text-muted" style="width: 1%; white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($channels as $ch)
                            <tr @if($ch->is_target) class="table-success" @endif>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-primary border border-primary px-3 py-2 fw-semibold">
                                        {{ (method_exists($channels, 'firstItem') ? ($channels->firstItem() ?? 1) : 1) + $loop->index }}
                                    </span>
                                  
                                </td>
                                <td>{{ $ch->channel_id }}</td>
                                <td>{{ $ch->username ?: '—' }}</td>
                                <td>
                                    @if($ch->is_target)
                                        <span class="badge bg-success">Target</span>
                                    @elseif($ch->is_source)
                                        <span class="badge bg-primary">Source</span>
                                    @else
                                        <span class="badge bg-light text-dark">—</span>
                                    @endif
                                </td>
<td class="text-end">
    <form action="{{ route('admin.telegram_channels.destroy', $ch->id) }}"
          method="POST"
          onsubmit="return confirm('Delete channel #{{ $ch->id }}?');"
          class="d-inline">
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
                                <td colspan="5" class="text-center text-muted py-4">No channels found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
