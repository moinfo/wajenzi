@extends('layouts.simple')

@section('content')
    <div class="bg-body-dark bg-pattern custom-bg custom-container" style="background-image: url('assets/media/various/bg-pattern-inverse.png');">
        <div class="custom-flex-container">
            <div class="custom-card">
                <!-- Logo and Header -->
                <div class="text-center mb-8">
                    <div class="logo-container">
                        <img class="custom-logo" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="">
                    </div>
                    <h2 class="logo-text">
                        <span class="text-dark">Financial</span>
                        <span class="text-accent">Analysis</span>
                    </h2>
                    <h1 class="custom-title">Welcome to Your Dashboard</h1>
                    <p class="custom-subtitle">It's a great day today! {{settings('SYSTEM_NAME')}}</p>
                </div>

                <!-- Login Form -->
                <form class="js-validation-signin" method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="custom-form">
                        <!-- Username Input -->
                        <div class="form-group">
                            <label for="email" class="custom-label">Username</label>
                            <input id="email"
                                   type="email"
                                   class="custom-input @error('email') is-invalid @enderror"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autocomplete="email"
                                   autofocus>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                            @enderror
                        </div>

                        <!-- Password Input -->
                        <div class="form-group">
                            <label for="password" class="custom-label">Password</label>
                            <input id="password"
                                   type="password"
                                   class="custom-input @error('password') is-invalid @enderror"
                                   name="password"
                                   required
                                   autocomplete="current-password">
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                            @enderror
                        </div>

                        <!-- Remember Me & Sign In -->
                        <div class="form-footer">
                            <div class="remember-me">
                                <input type="checkbox"
                                       class="custom-checkbox"
                                       name="remember"
                                       id="remember"
                                    {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember">Remember Me</label>
                            </div>
                            <button type="submit" class="custom-button">
                                Sign In
                            </button>
                        </div>

                        <!-- Forgot Password -->
                        <div class="forgot-password">
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="custom-link">
                                    Forgot Your Password?
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        :root {
            --primary-blue: #4169E1;
            --primary-green: #32CD32;
            --bg-gray: #F0F2F5;
            --text-dark: #333;
            --border-color: #E5E7EB;
        }

        /* Container and Flex Layout */
        .custom-container {
            min-height: 100vh;
            width: 100%;
            padding: 20px;
        }

        .custom-flex-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .custom-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 35%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            margin: 0;
            height: 100vh;
            overflow: hidden;
        }

        html {
            overflow: hidden;
        }

        .custom-container {
            height: 100vh;
            overflow: hidden;
        }

        /* Logo and Header Styles */
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .custom-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1rem 0;
        }

        .text-accent {
            color: var(--primary-green);
        }

        .custom-title {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin: 1rem 0;
        }

        .custom-subtitle {
            color: #666;
            margin-bottom: 2rem;
        }

        /* Form Styles */
        .custom-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .custom-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .custom-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: #F9FAFB;
        }

        .custom-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(65, 105, 225, 0.1);
            background-color: white;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .custom-button {
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-green));
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .custom-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(65, 105, 225, 0.2);
        }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .custom-link {
            color: var(--primary-blue);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .custom-link:hover {
            color: var(--primary-green);
        }

        /* Mobile Responsiveness */
        @media (max-width: 640px) {
            .custom-card {
                padding: 1.5rem;
                margin: 1rem;
                max-width: 100%;
            }

            .form-footer {
                flex-direction: column;
                gap: 1rem;
            }

            .custom-button {
                width: 100%;
            }

            .remember-me {
                width: 100%;
                justify-content: center;
            }

            .custom-logo {
                width: 60px;
                height: 60px;
            }

            .logo-text {
                font-size: 1.25rem;
            }
        }

        /* Additional Mobile Optimizations */
        @media (max-width: 480px) {
            .custom-card {
                padding: 1rem;
            }

            .custom-title {
                font-size: 1.25rem;
            }

            .custom-subtitle {
                font-size: 0.875rem;
            }
        }

        /* Fix for very small screens */
        @media (max-width: 320px) {
            .custom-container {
                padding: 10px;
            }
        }
    </style>
@endsection
