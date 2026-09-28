@props(['count' => 1])

@for($i = 0; $i < $count; $i++)
    <div class="animate-pulse rounded-[12px] border border-neutral-200 bg-white p-5 shadow-card" aria-hidden="true">
        <div class="flex items-start justify-between gap-3">
            <div class="h-3 w-28 rounded bg-neutral-200"></div>
            <div class="h-8 w-8 rounded-[8px] bg-neutral-100"></div>
        </div>
        <div class="mt-3 h-7 w-36 rounded bg-neutral-200"></div>
        <div class="mt-4 flex items-center gap-2">
            <div class="h-5 w-16 rounded-full bg-neutral-100"></div>
            <div class="h-3 w-24 rounded bg-neutral-100"></div>
        </div>
    </div>
@endfor
