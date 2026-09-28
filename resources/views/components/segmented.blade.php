@props([
    'options' => [],   // [['key' => 'board', 'label' => 'Pipeline', 'icon' => 'columns-3'], …]
    'model' => 'view', // the Alpine property this control drives
    'label' => 'View mode',
])

<div role="group"
     aria-label="{{ $label }}"
     {{ $attributes->merge(['class' => 'inline-flex rounded-[8px] border border-neutral-300 bg-neutral-100 p-0.5']) }}>
    @foreach($options as $o)
        <button type="button"
                @click="{{ $model }} = @js($o['key'])"
                :aria-pressed="{{ $model }} === @js($o['key']) ? 'true' : 'false'"
                class="inline-flex items-center gap-1.5 rounded-[6px] px-3 py-1.5 text-[13px] font-medium transition-colors"
                :class="{{ $model }} === @js($o['key'])
                    ? 'bg-white text-neutral-900 shadow-card'
                    : 'text-neutral-600 hover:text-neutral-900'">
            @if(! empty($o['icon']))<x-icon :name="$o['icon']" class="h-4 w-4" />@endif
            {{ $o['label'] }}
        </button>
    @endforeach
</div>
