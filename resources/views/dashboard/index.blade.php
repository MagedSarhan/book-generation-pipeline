@extends('layouts.app')

@section('title', 'المشاريع')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="bi bi-collection-fill text-primary me-2"></i> مشاريع التصميم</h3>
            <p class="text-secondary small mb-0">إدارة وإعادة تصميم الكتب والصفحات بالذكاء الاصطناعي</p>
        </div>
        <a href="{{ route('projects.create') }}" class="btn btn-indigo px-4 py-2">
            <i class="bi bi-plus-lg me-2"></i> إنشاء مشروع جديد
        </a>
    </div>

    @if($projects->isEmpty())
        <div class="text-center py-5 studio-card my-4">
            <div class="py-4">
                <i class="bi bi-journal-plus text-secondary display-1"></i>
                <h4 class="text-white mt-3 fw-bold">لا توجد مشاريع حتى الآن</h4>
                <p class="text-secondary mb-4">ابدأ برفع كتاب PDF أو DOCX أو مجموعة صور لإنشاء مشروعك الأول</p>
                <a href="{{ route('projects.create') }}" class="btn btn-indigo px-4 py-2">
                    <i class="bi bi-plus-lg me-2"></i> بدء مشروع جديد
                </a>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($projects as $project)
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="studio-card h-100 d-flex flex-column">
                        <div class="studio-card-header d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-25">
                                {{ strtoupper($project->source_type) }}
                            </span>
                            <small class="text-muted fs-7"><i class="bi bi-clock me-1"></i> {{ $project->updated_at->diffForHumans() }}</small>
                        </div>
                        
                        <div class="p-3 flex-grow-1">
                            <h5 class="fw-bold text-white mb-2 text-truncate" title="{{ $project->name }}">{{ $project->name }}</h5>
                            <p class="text-secondary small mb-3 text-truncate-2" style="min-height: 40px;">
                                {{ $project->description ?: 'لا يوجد وصف' }}
                            </p>

                            <!-- Stats & Progress -->
                            @php
                                $total = $project->total_pages ?: 1;
                                $progress = round(($project->completed_pages / $total) * 100);
                            @endphp
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between text-secondary small mb-1">
                                    <span>التقدم الإجمالي</span>
                                    <span class="fw-bold text-info">{{ $progress }}%</span>
                                </div>
                                <div class="progress bg-dark" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>

                            <div class="row g-2 text-center small mb-3">
                                <div class="col-4">
                                    <div class="bg-dark bg-opacity-50 p-2 rounded border border-secondary border-opacity-10">
                                        <div class="text-secondary fs-7">الصفحات</div>
                                        <div class="fw-bold text-white">{{ $project->total_pages }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-dark bg-opacity-50 p-2 rounded border border-secondary border-opacity-10">
                                        <div class="text-secondary fs-7">مكتملة</div>
                                        <div class="fw-bold text-success">{{ $project->completed_pages }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-dark bg-opacity-50 p-2 rounded border border-secondary border-opacity-10">
                                        <div class="text-secondary fs-7">جاري</div>
                                        <div class="fw-bold text-warning">{{ $project->generating_pages }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 border-top border-secondary border-opacity-25 d-flex gap-2">
                            <a href="{{ route('projects.show', $project->uuid) }}" class="btn btn-indigo btn-sm flex-grow-1 py-2">
                                <i class="bi bi-folder-symlink me-1"></i> فتح بيئة العمل
                            </a>
                            
                            <form action="{{ route('projects.destroy', $project->uuid) }}" method="POST" onsubmit="return confirm('هل أنت تأكد من رغبتك في حذف هذا المشروع نهائياً؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm p-2" title="حذف المشروع">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
