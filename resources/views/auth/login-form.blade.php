<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf

    @if(session('message'))
        <div class="auth-alert auth-alert--info">
            {{ session('message') }}
        </div>
    @endif

    @if($errors->any())
        <div class="auth-alert auth-alert--error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="auth-field">
        <label for="email" class="auth-label">{{ __('Email Address') }}</label>
        <input
            id="email"
            class="auth-input"
            type="email"
            name="email"
            value="{{ old('email') }}"
            autocomplete="email"
            autofocus
            required
            placeholder="{{ __('Enter your email') }}"
        >
        @error('email')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password" class="auth-label">{{ __('Password') }}</label>
        <input
            id="password"
            class="auth-input"
            type="password"
            name="password"
            autocomplete="current-password"
            required
            placeholder="{{ __('Enter your password') }}"
        >
        @error('password')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-row">
        <div class="auth-check">
            <input id="remember" name="remember" type="checkbox">
            <label for="remember">{{ __('Remember me') }}</label>
        </div>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="auth-link">{{ __('Forgot Password?') }}</a>
        @endif
    </div>

    <button type="submit" class="auth-btn auth-btn--primary">
        {{ __('Sign In') }}
    </button>
</form>
