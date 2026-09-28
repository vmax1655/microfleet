@props([
    'steps' => [],       // ['Personal Info', 'Address & Contact', …]
    'model' => 'step',   // Alpine property holding the 1-based current step
])

<ol {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-2 gap-y-3']) }}>
    @foreach($steps as $i => $label)
        @php $n = $i + 1; @endphp
        <li class="flex items-center gap-2">
            <button type="button"
                    @click="goTo({{ $n }})"
                    :aria-current="{{ $model }} === {{ $n }} ? 'step' : null"
                    class="flex items-center gap-2 rounded-[8px] px-1 py-1 text-left transition-colors">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border text-xs font-semibold tabular-nums transition-colors"
                      :class="{{ $model }} > {{ $n }}
                          ? 'border-primary-600 bg-primary-600 text-white'
                          : ({{ $model }} === {{ $n }}
                              ? 'border-primary-600 bg-white text-primary-700 ring-2 ring-primary-600/25'
                              : 'border-neutral-300 bg-white text-neutral-500')">
                    <template x-if="{{ $model }} > {{ $n }}">
                        <span><x-icon name="check" class="h-4 w-4" stroke="2.5" /></span>
                    </template>
                    <template x-if="{{ $model }} <= {{ $n }}">
                        <span>{{ $n }}</span>
                    </template>
                </span>

                <span class="hidden text-[13px] sm:block"
                      :class="{{ $model }} === {{ $n }} ? 'font-medium text-neutral-800' : 'text-neutral-500'">
                    {{ $label }}
                </span>
            </button>

            @if(! $loop->last)
                <span class="hidden h-px w-6 bg-neutral-300 sm:block lg:w-8" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
