@extends('erp.layout')

@section('title', 'System Update')
@section('page-title', 'System Update')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">System Version &amp; Updates</h1>
            <p class="text-muted mb-0">Administrator-controlled Git, Composer, database, and cache deployment workflow.</p>
        </div>
        <span class="badge {{ $update['state']['status'] === 'completed' ? 'bg-success' : (in_array($update['state']['status'], ['queued', 'running'], true) ? 'bg-warning text-dark' : ($update['state']['status'] === 'failed' ? 'bg-danger' : 'bg-secondary')) }} px-3 py-2">
            {{ ucwords($update['state']['status'] ?? 'idle') }}
        </span>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Version', 'value' => $update['version'], 'icon' => 'bi-tag'],
            ['label' => 'Branch', 'value' => $update['branch'], 'icon' => 'bi-git'],
            ['label' => 'Commit', 'value' => $update['commit'], 'icon' => 'bi-code-square'],
            ['label' => 'Commit Date', 'value' => $update['commit_date'] ? date('M d, Y H:i', strtotime($update['commit_date'])) : 'Unknown', 'icon' => 'bi-calendar3'],
        ] as $item)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="erp-card p-3 h-100">
                    <div class="text-muted small mb-1"><i class="bi {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}</div>
                    <div class="fw-bold text-break">{{ $item['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="erp-card p-4 mb-3">
                <h2 class="h5 fw-bold">Run System Update</h2>
                <p class="text-muted">The updater runs these fixed steps:</p>
                <ol class="small ps-3">
                    <li>Verify the Git worktree is clean and acquire an update lock.</li>
                    <li>Enable maintenance mode and run <code>git pull --ff-only</code>.</li>
                    <li>Run <code>composer install</code> with optimized autoloading.</li>
                    <li>Run database migrations and refresh configuration/view caches.</li>
                    <li>Leave maintenance mode and record the resulting version.</li>
                </ol>

                @if(!$update['enabled'])
                    <div class="alert alert-warning mb-0">Web updates are disabled by <code>SYSTEM_UPDATE_ENABLED</code>.</div>
                @elseif($update['dirty'])
                    <div class="alert alert-warning">
                        <strong>Update blocked:</strong> the working tree has {{ count($update['dirty_files']) }} uncommitted change(s). Commit or stash them first.
                    </div>
                    <details class="small mb-3"><summary>Changed files</summary><pre class="bg-light border rounded p-2 mt-2 mb-0">{{ implode("\n", array_slice($update['dirty_files'], 0, 50)) }}</pre></details>
                @elseif($update['running'])
                    <div class="alert alert-info mb-0">An update is currently running. Progress refreshes automatically.</div>
                @else
                    <div class="alert alert-danger small">
                        Back up the database and uploaded files before updating. The application will briefly enter maintenance mode.
                    </div>
                    <form method="post" action="{{ route('erp.system-update.run') }}" onsubmit="return confirm('Start the production system update now?');">
                        @csrf
                        <label class="form-label">Type <strong>UPDATE</strong> to confirm</label>
                        <div class="input-group">
                            <input name="confirmation" class="form-control" required autocomplete="off" pattern="UPDATE" placeholder="UPDATE">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-download me-1"></i>Run Update</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-xl-7">
            <div class="erp-card p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div><h2 class="h5 fw-bold mb-1">Update Activity</h2><div class="text-muted small">{{ $update['state']['message'] ?? 'No update activity.' }}</div></div>
                    @if(!empty($update['state']['finished_at']))<small class="text-muted">{{ date('M d, Y H:i:s', strtotime($update['state']['finished_at'])) }}</small>@endif
                </div>
                <div class="row g-2 small mb-3">
                    <div class="col-md-6"><span class="text-muted">Before:</span> <strong>{{ $update['state']['before_version'] ?? '-' }}</strong></div>
                    <div class="col-md-6"><span class="text-muted">After:</span> <strong>{{ $update['state']['after_version'] ?? '-' }}</strong></div>
                </div>
                <pre id="updateLog" class="bg-dark text-light rounded p-3 mb-0" style="min-height:260px;max-height:520px;overflow:auto;white-space:pre-wrap">{{ implode("\n", $update['state']['log'] ?? []) ?: 'No update log is available.' }}</pre>
            </div>
        </div>
    </div>
@endsection

@if($update['running'])
@push('scripts')
<script>
    (function pollUpdate() {
        window.setTimeout(function () {
            fetch('{{ route('erp.system-update.status') }}', { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (status) {
                    if (!status.running) {
                        window.location.reload();
                        return;
                    }
                    var log = document.getElementById('updateLog');
                    log.textContent = (status.state.log || []).join('\n') || 'Waiting for updater output...';
                    log.scrollTop = log.scrollHeight;
                    pollUpdate();
                })
                .catch(pollUpdate);
        }, 3000);
    }());
</script>
@endpush
@endif
