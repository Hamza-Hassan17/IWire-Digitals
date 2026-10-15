<div class="tbl-foot">
  <span>Showing {{ $p->firstItem() ?? 0 }}–{{ $p->lastItem() ?? 0 }} of {{ number_format($p->total()) }}</span>
  <div class="pager">
    @if ($p->onFirstPage())<span><span class="ms">chevron_left</span></span>@else<a href="{{ $p->previousPageUrl() }}"><span class="ms">chevron_left</span></a>@endif
    @foreach (range(1, $p->lastPage()) as $n)
      @if ($n === $p->currentPage())<span class="on">{{ $n }}</span>@else<a href="{{ $p->url($n) }}">{{ $n }}</a>@endif
    @endforeach
    @if ($p->hasMorePages())<a href="{{ $p->nextPageUrl() }}"><span class="ms">chevron_right</span></a>@else<span><span class="ms">chevron_right</span></span>@endif
  </div>
</div>
