@extends('layouts.app')

@section('title', 'إنشاء مشروع جديد')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm me-3 text-white rounded-circle">
                    <i class="bi bi-arrow-right"></i>
                </a>
                <div>
                    <h3 class="fw-bold text-white mb-0">معالج إنشاء مشروع كتاب جديد</h3>
                    <p class="text-secondary small mb-0">قم برفع المستند وتحديد الهوية البصرية وإعدادات التوليد</p>
                </div>
            </div>

            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Section 1: Project Metadata -->
                <div class="studio-card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-info-circle text-primary me-2"></i>1. معلومات المشروع الأساسية</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">اسم المشروع <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control studio-input" placeholder="مثال: كتاب الذكاء الاصطناعي - الطبعة الثانية" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">وصف المشروع</label>
                            <input type="text" name="description" class="form-control studio-input" placeholder="وصف مختصر لموضوع الكتاب أو الهوية">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold">الموجه البصري العام للمشروع (Master Prompt)</label>
                            <textarea name="master_prompt" rows="3" class="form-control studio-input" placeholder="اكتب التعليمات البصرية العامة للكتاب (مثال: تصميم مجلة عصري، ألوان دافئة، خطوط عربية أنيقة، تخطيط متناسق بدون ازدحام)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Source File Upload -->
                <div class="studio-card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-file-earmark-arrow-up text-info me-2"></i>2. رفع مستند الكتاب المصدر</h5>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">نوع المصدر <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source_type" id="src_pdf" value="pdf" checked onchange="toggleSourceInput('pdf')">
                                <label class="form-check-label text-white" for="src_pdf">مستند PDF</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source_type" id="src_docx" value="docx" onchange="toggleSourceInput('docx')">
                                <label class="form-check-label text-white" for="src_docx">مستند Word (DOCX)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source_type" id="src_img" value="images" onchange="toggleSourceInput('images')">
                                <label class="form-check-label text-white" for="src_img">صور صفحات متعدة (PNG/JPG)</label>
                            </div>
                        </div>
                    </div>

                    <div id="single_file_container" class="mb-3">
                        <label class="form-label text-secondary small fw-bold">ملف المستند <span class="text-danger">*</span></label>
                        <input type="file" name="source_file" id="source_file_input" class="form-control studio-input" accept=".pdf,.docx">
                    </div>

                    <div id="multiple_images_container" class="mb-3 d-none">
                        <label class="form-label text-secondary small fw-bold">صور الصفحات (حدد جميع الصور مرتبة) <span class="text-danger">*</span></label>
                        <input type="file" name="source_images[]" id="source_images_input" class="form-control studio-input" accept="image/*" multiple>
                    </div>
                </div>

                <!-- Section 3: Visual Style References -->
                <div class="studio-card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-palette text-warning me-2"></i>3. المراجع البصرية والهوية (Style References)</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">صورة الهوية البصرية الرئيسية (Master Style) <span class="text-danger">*</span></label>
                            <input type="file" name="master_style" class="form-control studio-input" accept="image/*" required>
                            <small class="text-muted d-block mt-1">تحدد هذه الصورة النمط البصري العام والألوان المستخدمة في جميع الصفحات.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">مراجع إضافية (اختياري)</label>
                            <input type="file" name="extra_references[]" class="form-control studio-input" accept="image/*" multiple>
                            <small class="text-muted d-block mt-1">يمكنك إرفاق صور إضافية كمرجع لتصميم الفصل أو الأشكال البيانية.</small>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Default Generation Settings -->
                <div class="studio-card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3"><i class="bi bi-sliders text-success me-2"></i>4. إعدادات التوليد الافتراضية</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label text-secondary small fw-bold">جودة التوليد (Quality)</label>
                            <select name="default_quality" class="form-select studio-select">
                                <option value="high" selected>عالية (High)</option>
                                <option value="medium">متوسطة (Medium)</option>
                                <option value="low">منخفضة (Low)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-secondary small fw-bold">الدقة (Resolution)</label>
                            <select name="default_resolution" class="form-select studio-select" onchange="checkCustomRes(this.value)">
                                <option value="auto" selected>تلقائي (Auto)</option>
                                <option value="a4_draft">A4 مسودة (1024x1440)</option>
                                <option value="a4_high">A4 عالية (1664x2352)</option>
                                <option value="a4_max">A4 أقصى جودة (2400x3392)</option>
                                <option value="custom">مخصص (Custom)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-secondary small fw-bold">صيغة الملف (Format)</label>
                            <select name="default_output_format" class="form-select studio-select">
                                <option value="png" selected>PNG</option>
                                <option value="jpeg">JPEG</option>
                                <option value="webp">WebP</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-secondary small fw-bold">عدد التنويعات (Variants)</label>
                            <select name="default_variants" class="form-select studio-select">
                                <option value="1" selected>تنويعة واحدة (1)</option>
                                <option value="2">تنويعتان (2)</option>
                                <option value="3">3 تنويعات</option>
                                <option value="4">4 تنويعات</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">حجم الدفعة الافتراضي (Batch Size)</label>
                            <input type="number" name="default_batch_size" class="form-control studio-input" value="10" min="1" max="100">
                        </div>

                        <div id="custom_res_box" class="col-12 row g-3 d-none mt-2">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">العرض (Pixel Width - مضاعفات 16)</label>
                                <input type="number" name="custom_width" class="form-control studio-input" value="1024" step="16">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">الارتفاع (Pixel Height - مضاعفات 16)</label>
                                <input type="number" name="custom_height" class="form-control studio-input" value="1440" step="16">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4 py-2">إلغاء</a>
                    <button type="submit" class="btn btn-indigo px-5 py-2">
                        <i class="bi bi-check-lg me-2"></i> إنشاء المشروع واستيراد الصفحات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleSourceInput(type) {
    const single = document.getElementById('single_file_container');
    const multiple = document.getElementById('multiple_images_container');
    const fileInput = document.getElementById('source_file_input');
    
    if (type === 'images') {
        single.classList.add('d-none');
        multiple.classList.remove('d-none');
        fileInput.removeAttribute('required');
    } else {
        single.classList.remove('d-none');
        multiple.classList.add('d-none');
        fileInput.setAttribute('required', 'required');
        fileInput.accept = type === 'pdf' ? '.pdf' : '.docx';
    }
}

function checkCustomRes(val) {
    const box = document.getElementById('custom_res_box');
    if (val === 'custom') {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}
</script>
@endpush
