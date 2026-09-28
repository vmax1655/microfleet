@props(['height' => 'h-80'])

<div class="animate-pulse {{ $height }} w-full" aria-hidden="true">
    <div class="mb-4 flex items-center justify-end gap-3">
        <div class="h-2.5 w-20 rounded bg-neutral-200"></div>
        <div class="h-2.5 w-20 rounded bg-neutral-200"></div>
    </div>
    <div class="flex h-[calc(100%-2.5rem)] items-end gap-2 border-b border-l border-neutral-200 px-2 pb-px">
        @foreach([45, 62, 38, 72, 55, 80, 48, 66, 58, 74, 42, 68] as $h)
            <div class="flex-1 rounded-t bg-neutral-200" style="height: {{ $h }}%"></div>
        @endforeach
    </div>
</div>
