@if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator'], true))
    <form method="post" action="{{ route('erp.settings.transaction-data.reset') }}" class="d-inline" onsubmit="return confirm('Permanently delete ALL Billing, AP, CV/check, receipt, and journal transaction data for this company? Layout designs and setup will be preserved. This cannot be undone.')">
        {{ csrf_field() }}
        <input type="hidden" name="confirmation" value="RESET TRANSACTION DATA">
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Reset Data</button>
    </form>
@endif
