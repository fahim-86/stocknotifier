<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Hero Panel (60%) -->
        <div class="hidden lg:flex lg:w-3/5 relative overflow-hidden"
            style="background: linear-gradient(135deg, #006a4e 0%, #004d35 100%);">
            <!-- Geometric overlay (subtle SVG pattern) -->
            <div class="absolute inset-0 opacity-10">
                <svg width="100%" height="100%">
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5" />
                        </pattern>
                        <pattern id="dots" x="10" y="10" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1.5" fill="white" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                    <rect width="100%" height="100%" fill="url(#dots)" />
                </svg>
            </div>

            <!-- Emblem & Content -->
            <div class="relative z-10 flex flex-col items-center justify-center w-full p-12 text-center text-white">
                <!-- DSHE-like emblem (you can replace with actual logo) -->
                <div class="mb-8">
                    <svg class="w-28 h-28 mx-auto" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="white" stroke-width="2" />
                        <path d="M50 15 L60 45 L90 50 L60 55 L50 85 L40 55 L10 50 L40 45 Z" fill="white" />
                        <!-- Open book inside star -->
                        <g transform="translate(35,35) scale(0.3)">
                            <path d="M5 0 L10 10 L0 3 L10 3 L0 10 Z" fill="#006a4e" />
                        </g>
                    </svg>
                </div>
                <h1 class="text-4xl font-serif font-bold tracking-tight">পরিদপ্তর</h1>
                <h2 class="mt-2 text-2xl font-medium">Directorate of Secondary & Higher Education</h2>
                <p class="mt-4 max-w-md text-lg opacity-90">শিক্ষার আলো, উন্নত আগামীর সোপান</p>
                <p class="mt-8 text-sm opacity-75">Empowering students, educators, and institutions</p>
            </div>

            <!-- Decorative red stripe / accent -->
            <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 w-16 h-1 bg-red-500 rounded-full"></div>
        </div>

        <!-- Form Panel (40%) -->
        <div class="w-full lg:w-2/5 flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-md">
                <!-- Mobile banner (visible only on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center justify-center p-3 bg-green-900 rounded-full">
                        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm-2.82 6L12 10.35 14.82 9 12 7.65 9.18 9zM16 15.54L12 17.72 8 15.54v-3.34l4 2.17 4-2.17v3.34z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-2xl font-serif font-bold text-green-900">DSHE Login</h2>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <h2 class="text-3xl font-serif font-bold text-gray-800 mb-2 hidden lg:block">Sign in to your account</h2>
                    <p class="text-gray-500 mb-6 hidden lg:block">Welcome back! Please enter your details.</p>

                    <!-- Email -->
                    <div class="mb-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password with show/hide toggle -->
                    <div class="mb-4 relative" x-data="{ show: false }">
                        <x-input-label for="password" :value="__('Password')" />
                        <div class="relative">
                            <x-text-input id="password" class="block mt-1 w-full pr-10"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                @click="show = !show">
                                <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Remember Me -->
                    <div class="block mb-4">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-green-700 shadow-sm focus:ring-green-500" name="remember">
                            <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <x-primary-button class="bg-green-700 hover:bg-green-800 focus:ring-green-500">
                            {{ __('Sign in') }}
                        </x-primary-button>
                        @if (Route::has('password.request'))
                        <a class="text-sm text-green-700 hover:text-green-800 underline" href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                        @endif
                    </div>

                    <p class="text-sm text-gray-500">
                        Don't have an account?
                        <a class="text-green-700 hover:text-green-800 font-medium" href="{{ route('register') }}">
                            Create an account
                        </a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>