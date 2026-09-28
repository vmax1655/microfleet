@props([
    'rows' => 6,
    'cols' => 6,
    'header' => true,
])

<div class="animate-pulse" aria-hidden="true">
    @if($header)
        <div class="flex items-center gap-4 border-b border-neutral-200 bg-neutral-100 px-3 py-3">
            @for($c = 0; $c < $cols; $c++)
                <div class="h-2.5 rounded bg-neutral-300" style="width: {{ $c === 0 ? 18 : 12 }}%"></div>
            @endfor
        </div>
    @endif

    @for($r = 0; $r < $rows; $r++)
        <div class="flex items-center gap-4 border-b border-neutral-100 px-3 py-4 {{ $r % 2 ? 'bg-neutral-50' : '' }}">
            @for($c = 0; $c < $cols; $c++)
                @if($c === 0)
                    <div class="flex items-center gap-2.5" style="width: 18%">
                        <div class="h-8 w-8 shrink-0 rounded-full bg-neutral-200"></div>
                        <div class="h-2.5 flex-1 rounded bg-neutral-200"></div>
                    </div>
                @else
                    <div class="h-2.5 rounded bg-neutral-200" style="width: {{ 12 - ($c % 3) }}%"></div>
                @endif
            @endfor
        </div>
    @endfor
</div>
