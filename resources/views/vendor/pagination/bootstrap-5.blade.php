@if ($paginator->hasPages())
    <nav class="pagination-modern" role="navigation" aria-label="Pagination Navigation">
        <ul class="pagination-modern__list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pagination-modern__item is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="pagination-modern__link" aria-hidden="true">Prev</span>
                </li>
            @else
                <li class="pagination-modern__item">
                    <a class="pagination-modern__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">Prev</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="pagination-modern__item is-disabled" aria-disabled="true"><span class="pagination-modern__link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pagination-modern__item is-active" aria-current="page"><span class="pagination-modern__link">{{ $page }}</span></li>
                        @else
                            <li class="pagination-modern__item"><a class="pagination-modern__link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination-modern__item">
                    <a class="pagination-modern__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">Next</a>
                </li>
            @else
                <li class="pagination-modern__item is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="pagination-modern__link" aria-hidden="true">Next</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
