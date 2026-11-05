@props(['name'])
<span {{ $attributes->class(['ui-icon']) }} style="--icon-url: url('{{ asset('icons/lucide/' . $name . '.svg') }}')" aria-hidden="true"></span>
