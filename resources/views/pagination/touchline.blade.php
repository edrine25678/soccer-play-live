@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination-disabled" aria-disabled="true">← <span>Previous</span></span>
        @else
            <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">← <span>Previous</span></a>
        @endif

        <span class="pagination-summary">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next"><span>Next</span> →</a>
        @else
            <span class="pagination-disabled" aria-disabled="true"><span>Next</span> →</span>
        @endif
    </nav>
@endif
