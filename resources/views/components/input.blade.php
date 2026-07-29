{{--
    Do NOT declare `name` via @props — that would consume the attribute and strip
    it from the rendered input, silently breaking form submission.
--}}
@php
    $field = $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp
<input {{ $attributes->merge([
    'class' => 'w-full rounded-lg text-sm shadow-sm placeholder:text-slate-400 focus:ring-1 '
        .($invalid
            ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'),
]) }}>
