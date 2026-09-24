<x-layouts::auth title="Log in">
    <div class="flex flex-col gap-8">

        <x-auth-header
            title="Sign in to {{ config('app.name') }}"
            description="Enter the email and password of your account."
        />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Email address"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Password"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 end-0 text-sm text-brand-orange-dark" :href="route('password.request')" wire:navigate>
                        Forgot password?
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" label="Keep me signed in on this device" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                Sign in
            </flux:button>
        </form>

        <flux:text class="border-t border-zinc-200 pt-6 text-sm">
            No account yet? Accounts are created by your administrator, so ask them for access.
        </flux:text>
    </div>
</x-layouts::auth>
