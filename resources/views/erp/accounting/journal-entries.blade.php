@extends('erp.layout')

@section('title', 'Journal Entry')
@section('page-title', 'Journal Entry')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Create Journal Entry</h1>
            <p class="text-muted mb-0">Posts directly to the general ledger. Total debits must equal total credits before this entry can be saved.</p>
        </div>
        <a href="{{ route('erp.accounting.coa') }}" class="btn btn-outline-secondary"><i class="bi bi-list-columns me-1"></i> Chart of Accounts</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="erp-card p-4" method="post" action="{{ route('erp.accounting.journals.review') }}" id="journalForm">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Journal No</label>
                <input name="journal_no" class="form-control" value="{{ old('journal_no', $nextJournalNo) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Journal Date</label>
                <input name="journal_date" type="date" class="form-control" value="{{ old('journal_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shipment Job ID</label>
                <input name="shipment_job_id" class="form-control" value="{{ old('shipment_job_id') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Reference No</label>
                <input name="reference_no" class="form-control" value="{{ old('reference_no') }}">
            </div>
        </div>

        <div class="table-responsive mt-4">
            <table class="table align-middle" id="journalLines">
                <thead>
                <tr>
                    <th style="width: 32%">Account</th>
                    <th>Description</th>
                    <th class="text-end" style="width: 150px">Debit</th>
                    <th class="text-end" style="width: 150px">Credit</th>
                    <th class="text-center no-print" style="width: 52px"></th>
                </tr>
                </thead>
                <tbody>
                @for($i = 0; $i < 2; $i++)
                    <tr>
                        <td>
                            <select name="line_account[]" class="form-select journal-account-search">
                                <option value=""></option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="line_description[]" class="form-control"></td>
                        <td><input name="line_debit[]" class="form-control text-end line-debit" value=""></td>
                        <td><input name="line_credit[]" class="form-control text-end line-credit" value=""></td>
                        <td class="text-center no-print"><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                    </tr>
                @endfor
                </tbody>
                <tfoot>
                <tr class="fw-bold">
                    <td colspan="2" class="text-end">Totals</td>
                    <td class="text-end" id="totalDebit">0.00</td>
                    <td class="text-end" id="totalCredit">0.00</td>
                    <td class="no-print"></td>
                </tr>
                <tr>
                    <td colspan="5" id="balanceWarning" class="text-danger small no-print" style="display:none;">Total debits must equal total credits before this entry can be posted.</td>
                </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm no-print" id="addJournalLine"><i class="bi bi-plus-circle me-1"></i>Add Line</button>

        <div class="col-12 mt-4">
            <label class="form-label">Memo</label>
            <textarea name="memo" class="form-control" rows="3">{{ old('memo') }}</textarea>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.modules.show', 'accounting') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" id="postJournalBtn"><i class="bi bi-check2 me-1"></i> Post Journal Entry</button>
        </div>
    </form>

    <div class="erp-card p-4 mt-4">
        <h2 class="h6 fw-bold mb-3">Recently Posted Journal Entries</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Journal No</th>
                    <th>Date</th>
                    <th>Memo</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentEntries as $entry)
                    <tr>
                        <td>{{ $entry->journal_no }}</td>
                        <td>{{ $entry->journal_date }}</td>
                        <td>{{ $entry->memo ?: '-' }}</td>
                        <td><span class="badge text-bg-success text-uppercase">{{ $entry->status }}</span></td>
                        <td class="text-end">{{ number_format($entry->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No journal entries have been posted yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        function numberValue(value) {
            var parsed = parseFloat(String(value || '').replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }

        function recalc() {
            var totalDebit = 0;
            var totalCredit = 0;
            $('.line-debit').each(function () { totalDebit += numberValue($(this).val()); });
            $('.line-credit').each(function () { totalCredit += numberValue($(this).val()); });

            $('#totalDebit').text(totalDebit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#totalCredit').text(totalCredit.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            var balanced = Math.abs(totalDebit - totalCredit) < 0.005 && totalDebit > 0;
            $('#balanceWarning').toggle(!balanced);
            $('#postJournalBtn').prop('disabled', !balanced);
        }

        function initAccountSearch(scope) {
            $(scope || document).find('.journal-account-search').each(function () {
                var select = $(this);
                if (select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                select.select2({ width: 'resolve', placeholder: 'Search account', allowClear: true });
            });
        }

        $('#journalLines').on('input change', '.line-debit, .line-credit', recalc);

        $('#addJournalLine').on('click', function () {
            var source = $('#journalLines tbody tr:first');
            source.find('.journal-account-search.select2-hidden-accessible').select2('destroy');
            source.find('.select2-container').remove();

            var row = source.clone();
            row.find('input').val('');
            row.find('select').val('');
            $('#journalLines tbody').append(row);
            initAccountSearch(source);
            initAccountSearch(row);
        });

        $('#journalLines').on('click', '.remove-line', function () {
            if ($('#journalLines tbody tr').length > 2) {
                $(this).closest('tr').remove();
                recalc();
            }
        });

        initAccountSearch(document);
        recalc();
    });
</script>
@endpush
