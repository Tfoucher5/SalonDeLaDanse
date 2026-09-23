<x-ui.card :title="__('Update Password')"
           :subtitle="__('Ensure your account is using a long, random password to stay secure.')">
    <form method="post" action="{{ route('password.update') }}" class="max-w-xl space-y-4">
        @csrf
        @method('put')

        <x-ui.field :label="__('Current Password')" for="update_password_current_password"
                    :messages="$errors->updatePassword->get('current_password')">
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
        </x-ui.field>

        <x-ui.field :label="__('New Password')" for="update_password_password"
                    :messages="$errors->updatePassword->get('password')">
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
        </x-ui.field>

        <x-ui.field :label="__('Confirm Password')" for="update_password_password_confirmation"
                    :messages="$errors->updatePassword->get('password_confirmation')">
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
        </x-ui.field>

        <div class="flex items-center gap-4 pt-2">
            <x-ui.button variant="primary" size="touch">{{ __('Save') }}</x-ui.button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gauge-free">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</x-ui.card>
