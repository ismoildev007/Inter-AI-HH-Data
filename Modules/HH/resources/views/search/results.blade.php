@extends('hh::layouts.master')

@section('content')
    <div class="container">
        <h1>Search Results</h1>
        <p>Showing candidates for your request. Status: <strong>{{ $searchRequest->status }}</strong></p>

        <div class="card mb-4">
            <div class="card-header">Your Search Criteria</div>
            <div class="card-body">
                <h5>Filters:</h5>
                <ul>
                    @foreach($searchRequest->filters as $key => $value)
                        <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                    @endforeach
                </ul>
                <h5>Custom Requirements:</h5>
                <p class="text-muted">{{ $searchRequest->custom_requirements }}</p>
            </div>
        </div>

        @if($searchRequest->status === 'completed')
            @if($results->isEmpty())
                <div class="alert alert-warning">
                    No candidates found matching your criteria. Try broadening your search.
                </div>
            @else
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $result->match_percentage }}%;" aria-valuenow="{{ $result->match_percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $result->match_percentage }}%</div>
                                    </div>
                                </td>
                                <td>{{ $result->candidate->first_name }} {{ $result->candidate->last_name }}</td>
                                <td>{{ $result->candidate->specialization }}</td>
                                <td>{{ $result->candidate->experience }} years</td>
                                <td><a href="#" class="btn btn-sm btn-info">View Profile</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $results->links() }}
            @endif
        @elseif($searchRequest->status === 'processing')
            <div class="alert alert-info">
                <p>Your request is still being processed. Please check back later.</p>
            </div>
        @else
            <div class="alert alert-danger">
                <p>Something went wrong while processing your request. Please try again or contact support.</p>
            </div>
        @endif
    </div>
@endsection
