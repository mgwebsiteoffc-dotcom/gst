@php
    $field = $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp
<select {{ $attributes->merge([
    'class' => 'w-full rounded-lg text-sm shadow-sm focus:ring-1 '
        .($invalid
            ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'),
]) }}>{{ $slot }}</select>
