@extends('layouts.app')

@section('title', 'Sign In - Hospital Management System')

@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card" style="max-width:420px; width:100%;">
        <div class="card-body p-5">
            <div class="text-center mb-3">
                <a href="{{ url('/') }}" class="mb-4 d-inline-block">
                    <img src="{{ asset('assets/images/logo-icon.svg') }}" alt="" width="36">
                    <span class="ms-2"><img src="{{ asset('assets/images/logo.svg') }}" alt=""></span>
                </a>
                <h1 class="card-title mb-5 h5">Sign in to your account</h1>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="needs-validation mt-3" method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required autofocus value="{{ old('email') }}">
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label d-flex justify-content-between">
                        <span>Password</span>
                        <a href="#" class="small link-primary">Forgot Password?</a>
                    </label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required minlength="6">
                    <div class="invalid-feedback">Please provide a password (min 6 characters).</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input id="remember" name="remember" class="form-check-input" type="checkbox">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                </div>

                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>

            <div class="text-center mt-3 small text-muted">
                Don't have an account? <a href="{{ route('register') }}" class="link-primary">Sign up</a>
            </div>
        </div>
    </div>
</div>
@endsection
