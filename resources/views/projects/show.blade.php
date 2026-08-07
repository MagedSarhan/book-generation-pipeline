@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="workspace-layout">
    <!-- RIGHT SIDEBAR: Page Navigator (RTL) -->
    <div class="studio-card d-flex flex-column h-100 p-3">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary border-opacity-25">
            <div>
                <h6 class="fw-bold text-white mb-0 text-truncate" style="max-width: 180px;">{{ $project->name }}</h6>
                <small class="text-secondary fs-7"><span id="pages_completed_count">{{ $project->pages->where('status', 'completed')->count() }}</span> / <span id="pages_total_count">{{ $project->pages->count() }}</span> صفحة مكتملة</small>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm p-1 rounded-circle text-white" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('projects.export.zip', $project->uuid) }}"><i class="bi bi-file-earmark-zip me-2"></i> تحميل الكل (ZIP)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteProject()"><i class="bi bi-trash me-2"></i> حذف المشروع</a></li>
                </ul>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="btn-group btn-group-sm w-100 mb-3" role="group">
            <button type="button" class="btn btn-outline-secondary active text-white" onclick="filterPages('all', this)">الكل</button>
            <button type="button" class="btn btn-outline-secondary text-white" onclick="filterPages('completed', this)">مكتملة</button>
            <button type="button" class="btn btn-outline-secondary text-white" onclick="filterPages('generating', this)">جاري</button>
            <button type="button" class="btn btn-outline-secondary text-white" onclick="filterPages('failed', this)">فشلت</button>
        </div>

        <!-- Page Search -->
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control studio-input" placeholder="انتقل إلى رقم الصفحة..." onkeyup="searchPage(this.value)">
        </div>

        <!-- Thumbnail Grid -->
        <div class="thumb-grid flex-grow-1" id="thumb_grid">
            @foreach($project->pages as $page)
                @php
                    $isCompleted = $page->status === 'completed';
                    $hasVersion = $page->selectedVersion;
                    $displayImg = $hasVersion ? route('asset.direct', ['path' => $page->selectedVersion->image_path]) : route('asset.direct', ['path' => $page->thumbnail_path ?: $page->source_image_path]);
                @endphp
                <div class="thumb-card page-item-card {{ $loop->first ? 'active' : '' }}" 
                     id="page_card_{{ $page->id }}" 
                     data-page-id="{{ $page->id }}"
                     data-page-number="{{ $page->page_number }}"
                     data-status="{{ $page->status }}"
                     onclick="selectPage({{ $page->id }})">
                     
                    <img src="{{ $displayImg }}" class="thumb-img" alt="صفحة {{ $page->page_number }}">
                    
                    <div class="position-absolute top-0 start-0 m-1">
                        <span class="badge bg-dark bg-opacity-75 text-white fs-7 border border-secondary border-opacity-25">
                            {{ $page->page_number }}
                        </span>
                    </div>

                    <div class="position-absolute bottom-0 w-100 p-1 text-center bg-dark bg-opacity-75 border-top border-secondary border-opacity-25">
                        <span class="badge-status badge-{{ $page->status }}" id="badge_status_{{ $page->id }}">
                            @switch($page->status)
                                @case('completed') مكتملة @break
                                @case('generating') جاري التوليد @break
                                @case('queued') في الانتظار @break
                                @case('submitted') مرسلة @break
                                @case('failed') فشلت @break
                                @default مستوردة
                            @endswitch
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- CENTER PANEL: Main Canvas & ChatGPT-style Composer -->
    <div class="canvas-container">
        <!-- Canvas Top Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-2 border-bottom border-secondary border-opacity-25 bg-dark bg-opacity-50">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary btn-sm text-white" onclick="prevPage()"><i class="bi bi-chevron-right"></i> الصفحة السابقة</button>
                <span class="text-white fw-bold px-2">الصفحة <span id="current_page_num">1</span> من {{ $project->pages->count() }}</span>
                <button class="btn btn-outline-secondary btn-sm text-white" onclick="nextPage()">الصفحة التالية <i class="bi bi-chevron-left"></i></button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Toggle Before / After -->
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="preview_mode" id="prev_mode_gen" value="generated" checked onchange="togglePreviewMode('generated')">
                    <label class="btn btn-outline-info text-white" for="prev_mode_gen">التصميم الجديد</label>

                    <input type="radio" class="btn-check" name="preview_mode" id="prev_mode_src" value="source" onchange="togglePreviewMode('source')">
                    <label class="btn btn-outline-secondary text-white" for="prev_mode_src">المستند الأصلي</label>
                </div>

                <!-- Version Switcher -->
                <select class="form-select form-select-sm studio-select text-white" id="version_select" onchange="switchVersion(this.value)" style="width: auto;">
                    <option value="">نسخة المصدر الأصلي</option>
                </select>

                <a id="btn_download_page" href="#" class="btn btn-outline-light btn-sm" title="تحميل الصفحة"><i class="bi bi-download"></i></a>
                <button class="btn btn-outline-warning btn-sm" onclick="triggerRegenerate()" title="إعادة توليد هذه الصفحة"><i class="bi bi-arrow-repeat"></i></button>
            </div>
        </div>

        <!-- Canvas Main Preview Area -->
        <div class="canvas-preview-area" id="canvas_area">
            <div class="text-center" id="canvas_loader" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-secondary mt-2 small">جاري التوليد...</div>
            </div>
            
            <img id="main_canvas_image" src="" class="main-preview-image shadow-lg" alt="معاينة الصفحة">
        </div>

        <!-- ChatGPT-Style Bottom Instruction Composer -->
        <div class="chat-composer-bar">
            <!-- Parameter Control Chips -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                <!-- Quality Chips -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small fw-bold">الجودة:</span>
                    <div class="btn-group btn-group-sm quality-selector-group" role="group">
                        <input type="radio" class="btn-check" name="composer_quality" id="q_low" value="low">
                        <label class="btn btn-outline-secondary text-white py-1 px-2" for="q_low">منخفضة</label>

                        <input type="radio" class="btn-check" name="composer_quality" id="q_medium" value="medium">
                        <label class="btn btn-outline-secondary text-white py-1 px-2" for="q_medium">متوسطة</label>

                        <input type="radio" class="btn-check" name="composer_quality" id="q_high" value="high" checked>
                        <label class="btn btn-outline-secondary text-white py-1 px-2" for="q_high">عالية (High)</label>
                    </div>
                </div>

                <!-- Resolution & Format Chips -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small fw-bold">الدقة:</span>
                    <select id="composer_resolution" class="form-select form-select-sm studio-select py-1 text-white" style="width: auto;">
                        <option value="auto" selected>تلقائي (Auto)</option>
                        <option value="a4_draft">A4 مسودة (1024x1440)</option>
                        <option value="a4_high">A4 عالية (1664x2352)</option>
                        <option value="a4_max">A4 أقصى جودة (2400x3392)</option>
                    </select>

                    <span class="text-secondary small fw-bold ms-2">الصيغة:</span>
                    <select id="composer_format" class="form-select form-select-sm studio-select py-1 text-white" style="width: auto;">
                        <option value="png" selected>PNG</option>
                        <option value="jpeg">JPEG</option>
                        <option value="webp">WebP</option>
                    </select>

                    <span class="text-secondary small fw-bold ms-2">تنوع:</span>
                    <select id="composer_variants" class="form-select form-select-sm studio-select py-1 text-white" style="width: auto;">
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>
            </div>

            <!-- Composer Input & Action Trigger -->
            <div class="input-group">
                <textarea id="composer_prompt" class="form-control studio-input" rows="2" placeholder="اكتب تعليمات التصميم للصفحة الحالية أو اختر دفعة التوليد (مثال: صمم هذه الصفحة بأسلوب الهوية، كبّر العنوان، كمل 10 صفحات التالية...)"></textarea>
                
                <div class="d-flex flex-column gap-1 p-1 bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-start">
                    <button class="btn btn-indigo px-4 h-100 d-flex align-items-center gap-2" onclick="submitGeneration('current')">
                        <i class="bi bi-stars"></i>
                        <span>توليد الآن</span>
                    </button>
                </div>
            </div>

            <!-- Quick Batch Buttons -->
            <div class="d-flex align-items-center gap-2 mt-2 pt-1">
                <span class="text-secondary fs-7 fw-bold">توليد متتابع:</span>
                <button class="btn btn-outline-secondary btn-sm text-white fs-7 py-0 px-2" onclick="submitGeneration('next_10')"><i class="bi bi-play-fill"></i> 10 صفحات التالية</button>
                <button class="btn btn-outline-secondary btn-sm text-white fs-7 py-0 px-2" onclick="submitGeneration('next_20')"><i class="bi bi-play-fill"></i> 20 صفحة التالية</button>
                <button class="btn btn-outline-secondary btn-sm text-white fs-7 py-0 px-2" onclick="submitGeneration('next_50')"><i class="bi bi-play-fill"></i> 50 صفحة التالية</button>
                <button class="btn btn-outline-primary btn-sm fs-7 py-0 px-2" onclick="submitGeneration('all_remaining')"><i class="bi bi-fast-forward-fill me-1"></i> صمم كل الصفحات المتبقية</button>
            </div>
        </div>
    </div>

    <!-- LEFT PANEL: Project References & Batch Status -->
    <div class="secondary-panel studio-card d-flex flex-column h-100 p-3">
        <h6 class="fw-bold text-white mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="bi bi-layers text-info me-2"></i> المراجع والتحكم في الدفعات
        </h6>

        <!-- Active Batch Progress Card -->
        <div id="batch_progress_card" class="bg-dark p-3 rounded border border-secondary border-opacity-25 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-white small">الدفعة الحالية</span>
                <span id="batch_status_badge" class="badge bg-primary">نشطة</span>
            </div>
            <div class="progress bg-secondary bg-opacity-25 mb-2" style="height: 8px;">
                <div id="batch_progress_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 0%"></div>
            </div>
            <div class="d-flex justify-content-between text-secondary fs-7">
                <span>المكتمل: <strong id="batch_completed_txt" class="text-white">0</strong></span>
                <span>الفاشل: <strong id="batch_failed_txt" class="text-danger">0</strong></span>
                <span>الإجمالي: <strong id="batch_total_txt" class="text-white">0</strong></span>
            </div>
            
            <div class="d-flex gap-2 mt-3">
                <button id="btn_pause_batch" class="btn btn-outline-warning btn-sm flex-grow-1" onclick="pauseBatch()"><i class="bi bi-pause-fill"></i> إيقاف مؤقت</button>
                <button id="btn_resume_batch" class="btn btn-outline-success btn-sm flex-grow-1 d-none" onclick="resumeBatch()"><i class="bi bi-play-fill"></i> استئناف</button>
            </div>
        </div>

        <!-- Master Style Reference Preview -->
        <div class="mb-3">
            <label class="text-secondary small fw-bold mb-2">الهوية البصرية الرئيسية للمشروع:</label>
            @if($project->masterStyleReference)
                <div class="position-relative rounded border border-secondary border-opacity-25 overflow-hidden">
                    <img src="{{ route('asset.direct', ['path' => $project->masterStyleReference->image_path]) }}" class="w-100" style="height: 140px; object-fit: cover;" alt="Master Style">
                    <span class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-75 text-white text-center fs-7 py-1">Master Style Reference</span>
                </div>
            @else
                <div class="text-muted small">لم يتم تعيين مرجع رئيسي</div>
            @endif
        </div>

        <!-- Conversation Logs -->
        <div class="flex-grow-1 overflow-auto border border-secondary border-opacity-25 rounded p-2 bg-dark bg-opacity-50">
            <div class="text-secondary fs-7 fw-bold mb-2"><i class="bi bi-chat-left-text me-1"></i> سجل المحادثات والتعليمات</div>
            <div id="conversation_log" class="d-flex flex-column gap-2">
                @foreach($conversation->messages as $msg)
                    <div class="bg-secondary bg-opacity-10 p-2 rounded border border-secondary border-opacity-10 fs-7 text-secondary">
                        <div class="text-white fw-bold mb-1">{{ $msg->body }}</div>
                        <div class="text-muted fs-8">{{ $msg->created_at->format('H:i - Y/m/d') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let pagesData = @json($project->pages);
let currentPageId = pagesData.length > 0 ? pagesData[0].id : null;
let currentPreviewMode = 'generated';
let currentBatchId = null;
let pollInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    if (currentPageId) {
        selectPage(currentPageId);
    }
    startStatusPolling();
});

function selectPage(pageId) {
    currentPageId = pageId;
    
    // Highlight thumbnail
    document.querySelectorAll('.page-item-card').forEach(el => el.classList.remove('active'));
    const card = document.getElementById(`page_card_${pageId}`);
    if (card) card.classList.add('active');

    const page = pagesData.find(p => p.id === pageId);
    if (!page) return;

    document.getElementById('current_page_num').innerText = page.page_number;

    // Update Version Selector Dropdown
    const vSelect = document.getElementById('version_select');
    vSelect.innerHTML = '<option value="">نسخة المصدر الأصلي</option>';

    if (page.versions && page.versions.length > 0) {
        page.versions.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.innerText = `النسخة V${v.version_number} ${v.is_selected ? '(المختارة)' : ''}`;
            if (v.is_selected) opt.selected = true;
            vSelect.appendChild(opt);
        });
    }

    updateCanvasImage();
}

function updateCanvasImage() {
    const page = pagesData.find(p => p.id === currentPageId);
    if (!page) return;

    const imgEl = document.getElementById('main_canvas_image');
    const dlBtn = document.getElementById('btn_download_page');

    if (currentPreviewMode === 'source' || !page.selected_version) {
        const srcUrl = `/assets/direct?path=${encodeURIComponent(page.source_image_path)}`;
        imgEl.src = srcUrl;
        dlBtn.href = `/pages/${page.id}/download`;
    } else {
        const genUrl = `/assets/direct?path=${encodeURIComponent(page.selected_version.image_path)}`;
        imgEl.src = genUrl;
        dlBtn.href = `/versions/${page.selected_version.id}/download`;
    }
}

function togglePreviewMode(mode) {
    currentPreviewMode = mode;
    updateCanvasImage();
}

function prevPage() {
    const idx = pagesData.findIndex(p => p.id === currentPageId);
    if (idx > 0) selectPage(pagesData[idx - 1].id);
}

function nextPage() {
    const idx = pagesData.findIndex(p => p.id === currentPageId);
    if (idx < pagesData.length - 1) selectPage(pagesData[idx + 1].id);
}

function submitGeneration(actionType) {
    const prompt = document.getElementById('composer_prompt').value;
    const quality = document.querySelector('input[name="composer_quality"]:checked').value;
    const resolution = document.getElementById('composer_resolution').value;
    const format = document.getElementById('composer_format').value;
    const variants = document.getElementById('composer_variants').value;

    fetch("{{ route('projects.generate', $project->uuid) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            prompt: prompt,
            action_type: actionType,
            current_page_id: currentPageId,
            quality: quality,
            resolution: resolution,
            output_format: format,
            num_images: parseInt(variants)
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            currentBatchId = data.batch_id;
            document.getElementById('composer_prompt').value = '';
            fetchStatus();
        }
    })
    .catch(err => console.error(err));
}

function triggerRegenerate() {
    if (!currentPageId) return;
    fetch(`/pages/${currentPageId}/regenerate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) fetchStatus();
    });
}

function startStatusPolling() {
    fetchStatus();
    pollInterval = setInterval(fetchStatus, 3000);
}

function fetchStatus() {
    fetch("{{ route('projects.status', $project->uuid) }}")
    .then(r => r.json())
    .then(data => {
        document.getElementById('pages_completed_count').innerText = data.completed;
        document.getElementById('pages_total_count').innerText = data.total;

        // Update pages array & status badges
        data.pages.forEach(p => {
            const local = pagesData.find(item => item.id === p.id);
            if (local) {
                local.status = p.status;
                local.selected_version = p.selected_version;
            }

            const badge = document.getElementById(`badge_status_${p.id}`);
            if (badge) {
                badge.className = `badge-status badge-${p.status}`;
                badge.innerText = p.status === 'completed' ? 'مكتملة' : (p.status === 'generating' ? 'جاري التوليد' : p.status);
            }
        });

        // Batch Progress Update
        if (data.active_batch) {
            currentBatchId = data.active_batch.id;
            document.getElementById('batch_total_txt').innerText = data.active_batch.total;
            document.getElementById('batch_completed_txt').innerText = data.active_batch.completed;
            document.getElementById('batch_failed_txt').innerText = data.active_batch.failed;

            const pct = data.active_batch.total > 0 ? Math.round((data.active_batch.completed / data.active_batch.total) * 100) : 0;
            document.getElementById('batch_progress_bar').style.width = `${pct}%`;
        }

        updateCanvasImage();
    })
    .catch(err => console.error(err));
}

function pauseBatch() {
    if (!currentBatchId) return;
    fetch(`/batches/${currentBatchId}/pause`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        document.getElementById('btn_pause_batch').classList.add('d-none');
        document.getElementById('btn_resume_batch').classList.remove('d-none');
    });
}

function resumeBatch() {
    if (!currentBatchId) return;
    fetch(`/batches/${currentBatchId}/resume`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        document.getElementById('btn_resume_batch').classList.add('d-none');
        document.getElementById('btn_pause_batch').classList.remove('d-none');
    });
}

function filterPages(status, btn) {
    document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.page-item-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function searchPage(num) {
    if (!num) {
        document.querySelectorAll('.page-item-card').forEach(card => card.style.display = 'block');
        return;
    }
    document.querySelectorAll('.page-item-card').forEach(card => {
        card.style.display = card.dataset.pageNumber.includes(num) ? 'block' : 'none';
    });
}

function deleteProject() {
    if (confirm('هل أنت متاكد من رغبتك في حذف هذا المشروع بجميع ملفاته وصفحاته؟')) {
        fetch("{{ route('projects.destroy', $project->uuid) }}", {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(() => window.location.href = "{{ route('dashboard') }}");
    }
}
</script>
@endpush
