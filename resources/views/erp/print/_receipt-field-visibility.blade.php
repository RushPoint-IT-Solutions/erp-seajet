<details class="receipt-layout-readout erp-card p-3 no-print">
    <summary class="fw-semibold text-primary" style="cursor: pointer">Choose printable fields</summary>
    <div class="d-flex flex-wrap gap-3 mt-3">
        @foreach($receiptFieldLabels as $field => $label)
            <label class="form-check d-flex align-items-center gap-2 mb-0">
                <input
                    type="checkbox"
                    class="form-check-input receipt-field-visibility"
                    name="visible_field[]"
                    value="{{ $field }}"
                    data-field-key="{{ $field }}"
                    form="receiptLayoutForm"
                    {{ in_array($field, $receiptHiddenFields, true) ? '' : 'checked' }}
                >
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <div class="small text-muted mt-2">Checked fields will appear and print. Deleted fields can be restored here.</div>
</details>
