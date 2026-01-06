@extends('hh::layouts.master')

@section('content')
    <div class="container">
        <h1>Find Your Next Hire</h1>
        <p>Use our advanced search to find the perfect candidate from our database of 22 million professionals.</p>

        <form action="{{ route('hh.search.store') }}" method="POST">
            @csrf
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Standard Filters</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="specialization">Specialization</label>
                            <input type="text" name="filters[specialization]" class="form-control" placeholder="e.g., Software Engineer">
                        </div>
                        <div class="col-md-4">
                            <label for="experience_min">Min Experience (years)</label>
                            <input type="number" name="filters[experience_min]" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="experience_max">Max Experience (years)</label>
                            <input type="number" name="filters[experience_max]" class="form-control" min="0">
                        </div>
                    </div>
                    {{-- Add more filters for location, salary, etc. as needed --}}
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Custom Requirements (AI-Powered)</h4>
                </div>
                <div class="card-body">
                    <p>Describe your ideal candidate in detail. Our AI will analyze your text and find the best matches based on their skills, experience, and personal summary.</p>
                    <textarea name="custom_requirements" class="form-control" rows="8" placeholder="e.g., 'Looking for a senior PHP developer with strong experience in Laravel and Vue.js. Must have experience with building scalable microservices and working in an agile environment. Good communication skills are essential.'"></textarea>
                    @error('custom_requirements')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Search Candidates</button>
        </form>
    </div>
@endsection
