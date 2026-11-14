@if ($paginator->hasPages())
    <nav aria-label="Halaman" class="paginasi">
        @if ($paginator->onFirstPage())
            <span class="redup">← Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Sebelumnya</a>
        @endif
        <span class="redup">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya →</a>
        @else
            <span class="redup">Berikutnya →</span>
        @endif
    </nav>
@endif
