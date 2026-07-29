{{--
    Base styling (border, padding, focus ring) comes from the @layer base rules
    in app.css, so every input matches whether or not it uses this component.

    Do NOT declare `name` via @props — that would consume the attribute and
    strip it from the rendered input, silently breaking form submission.
--}}
@php
    $field = $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp
<input {{ $attributes->class([
    'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' => $invalid,
]) }}>
