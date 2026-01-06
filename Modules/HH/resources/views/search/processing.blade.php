@extends('hh::layouts.master')

@section('content')
    <div class="container text-center">
        <div class="py-5">
            <h2>Thank You for Your Request!</h2>
            <p class="lead">We have started processing your search for the ideal candidate. This process involves advanced analysis and may take some time.</p>
            <hr class="my-4">
            <h4>What Happens Next?</h4>
            <p>To ensure the security and privacy of your search results, we will deliver them directly to your personal account dashboard.</p>
            <p>Due to the high volume of requests, this helps us prevent mix-ups and ensures you receive the correct list of candidates tailored to your specific needs.</p>

            @guest
                <div class="mt-5">
                    <a href="{{ route('register') }}" class="btn btn-success btn-lg">Create a Free Account to View Your Results</a>
                    <p class="mt-3">Already have an account? <a href="{{ route('login') }}">Log in here</a>.</p>
                </div>
            @else
                <div class="mt-5">
                    <p>You will be notified once the results are ready. You can check your dashboard for updates.</p>
                    <a href="{{-- route('dashboard') --}}" class="btn btn-primary">Go to Your Dashboard</a>
                </div>
            @endguest
        </div>
    </div>
@endsection
