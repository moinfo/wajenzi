<!doctype html>
<html lang="{{ config('app.locale') }}" data-theme="">
<script>
    (function(){
        var t = localStorage.getItem('client-theme');
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
</script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal Login - {{ settings('SYSTEM_NAME') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* Mantine Tokens */
        :root {
            --m-font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --m-blue-5: #339af0; --m-blue-6: #228be6; --m-blue-7: #1c7ed6; --m-blue-8: #1971c2;
            --m-teal-6: #12b886; --m-teal-7: #0ca678;
            --m-gray-0: #f8f9fa; --m-gray-1: #f1f3f5; --m-gray-2: #e9ecef; --m-gray-3: #dee2e6;
            --m-gray-4: #ced4da; --m-gray-5: #adb5bd; --m-gray-6: #868e96; --m-gray-7: #495057;
            --m-gray-8: #343a40; --m-gray-9: #212529;
            --m-red-6: #fa5252;
            --m-radius-sm: 0.25rem; --m-radius-md: 0.5rem; --m-radius-lg: 1rem;
            --m-shadow-md: 0 1px 3px rgba(0,0,0,.04), 0 10px 20px -5px rgba(0,0,0,.06);

            --login-bg: #f1f3f5;
            --card-bg: #ffffff;
            --card-border: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #868e96;
            --input-bg: #ffffff;
            --input-border: #ced4da;
            --input-focus-border: #228be6;
            --divider: #e9ecef;
        }

        [data-theme="dark"] {
            --m-dark-4: #373A40; --m-dark-5: #2C2E33; --m-dark-6: #25262B; --m-dark-7: #1A1B1E;
            --login-bg: #141517;
            --card-bg: #1A1B1E;
            --card-border: #2C2E33;
            --text-primary: #C1C2C5;
            --text-secondary: #909296;
            --input-bg: #25262B;
            --input-border: #373A40;
            --input-focus-border: #339af0;
            --divider: #2C2E33;
            --m-shadow-md: 0 1px 3px rgba(0,0,0,.4), rgba(0,0,0,.35) 0 10px 15px -5px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--m-font); background: var(--login-bg); color: var(--text-primary); min-height: 100vh; display: flex; }

        /* ── Layout ── */
        .login-wrapper { display: flex; width: 100%; min-height: 100vh; }

        /* ── Left panel ── */
        .login-hero {
            flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem;
            background: linear-gradient(135deg, var(--m-blue-7) 0%, var(--m-teal-6) 100%);
            position: relative; overflow: hidden;
        }
        .login-hero::before {
            content: ''; position: absolute; inset: 0;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.08) 1px, transparent 1px),
                radial-gradient(circle at 70% 60%, rgba(255,255,255,0.06) 1px, transparent 1px),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 60px 60px, 90px 90px, 120px 120px;
        }
        .hero-content { position: relative; z-index: 1; color: #fff; max-width: 440px; }
        .hero-content h1 { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 0.75rem; }
        .hero-content p { font-size: 1.05rem; opacity: 0.85; line-height: 1.6; margin-bottom: 2.5rem; }
        .hero-features { display: flex; flex-direction: column; gap: 1.25rem; }
        .hero-feature { display: flex; align-items: center; gap: 0.875rem; font-size: 0.95rem; }
        .hero-feature-icon {
            width: 40px; height: 40px; border-radius: var(--m-radius-md); display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.15); font-size: 1rem; flex-shrink: 0;
        }
        .hero-feature span { opacity: 0.9; }

        /* ── Right panel ── */
        .login-form-panel {
            flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem;
            background: var(--login-bg);
            position: relative; z-index: 1;
        }
        .login-card {
            width: 100%; max-width: 420px;
            background: var(--card-bg); border: 1px solid var(--card-border);
            border-radius: var(--m-radius-lg); padding: 2.5rem;
            box-shadow: var(--m-shadow-md);
        }

        /* ── Card header ── */
        .login-card-header { text-align: center; margin-bottom: 2rem; }
        .login-logo {
            width: 64px; height: 64px; border-radius: var(--m-radius-md);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;
        }
        .login-card-header h2 { font-size: 1.375rem; font-weight: 700; margin: 0 0 0.25rem; }
        .login-card-header p { font-size: 0.875rem; color: var(--text-secondary); margin: 0; }

        /* ── Form ── */
        .m-form-group { margin-bottom: 1.25rem; }
        .m-label {
            display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem;
            color: var(--text-primary);
        }
        .m-input-wrapper { position: relative; }
        .m-input {
            width: 100%; padding: 0.625rem 0.875rem;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: var(--m-radius-sm); font-size: 0.875rem; color: var(--text-primary);
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: var(--m-font); line-height: 1.55;
        }
        .m-input::placeholder { color: var(--text-secondary); }
        .m-input:focus {
            outline: none; border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(34,139,230,0.15);
        }
        .m-input.is-invalid { border-color: var(--m-red-6); }
        .m-input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(250,82,82,0.15); }

        .m-input-icon {
            position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
            color: var(--text-secondary); font-size: 0.875rem; pointer-events: none;
        }
        .m-input.has-icon { padding-left: 2.375rem; }

        .toggle-pw {
            position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-secondary); cursor: pointer;
            padding: 0.25rem 0.375rem; border-radius: var(--m-radius-sm); font-size: 0.8rem;
        }
        .toggle-pw:hover { color: var(--text-primary); }

        .m-error {
            display: flex; align-items: center; gap: 0.25rem;
            color: var(--m-red-6); font-size: 0.75rem; margin-top: 0.375rem;
        }

        /* ── Checkbox ── */
        .m-checkbox-row { display: flex; align-items: center; margin-bottom: 1.25rem; }
        .m-checkbox-row input[type="checkbox"] {
            width: 1.125rem; height: 1.125rem; margin: 0; margin-right: 0.5rem;
            accent-color: var(--m-blue-6); cursor: pointer;
        }
        .m-checkbox-row label { font-size: 0.875rem; color: var(--text-secondary); cursor: pointer; }

        /* ── Button ── */
        .m-btn-primary {
            width: 100%; padding: 0.6875rem; border: none; border-radius: var(--m-radius-sm);
            background: var(--m-blue-6); color: #fff; font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: background 0.15s; font-family: var(--m-font);
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .m-btn-primary:hover { background: var(--m-blue-7); }
        .m-btn-primary:active { background: var(--m-blue-8); }

        /* ── Footer ── */
        .login-footer {
            text-align: center; margin-top: 1.5rem; padding-top: 1.25rem;
            border-top: 1px solid var(--divider);
        }
        .login-footer p { font-size: 0.8125rem; color: var(--text-secondary); margin: 0; }
        .login-footer a { color: var(--m-blue-6); text-decoration: none; font-weight: 500; }
        .login-footer a:hover { text-decoration: underline; }

        /* ── Theme toggle ── */
        .theme-toggle {
            position: fixed; top: 1rem; right: 1rem; z-index: 10;
            width: 36px; height: 36px; border-radius: var(--m-radius-sm);
            border: 1px solid var(--card-border); background: var(--card-bg);
            color: var(--text-secondary); cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
        }
        .theme-toggle:hover { color: var(--text-primary); border-color: var(--m-blue-6); }

        /* ── Dark mode adjustments ── */
        [data-theme="dark"] .login-hero {
            background: linear-gradient(135deg, #1864ab 0%, #087f5b 100%);
        }
        [data-theme="dark"] .login-card {
            border-color: #373A40;
        }
        [data-theme="dark"] .m-input {
            color: #C1C2C5;
        }
        [data-theme="dark"] .m-input::placeholder {
            color: #5C5F66;
        }
        [data-theme="dark"] .toggle-pw {
            color: #5C5F66;
        }
        [data-theme="dark"] .toggle-pw:hover {
            color: #C1C2C5;
        }
        [data-theme="dark"] .m-checkbox-row label {
            color: #909296;
        }
        [data-theme="dark"] .login-footer p {
            color: #909296;
        }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .login-hero { display: none; }
            .login-form-panel { padding: 1.5rem; }
        }
        @media (max-width: 480px) {
            .login-card { padding: 1.5rem; box-shadow: none; border: none; background: transparent; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle color scheme">
        <i class="fas fa-sun" id="themeIcon"></i>
    </button>

    <div class="login-wrapper">
        <!-- Left Hero -->
        <div class="login-hero">
            <div class="hero-content">
                <h1>Your Projects,<br>At a Glance</h1>
                <p>Track progress, review documents, and stay informed on your construction projects.</p>
                <div class="hero-features">
                    <div class="hero-feature">
                        <div class="hero-feature-icon"><i class="fas fa-hard-hat"></i></div>
                        <span>Real-time project overview & milestones</span>
                    </div>
                    <div class="hero-feature">
                        <div class="hero-feature-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <span>Invoices, quotations & payment tracking</span>
                    </div>
                    <div class="hero-feature">
                        <div class="hero-feature-icon"><i class="fas fa-calendar-check"></i></div>
                        <span>Construction schedule & progress photos</span>
                    </div>
                    <div class="hero-feature">
                        <div class="hero-feature-icon"><i class="fas fa-chart-line"></i></div>
                        <span>Work progress charts & site visit reports</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form -->
        <div class="login-form-panel">
            <div class="login-card">
                <div class="login-card-header">
                    <img class="login-logo" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Wajenzi">
                    <h2>Client Portal</h2>
                    <p>Sign in to view your projects</p>
                </div>

                <form method="POST" action="{{ route('client.login') }}">
                    @csrf

                    <div class="m-form-group">
                        <label class="m-label" for="login">Email or Phone</label>
                        <div class="m-input-wrapper">
                            <i class="fas fa-user m-input-icon"></i>
                            <input id="login"
                                   type="text"
                                   class="m-input has-icon @error('login') is-invalid @enderror"
                                   name="login"
                                   value="{{ old('login') }}"
                                   placeholder="Enter your email or phone"
                                   required autofocus>
                        </div>
                        @error('login')
                            <div class="m-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="m-form-group">
                        <label class="m-label" for="password">Password</label>
                        <div class="m-input-wrapper">
                            <i class="fas fa-lock m-input-icon"></i>
                            <input id="password"
                                   type="password"
                                   class="m-input has-icon @error('password') is-invalid @enderror"
                                   name="password"
                                   placeholder="Enter your password"
                                   required autocomplete="current-password"
                                   style="padding-right: 2.5rem;">
                            <button type="button" class="toggle-pw" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="m-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="m-checkbox-row">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="m-btn-primary">
                        Sign In <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="login-footer">
                        <p>Need help? Contact <a href="mailto:info@wajenziprofessional.co.tz">info@wajenziprofessional.co.tz</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var icon = document.getElementById('toggleIcon');
            if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { input.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        }

        function toggleTheme() {
            var html = document.documentElement;
            var isDark = html.getAttribute('data-theme') === 'dark';
            html.setAttribute('data-theme', isDark ? '' : 'dark');
            localStorage.setItem('client-theme', isDark ? 'light' : 'dark');
            updateThemeIcon();
        }

        function updateThemeIcon() {
            var icon = document.getElementById('themeIcon');
            if (!icon) return;
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
</body>
</html>
