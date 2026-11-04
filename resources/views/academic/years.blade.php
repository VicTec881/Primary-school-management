@extends('layouts.app')

@section('title', 'Academic Years & Terms')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">Academic Years & Term Schedules</h4>
    <p class="text-muted small mb-0">Configure school terms, opening dates, and current active period.</p>
</div>

<div class="row g-4">
    @foreach($years as $year)
        <div class="col-lg-6">
            <div class="card h-100 {{ $year->is_current ? 'border-primary shadow-sm' : '' }}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Academic Year {{ $year->name }}</h5>
                        <small class="text-muted">{{ $year->start_date->format('d M Y') }} &mdash; {{ $year->end_date->format('d M Y') }}</small>
                    </div>
                    <div>
                        @if($year->is_current)
                            <span class="badge bg-primary px-3 py-2">Current Active Year</span>
                        @else
                            <form action="{{ route('academic.year.set-current', $year->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Set Current</button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Term</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Next Term Begins</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($year->terms as $term)
                                    <tr>
                                        <td class="fw-bold">{{ $term->name }}</td>
                                        <td>{{ $term->start_date->format('d M Y') }}</td>
                                        <td>{{ $term->end_date->format('d M Y') }}</td>
                                        <td>{{ $term->next_term_begins ? $term->next_term_begins->format('d M Y') : 'TBD' }}</td>
                                        <td>
                                            @if($term->is_current)
                                                <span class="badge bg-success">Current Term</span>
                                            @else
                                                <form action="{{ route('academic.term.set-current', $term->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;">Set Current</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

