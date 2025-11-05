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

        .category-grid-card { margin: 1.5rem 1.5rem 2rem; border: none; border-radius: 26px; box-shadow: 0 28px 58px rgba(19, 48, 132, 0.16); background: linear-gradient(135deg, rgba(248, 250, 252, 0.85), rgba(232, 240, 255, 0.82)); padding: 24px 28px; }
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .category-tile { display: flex; flex-direction: column; gap: 6px; padding: 18px; border-radius: 18px; background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.9); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06); transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease; text-decoration: none; color: inherit; }
        .category-tile:hover { border-color: rgba(59, 130, 246, 0.28); box-shadow: 0 22px 44px rgba(59, 130, 246, 0.14); transform: translateY(-3px); }
        .category-tile .name { font-weight: 600; color: #172655; }
        .category-tile .meta { color: #64748b; font-size: 0.9rem; }

        @media (max-width: 991px) { .resumes-hero { margin: 1.5rem 1rem; padding: 32px; border-radius: 24px; } .category-grid-card { margin: 1.5rem 1rem 2rem; } }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-uppercase text-muted" style="letter-spacing: 0.08em;">Library</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.resumes.index') }}">Resumes</a></li>
                <li class="breadcrumb-item">Categories</li>
            </ul>
        </div>
    </div>

    <div class="resumes-hero">
        <div class="resumes-hero-content">
            <div class="resumes-hero-left">
                <span class="resumes-hero-badge">
                    <i class="feather-layers"></i>
                    Taxonomy
                </span>
                <h1>Resume categories</h1>
                <p>Browse resumes by category. Click a category to view all resumes under it.</p>
                <!-- <div class="mt-3">
                    <a href="{{ route('admin.resumes.index') }}" class="btn btn-outline-primary">
                        <i class="feather-arrow-left me-1"></i> Back to catalogue
                    </a>
                </div> -->
            </div>
            <div class="resumes-stats">
                <div class="resumes-stat-card">
                    <span class="label">Total categories</span>
                    <span class="value">{{ number_format(($categories ?? collect())->count()) }}</span>
                    <span class="hint">Active in the library</span>
                </div>
                <div class="resumes-stat-card">
                    <span class="label">Total resumes</span>
                    <span class="value">{{ number_format($totalResumes ?? 0) }}</span>
                    <span class="hint">Across all categories</span>
                </div>
            </div>
        </div>
    </div>

    <div class="category-grid-card card">
        <div class="category-grid">
            @forelse($categories as $cat)
                <a class="category-tile" href="{{ route('admin.resumes.categories.show', ['category' => $cat->category]) }}">
                    <span class="name">{{ $cat->category }}</span>
                    <span class="meta">{{ number_format($cat->total) }} resumes</span>
                </a>
            @empty
                <div class="text-muted">No categories found.</div>
            @endforelse
        </div>
    </div>
@endsection

