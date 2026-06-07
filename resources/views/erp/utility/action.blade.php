@extends('erp.layout')

@section('title', $details['title'])
@section('page-title', $details['title'])

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $details['title'] }}</h1>
            <p class="text-muted mb-0">{{ $details['subtitle'] }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ $details['primary']['route'] }}" class="btn btn-primary"><i class="bi {{ $details['primary']['icon'] }} me-1"></i>{{ $details['primary']['label'] }}</a>
            <a href="{{ $details['secondary']['route'] }}" class="btn btn-outline-secondary"><i class="bi {{ $details['secondary']['icon'] }} me-1"></i>{{ $details['secondary']['label'] }}</a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($details['cards'] as $card)
            <div class="col-md-3">
                <div class="erp-card p-3 h-100">
                    <div class="text-muted small">{{ $card[0] }}</div>
                    <div class="h4 fw-bold mb-0">{{ $card[1] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Workspace Records</h2>
                        <small class="text-muted">Live-ready table for {{ strtolower($details['title']) }}</small>
                    </div>
                    <a href="{{ route('erp.export', $slug) }}" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle action-table w-100">
                        <thead>
                        <tr>
                            @foreach($details['columns'] as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($details['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Process Checklist</h2>
                <div class="list-group list-group-flush">
                    @foreach($details['steps'] as $step)
                        <div class="list-group-item px-0 d-flex gap-2">
                            <i class="bi bi-check-circle text-success"></i>
                            <span>{{ $step }}</span>
                        </div>
                    @endforeach
                </div>
                <hr>
                <h3 class="h6 fw-bold mb-3">Controls</h3>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge badge-soft py-2 px-3">Role-based access</span>
                    <span class="badge badge-soft py-2 px-3">Approval workflow</span>
                    <span class="badge badge-soft py-2 px-3">Audit trail</span>
                    <span class="badge badge-soft py-2 px-3">PDF / Excel ready</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.action-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search records' }
    });
</script>
@endpush
