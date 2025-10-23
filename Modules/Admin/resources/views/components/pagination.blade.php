@props([
    'paginator',
    'containerClass' => '',
])

@php
    $isPaginator = $paginator instanceof \Illuminate\Contracts\Pagination\Paginator
        || $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

@if($isPaginator && $paginator->hasPages())
    @once
        <style>
            .admin-pagination {
                padding: 20px 32px 40px;
                border-top: 1px solid rgba(15, 35, 87, 0.06);
                background: #fff;
                display: flex;
                justify-content: center;
            }

            .admin-pagination nav > ul,
            .admin-pagination nav > div > ul,
            .admin-pagination nav > div > div > ul,
            .admin-pagination nav .pagination {
                display: inline-flex;
                gap: 12px;
                padding: 10px 16px;
                border-radius: 999px;
                background: linear-gradient(135deg, rgba(230, 236, 255, 0.92), rgba(206, 220, 255, 0.88));
                box-shadow: 0 12px 24px rgba(26, 44, 104, 0.18);
                align-items: center;
            }

            .admin-pagination nav > ul li a,
            .admin-pagination nav > ul li span,
            .admin-pagination nav > div > ul li a,
            .admin-pagination nav > div > ul li span,
            .admin-pagination nav > div > div > ul li a,
            .admin-pagination nav > div > div > ul li span,
            .admin-pagination nav .pagination li a,
            .admin-pagination nav .pagination li span {
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

            .admin-pagination nav > ul li a:hover,
            .admin-pagination nav > div > ul li a:hover,
            .admin-pagination nav > div > div > ul li a:hover,
            .admin-pagination nav .pagination li a:hover {
                background: #ffffff;
                box-shadow: 0 8px 18px rgba(26, 44, 104, 0.22);
                transform: translateY(-2px);
            }

            .admin-pagination nav > ul li span[aria-current="page"],
            .admin-pagination nav > div > ul li span[aria-current="page"],
            .admin-pagination nav > div > div > ul li span[aria-current="page"],
            .admin-pagination nav .pagination li span[aria-current="page"] {
                background: linear-gradient(135deg, #4a76ff, #265bff);
                color: #fff;
                box-shadow: 0 12px 24px rgba(38, 91, 255, 0.35);
            }

            .admin-pagination nav > ul li:first-child a,
            .admin-pagination nav > ul li:last-child a,
            .admin-pagination nav > div > ul li:first-child a,
            .admin-pagination nav > div > ul li:last-child a,
            .admin-pagination nav > div > div > ul li:first-child a,
            .admin-pagination nav > div > div > ul li:last-child a,
            .admin-pagination nav .pagination li:first-child a,
            .admin-pagination nav .pagination li:last-child a {
                width: auto;
                padding: 0 18px;
                border-radius: 999px;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            @media (max-width: 991px) {
                .admin-pagination {
                    padding: 20px 18px 32px;
                }

                .admin-pagination nav > ul,
                .admin-pagination nav > div > ul,
                .admin-pagination nav > div > div > ul,
                .admin-pagination nav .pagination {
                    gap: 6px;
                    padding: 8px 10px;
                }

                .admin-pagination nav > ul li a,
                .admin-pagination nav > ul li span,
                .admin-pagination nav > div > ul li a,
                .admin-pagination nav > div > ul li span,
                .admin-pagination nav > div > div > ul li a,
                .admin-pagination nav > div > div > ul li span,
                .admin-pagination nav .pagination li a,
                .admin-pagination nav .pagination li span {
                    width: 36px;
                    height: 36px;
                    font-size: 0.85rem;
                }

                .admin-pagination nav > ul li:first-child a,
                .admin-pagination nav > ul li:last-child a,
                .admin-pagination nav > div > ul li:first-child a,
                .admin-pagination nav > div > ul li:last-child a,
                .admin-pagination nav > div > div > ul li:first-child a,
                .admin-pagination nav > div > div > ul li:last-child a,
                .admin-pagination nav .pagination li:first-child a,
                .admin-pagination nav .pagination li:last-child a {
                    padding: 0 12px;
                    font-size: 0.75rem;
                }
            }
        </style>
    @endonce

    <div class="{{ trim('admin-pagination ' . $containerClass) }}">
        {{ $paginator->links('vendor.pagination.bootstrap-5') }}
    </div>
@endif
