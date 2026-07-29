@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif
               class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $label }}@if ($required)<span class="ml-0.5 text-rose-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($name && $errors->has($name))
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first($name) }}</p>
    @endif
</div>
