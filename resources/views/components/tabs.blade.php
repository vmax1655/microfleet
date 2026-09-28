@props([
    'tabs' => [],        // [['key' => 'overview', 'label' => 'Overview', 'count' => 3], …]
    'default' => null,
    'panelClass' => 'mt-5',
])

@php $default = $default ?? ($tabs[0]['key'] ?? ''); @endphp

{{--
    Panel bodies live in the default slot and toggle on the `tab` property:
      <div x-show="tab === 'overview'" role="tabpanel">…</div>
--}}
<div x-data="{ tab: @js($default) }" {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="overflow-x-auto border-b border-neutral-200">
        <div class="flex min-w-max gap-1" role="tablist">
            @foreach($tabs as $t)
                <button type="button"
                        role="tab"
                        :aria-selected="tab === @js($t['key']) ? 'true' : 'false'"
                        :tabindex="tab === @js($t['key']) ? 0 : -1"
                        @click="tab = @js($t['key'])"
                        @keydown.right.prevent="$el.nextElementSibling?.focus(); $el.nextElementSibling?.click()"
                        @keydown.left.prevent="$el.previousElementSibling?.focus(); $el.previousElementSibling?.click()"
                        class="relative -mb-px flex items-center gap-2 whitespace-nowrap border-b-2 px-3.5 py-2.5 text-sm transition-colors"
                        :class="tab === @js($t['key'])
                            ? 'border-primary-600 font-medium text-primary-700'
                            : 'border-transparent text-neutral-600 hover:border-neutral-300 hover:text-neutral-900'">
                    @if(! empty($t['icon']))
                        <x-icon :name="$t['icon']" class="h-4 w-4" />
                    @endif
                    {{ $t['label'] }}
                    @if(isset($t['count']))
                        <span class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums"
                              :class="tab === @js($t['key']) ? 'bg-primary-100 text-primary-800' : 'bg-neutral-100 text-neutral-600'">
                            {{ $t['count'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="{{ $panelClass }}">
        {{ $slot }}
    </div>
</div>
