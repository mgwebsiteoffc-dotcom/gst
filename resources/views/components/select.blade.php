@php
    $field = $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp
<select {{ $attributes->class([
    'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' => $invalid,
]) }}>{{ $slot }}</select>
