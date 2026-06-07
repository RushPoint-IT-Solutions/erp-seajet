@extends('erp.layout')

@section('title', 'Export '.$type)
@section('page-title', 'Export '.$type)

@section('content')
    <div class="erp-card p-4">
        <h1 class="h4 fw-bold mb-2">Export {{ $type }}</h1>
        <p class="text-muted mb-4">Export route is working. Hook this route to Laravel Excel, DomPDF, or queued report generation for production files.</p>
        <a href="{{ route('erp.dashboard') }}" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>
@endsection
