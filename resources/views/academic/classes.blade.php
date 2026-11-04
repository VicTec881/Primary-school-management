@extends('layouts.app')

@section('title', 'Grades & Streams')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Grades & Streams (Class Structure)</h4>
        <p class="text-muted small mb-0">Active Academic Year: <strong>{{ $currentYear?->name ?? '2026' }}</strong></p>
    </div>
</div>

<div class="row g-4">
    @foreach($grades as $grade)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-top border-4 border-primary">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">{{ $grade->name }}</h5>
                        <small class="text-muted">{{ $grade->stage }}</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary fw-bold">{{ $grade->code }}</span>
                </div>
                <div class="card-body p-3">
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Streams / Classes:</h6>
                    <div class="list-group list-group-flush">
                        @foreach($grade->streams as $stream)
                            @php
                                $classTeacher = $stream->classTeachers->first()?->teacher;
                                $enrolledCount = $stream->enrollments->count();
                            @endphp
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <a href="{{ route('academic.class.show', $stream->id) }}" class="fw-bold text-decoration-none">
                                        Stream {{ $stream->name }}
                                    </a>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-person-badge me-1"></i>{{ $classTeacher?->name ?? 'No class teacher' }}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge {{ $enrolledCount >= $stream->capacity ? 'bg-danger' : 'bg-light text-dark border' }}">
                                        {{ $enrolledCount }} / {{ $stream->capacity }} Learners
                                    </span>
                                    <a href="{{ route('academic.class.show', $stream->id) }}" class="btn btn-sm btn-outline-primary ms-2 py-0 px-2" style="font-size: 0.75rem;">View</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

