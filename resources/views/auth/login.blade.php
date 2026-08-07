@extends('layouts.app')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="col-md-5 col-lg-4">
        <div class="studio-card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                    <i class="bi bi-shield-lock-fill fs-2"></i>
                </div>
                <h4 class="fw-bold text-white">منصة تصميم الكتب الذكية</h4>
                <p class="text-secondary small">سجل الدخول للوصول إلى بيئة عمل التصميم</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control studio-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold">كلمة المرور</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" class="form-control studio-input @error('password') is-invalid @enderror" required>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label text-secondary small" for="remember">تذكرني على هذا الجهاز</label>
                </div>

                <button type="submit" class="btn btn-indigo w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i> دخول
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
