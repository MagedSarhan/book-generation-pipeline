@extends('layouts.app')

@section('title', 'إعدادات النظام')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm me-3 text-white rounded-circle">
                    <i class="bi bi-arrow-right"></i>
                </a>
                <div>
                    <h3 class="fw-bold text-white mb-0"><i class="bi bi-sliders text-primary me-2"></i> إعدادات المنصة الافتراضية</h3>
                    <p class="text-secondary small mb-0">ضبط القيم الافتراضية للتوليد والتحكم في تزامن الطلبات</p>
                </div>
            </div>

            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                
                <div class="studio-card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-gear-wide-connected text-info me-2"></i> إعدادات التوليد الافتراضية</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">الجودة الافتراضية (Quality)</label>
                            <select name="default_quality" class="form-select studio-select">
                                <option value="high" {{ $settings['default_quality'] === 'high' ? 'selected' : '' }}>عالية (High)</option>
                                <option value="medium" {{ $settings['default_quality'] === 'medium' ? 'selected' : '' }}>متوسطة (Medium)</option>
                                <option value="low" {{ $settings['default_quality'] === 'low' ? 'selected' : '' }}>منخفضة (Low)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">الدقة الافتراضية (Resolution)</label>
                            <select name="default_resolution" class="form-select studio-select">
                                <option value="auto" {{ $settings['default_resolution'] === 'auto' ? 'selected' : '' }}>تلقائي (Auto)</option>
                                <option value="a4_draft" {{ $settings['default_resolution'] === 'a4_draft' ? 'selected' : '' }}>A4 مسودة (1024x1440)</option>
                                <option value="a4_high" {{ $settings['default_resolution'] === 'a4_high' ? 'selected' : '' }}>A4 عالية (1664x2352)</option>
                                <option value="a4_max" {{ $settings['default_resolution'] === 'a4_max' ? 'selected' : '' }}>A4 أقصى جودة (2400x3392)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">صيغة المخرجات (Output Format)</label>
                            <select name="default_output_format" class="form-select studio-select">
                                <option value="png" {{ $settings['default_output_format'] === 'png' ? 'selected' : '' }}>PNG</option>
                                <option value="jpeg" {{ $settings['default_output_format'] === 'jpeg' ? 'selected' : '' }}>JPEG</option>
                                <option value="webp" {{ $settings['default_output_format'] === 'webp' ? 'selected' : '' }}>WebP</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">حجم الدفعة الافتراضي (Batch Size)</label>
                            <input type="number" name="default_batch_size" class="form-control studio-input" value="{{ $settings['default_batch_size'] }}" min="1" max="100">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">الحد الأقصى للتنويعات (Max Variants)</label>
                            <input type="number" name="max_variants" class="form-control studio-input" value="{{ $settings['max_variants'] }}" min="1" max="4">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">تزامن التوليد النشط (Active Concurrency - FAL_MAX_ACTIVE_REQUESTS)</label>
                            <input type="number" name="concurrency" class="form-control studio-input" value="{{ $settings['concurrency'] }}" min="1" max="10">
                            <small class="text-muted fs-8 d-block mt-1">حدد عدد الطلبات المتزامنة النشطة المرسلة إلى المزود في نفس الوقت لتجنب إجهاد الخادم وحساب API.</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4 py-2">إلغاء</a>
                    <button type="submit" class="btn btn-indigo px-5 py-2">
                        <i class="bi bi-save me-2"></i> حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
