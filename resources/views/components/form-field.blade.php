@props([
    'label',
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'prefix' => null,       // e.g. '₱'
    'suffix' => null,       // e.g. '%'
    'options' => [],        // for type="select"
    'rows' => 3,            // for type="textarea"
    'id' => null,
    'tabular' => false,
])

@php
    $id = $id ?? ($name ? 'f-'.$name : 'f-'.\Illuminate\Support\Str::random(6));
    $describedBy = collect([
        $error ? $id.'-error' : null,
        $help ? $id.'-help' : null,
    ])->filter()->implode(' ');

    $control = 'block w-full rounded-[8px] border bg-white text-sm text-neutral-800 placeholder:text-neutral-400 '
        .'disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500 focus:outline-none focus:ring-2 '
        .($tabular ? 'tabular-nums ' : '')
        .($error
            ? 'border-danger focus:border-danger focus:ring-danger/25 '
            : 'border-neutral-300 focus:border-primary-500 focus:ring-primary-600/30 ');

    $sized = $type === 'textarea' ? ' px-3 py-2' : ' h-9 px-3';
    $padded = $control.$sized
        .($prefix ? ' pl-7' : '')
        .($suffix ? ' pr-8' : '');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'min-w-0']) }}>
    <label for="{{ $id }}" class="mb-1 block text-[13px] font-medium text-neutral-700">
        {{ $label }}
        @if($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>

    <div class="relative">
        @if($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-7 place-items-center text-sm text-neutral-500">{{ $prefix }}</span>
        @endif

        @if($type === 'select')
            <select id="{{ $id }}"
                    @if($name) name="{{ $name }}" @endif
                    @required($required)
                    @disabled($disabled)
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    @if($error) aria-invalid="true" @endif
                    {{ $attributes->except('class')->merge(['class' => $padded]) }}>
                @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
                @foreach($options as $optValue => $optLabel)
                    @php $v = is_int($optValue) ? $optLabel : $optValue; @endphp
                    <option value="{{ $v }}" @selected((string) $value === (string) $v)>{{ $optLabel }}</option>
                @endforeach
            </select>

        @elseif($type === 'textarea')
            <textarea id="{{ $id }}"
                      @if($name) name="{{ $name }}" @endif
                      rows="{{ $rows }}"
                      placeholder="{{ $placeholder }}"
                      @required($required)
                      @disabled($disabled)
                      @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                      @if($error) aria-invalid="true" @endif
                      {{ $attributes->except('class')->merge(['class' => $padded]) }}>{{ $value }}</textarea>

        @else
            <input id="{{ $id }}"
                   type="{{ $type }}"
                   @if($name) name="{{ $name }}" @endif
                   value="{{ $value }}"
                   placeholder="{{ $placeholder }}"
                   @required($required)
                   @disabled($disabled)
                   @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                   @if($error) aria-invalid="true" @endif
                   {{ $attributes->except('class')->merge(['class' => $padded]) }}>
        @endif

        @if($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-0 grid w-8 place-items-center text-sm text-neutral-500">{{ $suffix }}</span>
        @endif
    </div>

    @if($error)
        <p id="{{ $id }}-error" class="mt-1 flex items-start gap-1 text-xs text-danger">
            <x-icon name="alert-circle" class="mt-px h-3.5 w-3.5 shrink-0" />
            <span>{{ $error }}</span>
        </p>
    @elseif($help)
        <p id="{{ $id }}-help" class="mt-1 text-xs text-neutral-500">{{ $help }}</p>
    @endif
</div>
