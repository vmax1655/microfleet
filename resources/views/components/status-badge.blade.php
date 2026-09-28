@props([
    'status',
    'tone' => null,
])

@php
    // Fixed mapping — see App\Support\Format::badgeTone(). Never improvised per page.
    $resolved = $tone ?? \App\Support\Format::badgeTone($status);

    $tones = [
        'success' => 'text-[#14804A] bg-[#E7F6EE] border-[#A6E0C1]',
        'warning' => 'text-[#B54708] bg-[#FEF0D6] border-[#F5D28A]',
        'danger'  => 'text-[#B42318] bg-[#FEECEA] border-[#F5B5AE]',
        'info'    => 'text-[#1570EF] bg-[#EAF2FE] border-[#B0CDFA]',
        'muted'   => 'text-[#5A6B64] bg-[#F0F4F2] border-[#C9D2CD]',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium '
        .($tones[$resolved] ?? $tones['muted']),
]) }}>{{ $slot->isNotEmpty() ? $slot : $status }}</span>
