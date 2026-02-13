@extends('layouts.simple')

@section('css_before')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('content')
    <div class="login-container">
        <div class="background-pattern"></div>

        <div class="login-content">
            <!-- Left Side - Welcome -->
            <div class="welcome-section">
                <canvas id="meshCanvas" class="mesh-background"></canvas>
                <div class="welcome-content">
                    <h1 class="welcome-title">Your Projects,<br>At a Glance</h1>
                    <p class="welcome-subtitle">Track progress, review documents, and stay informed on your construction projects</p>

                    <div class="features-list">
                        <div class="feature-item">
                            <i class="fas fa-hard-hat"></i>
                            <span>Project Overview</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Financial Tracking</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Schedule & Progress</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-section">
                <div class="login-card">
                    <div class="login-header">
                        <div class="logo-wrapper">
                            <img class="logo-img" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Wajenzi Logo">
                        </div>
                        <h2 class="brand-name">Client Portal</h2>
                        <p class="login-subtitle">Sign in to view your projects</p>
                    </div>

                    <form class="login-form" method="POST" action="{{ route('client.login') }}">
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Email Address
                            </label>
                            <input id="email"
                                   type="email"
                                   class="form-input @error('email') is-invalid @enderror"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="Enter your email"
                                   required
                                   autocomplete="email"
                                   autofocus>
                            @error('email')
                                <span class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>
                                Password
                            </label>
                            <div class="password-wrapper">
                                <input id="password"
                                       type="password"
                                       class="form-input @error('password') is-invalid @enderror"
                                       name="password"
                                       placeholder="Enter your password"
                                       required
                                       autocomplete="current-password">
                                <button type="button" class="toggle-password" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="form-options">
                            <label class="remember-checkbox">
                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <span class="checkbox-label">Remember me</span>
                            </label>
                        </div>

                        <button type="submit" class="login-button">
                            <span>Sign In</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <div class="login-footer">
                            <p class="footer-text">
                                Need help? Contact
                                <a href="mailto:support@wajenzi.com" class="support-link">support@wajenzi.com</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --logo-blue-light: #7BB3FF;
            --logo-blue-primary: #2563EB;
            --logo-blue-dark: #1D4ED8;
            --logo-green: #22C55E;
            --logo-green-dark: #16A34A;
            --logo-text-dark: #1F2937;
            --primary-color: #2563EB;
            --primary-dark: #1D4ED8;
            --secondary-color: #22C55E;
            --accent-color: #7BB3FF;
            --bg-light: #F8FAFC;
            --bg-dark: #1F2937;
            --text-primary: #1F2937;
            --text-secondary: #64748B;
            --border-color: #E2E8F0;
            --error-color: #EF4444;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--bg-light); color: var(--text-primary); }

        .login-container { min-height: 100vh; display: flex; position: relative; overflow: hidden; }
        .background-pattern { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: radial-gradient(circle at 20% 80%, var(--primary-color) 0%, transparent 50%), radial-gradient(circle at 80% 20%, var(--secondary-color) 0%, transparent 50%); opacity: 0.05; z-index: 0; }
        .login-content { width: 100%; display: flex; position: relative; z-index: 1; }

        .welcome-section { flex: 1; background: linear-gradient(135deg, var(--logo-blue-primary) 0%, var(--logo-green) 100%); display: flex; align-items: center; justify-content: center; padding: 3rem; position: relative; overflow: hidden; }
        .mesh-background { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .welcome-section::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: radial-gradient(circle at 10% 20%, rgba(255,255,255,0.1) 1px, transparent 1px), radial-gradient(circle at 90% 80%, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 100px 100px, 120px 120px; animation: meshFloat 20s linear infinite; z-index: 1; }
        @keyframes meshFloat { 0% { transform: translate(0, 0); } 50% { transform: translate(10px, -20px); } 100% { transform: translate(0, 0); } }
        .welcome-content { max-width: 500px; color: white; position: relative; z-index: 1; }
        .welcome-title { font-size: 3rem; font-weight: 700; margin-bottom: 1rem; line-height: 1.2; }
        .welcome-subtitle { font-size: 1.25rem; opacity: 0.9; margin-bottom: 3rem; }
        .features-list { display: flex; flex-direction: column; gap: 1.5rem; }
        .feature-item { display: flex; align-items: center; gap: 1rem; font-size: 1.1rem; }
        .feature-item i { font-size: 1.5rem; opacity: 0.8; }

        .login-section { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; background: linear-gradient(45deg, var(--bg-light) 0%, #ffffff 100%); }
        .login-card { width: 100%; max-width: 450px; background: white; border-radius: 1rem; box-shadow: var(--shadow-lg); padding: 3rem; }
        .login-header { text-align: center; margin-bottom: 2.5rem; }
        .logo-wrapper { display: inline-block; margin-bottom: 1rem; }
        .logo-img { width: 80px; height: 80px; border-radius: 1rem; box-shadow: var(--shadow-md); }
        .brand-name { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin: 0.5rem 0; }
        .login-subtitle { color: var(--text-secondary); font-size: 1rem; }
        .login-form { display: flex; flex-direction: column; gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-label { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500; font-size: 0.875rem; }
        .form-label i { color: var(--text-secondary); font-size: 0.875rem; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--border-color); border-radius: 0.5rem; font-size: 1rem; transition: all 0.2s; background-color: var(--bg-light); }
        .form-input:focus { outline: none; border-color: var(--logo-blue-primary); background-color: white; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-input.is-invalid { border-color: var(--error-color); }
        .password-wrapper { position: relative; }
        .toggle-password { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; }
        .error-message { display: flex; align-items: center; gap: 0.25rem; color: var(--error-color); font-size: 0.875rem; margin-top: 0.25rem; }
        .form-options { display: flex; justify-content: space-between; align-items: center; }
        .remember-checkbox { display: flex; align-items: center; cursor: pointer; }
        .remember-checkbox input[type="checkbox"] { width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; }
        .checkbox-label { color: var(--text-secondary); font-size: 0.875rem; }
        .login-button { display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.875rem; background: linear-gradient(135deg, var(--logo-blue-primary) 0%, var(--logo-green) 100%); color: white; border: none; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .login-button:hover { background: linear-gradient(135deg, var(--logo-blue-dark) 0%, var(--logo-green-dark) 100%); transform: translateY(-1px); }
        .login-footer { text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); }
        .footer-text { color: var(--text-secondary); font-size: 0.875rem; margin: 0; }
        .support-link { color: var(--logo-blue-primary); text-decoration: none; font-weight: 500; }

        @media (max-width: 1024px) { .welcome-section { display: none; } }
        @media (max-width: 480px) { .login-card { padding: 1.5rem; box-shadow: none; } }
    </style>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        class MeshAnimation {
            constructor(canvas) {
                this.canvas = canvas;
                this.ctx = canvas.getContext('2d');
                this.dots = [];
                this.resize();
                this.createDots();
                this.animate();
                window.addEventListener('resize', () => this.resize());
            }
            resize() {
                const rect = this.canvas.parentElement.getBoundingClientRect();
                this.canvas.width = rect.width;
                this.canvas.height = rect.height;
                this.width = rect.width;
                this.height = rect.height;
            }
            createDots() {
                const count = Math.floor((this.width * this.height) / 15000);
                this.dots = [];
                for (let i = 0; i < count; i++) {
                    this.dots.push({ x: Math.random() * this.width, y: Math.random() * this.height, vx: (Math.random() - 0.5) * 0.5, vy: (Math.random() - 0.5) * 0.5, radius: Math.random() * 2 + 1 });
                }
            }
            animate() {
                this.ctx.clearRect(0, 0, this.width, this.height);
                this.dots.forEach(dot => {
                    dot.x += dot.vx; dot.y += dot.vy;
                    if (dot.x < 0 || dot.x > this.width) dot.vx *= -1;
                    if (dot.y < 0 || dot.y > this.height) dot.vy *= -1;
                    dot.x = Math.max(0, Math.min(this.width, dot.x));
                    dot.y = Math.max(0, Math.min(this.height, dot.y));
                    this.ctx.beginPath();
                    this.ctx.arc(dot.x, dot.y, dot.radius, 0, Math.PI * 2);
                    this.ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
                    this.ctx.fill();
                });
                this.dots.forEach((dot, i) => {
                    this.dots.slice(i + 1).forEach(other => {
                        const dist = Math.sqrt(Math.pow(dot.x - other.x, 2) + Math.pow(dot.y - other.y, 2));
                        if (dist < 120) {
                            this.ctx.strokeStyle = `rgba(255, 255, 255, ${(120 - dist) / 120 * 0.4})`;
                            this.ctx.lineWidth = 0.5;
                            this.ctx.beginPath();
                            this.ctx.moveTo(dot.x, dot.y);
                            this.ctx.lineTo(other.x, other.y);
                            this.ctx.stroke();
                        }
                    });
                });
                requestAnimationFrame(() => this.animate());
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('meshCanvas');
            if (canvas) new MeshAnimation(canvas);
        });
    </script>
@endsection
