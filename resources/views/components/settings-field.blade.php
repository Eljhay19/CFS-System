@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => null, 'autocomplete' => null])
<div {{ $attributes->class(['settings-field']) }}>
    <label for="account_{{ $name }}">{{ $label }}</label>
    <div class="settings-input">
        <x-icon :name="$type === 'password' ? 'lock-keyhole' : ($type === 'email' ? 'mail' : 'user-round')" />
        <input id="account_{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ old($name, $value) }}" @endif {{ $required ? 'required' : '' }} @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if (in_array($name, ['password', 'password_confirmation'])) minlength="8" @endif>
        @if ($type === 'password')<button class="settings-icon-button" type="button" aria-label="Show {{ strtolower($label) }}" data-password-toggle="account_{{ $name }}" data-icon-show="{{ asset('icons/lucide/eye.svg') }}" data-icon-hide="{{ asset('icons/lucide/eye-off.svg') }}"><x-icon name="eye" /></button>@endif
    </div>
    @error($name)<small class="business-error">{{ $message }}</small>@enderror
</div>
