@extends('layouts.app')

@section('title', 'Sign Up - Hospital Management System')

@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card" style="max-width:420px; width:100%;">
        <div class="card-body p-5">
            <div class="text-center mb-3">
                <a href="{{ url('/') }}" class="mb-4 d-inline-block">
                    <img src="{{ asset('assets/images/logo-icon.svg') }}" alt="" width="36">
                    <span class="ms-2"><img src="{{ asset('assets/images/logo.svg') }}" alt=""></span>
                </a>
                <h1 class="card-title mb-5 h5">Create your account</h1>
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

            <form class="needs-validation mt-3" method="POST" action="{{ route('register') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" placeholder="John Doe" required autofocus value="{{ old('name') }}">
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required value="{{ old('email') }}">
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required minlength="8">
                    <div class="invalid-feedback">Password must be at least 8 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" placeholder="Confirm Password" required>
                    <div class="invalid-feedback">Please confirm your password.</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input id="terms" class="form-check-input" type="checkbox" required>
                        <label class="form-check-label small" for="terms">I agree to the <a href="#">Terms</a></label>
                    </div>
                </div>

                <button class="btn btn-primary w-100" type="submit">Sign up</button>
            </form>

            <div class="text-center mt-3 small text-muted">
                Already have an account? <a href="{{ route('login') }}" class="link-primary">Sign in</a>
            </div>
        </div>
    </div>
</div>
@endsection
