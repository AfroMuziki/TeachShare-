@props(['name', 'label', 'type' => 'text', 'autocomplete' => null])

@php
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($name);
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->merge(['class' => 'block w-full rounded-xl border bg-white px-4 py-3 text-[15px] text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-[#2F5BFF] focus:outline-none focus:ring-4 focus:ring-[#2F5BFF]/20 '.($invalid ? 'border-red-500' : 'border-gray-300')]) }}
    >

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>
    @enderror
</div>
