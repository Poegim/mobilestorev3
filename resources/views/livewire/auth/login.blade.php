<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Logowanie')" :description="__('Wpisz login i hasło')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Login -->
            <flux:input
                name="login"
                :label="__('Login')"
                :value="old('login')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="login"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Hasło')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Hasło')"
                viewable
            />

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Zapamiętaj mnie')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Zaloguj') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>