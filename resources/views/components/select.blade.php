@props([
    'label',
    'name' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'id' => null,
    'width' => 'w-44',
    'hideLabel' => false,
])

@php
    $id = $id ?? 'select-'.\Illuminate\Support\Str::slug($label).'-'.\Illuminate\Support\Str::random(4);
@endphp

<div>
    <label for="{{ $id }}"
           class="{{ $hideLabel ? 'sr-only' : 'mb-1 block text-xs font-medium text-neutral-600' }}">{{ $label }}</label>
    <select id="{{ $id }}"
            @if($name) name="{{ $name }}" @endif
            {{ $attributes->merge([
                'class' => 'h-9 '.$width.' rounded-[8px] border border-neutral-300 bg-white px-2.5 text-sm text-neutral-800 '
                    .'focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30',
            ]) }}>
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $text)
            @php $val = is_int($value) ? $text : $value; @endphp
            <option value="{{ $val }}" @selected($selected === $val)>{{ $text }}</option>
        @endforeach
    </select>
</div>
