@extends('erp.layout')

@section('title', 'Billing Invoice Print Layout')
@section('page-title', 'Billing Invoice Print Layout')

@push('styles')
<style>
    .layout-editor-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 16px;
        align-items: start;
    }
    .layout-sheet-wrap {
        overflow: auto;
        padding: 18px;
        background: #e9edf2;
        border-radius: 8px;
        border: 1px solid #d8dde6;
    }
    .layout-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #fbf7cf;
        @if($settings['template_url'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
        border: 1px solid #222;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .16);
        font-family: Arial, sans-serif;
        color: #1f2937;
        overflow: hidden;
        user-select: none;
    }
    .layout-template-guide {
        position: absolute;
        inset: 28px 24px 46px;
        border: 1px solid rgba(0, 0, 0, .5);
        pointer-events: none;
    }
    .layout-guide-line {
        position: absolute;
        left: 24px;
        right: 24px;
        height: 1px;
        background: rgba(0, 0, 0, .45);
        pointer-events: none;
    }
    .layout-guide-vline {
        position: absolute;
        top: 330px;
        bottom: 210px;
        width: 1px;
        background: rgba(0, 0, 0, .35);
        pointer-events: none;
    }
    .layout-label-preview {
        position: absolute;
        font-size: 11px;
        color: rgba(0, 0, 0, .55);
        text-transform: uppercase;
        letter-spacing: .02em;
        pointer-events: none;
    }
    .layout-company-preview {
        position: absolute;
        top: 28px;
        left: 180px;
        right: 180px;
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: .02em;
        pointer-events: none;
    }
    .layout-logo-preview {
        position: absolute;
        top: 26px;
        left: 92px;
        width: 72px;
        height: 48px;
        object-fit: contain;
        pointer-events: none;
    }
    .layout-title-preview {
        position: absolute;
        top: 82px;
        right: 88px;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: .04em;
        pointer-events: none;
    }
    .drag-field {
        position: absolute;
        z-index: 5;
        min-width: 34px;
        max-width: 390px;
        padding: 3px 6px;
        border: 1px dashed #fe790d;
        border-radius: 4px;
        background: rgba(255, 255, 255, .72);
        color: #1f2937;
        cursor: move;
        font-size: 12px;
        line-height: 1.25;
        white-space: pre-line;
        touch-action: none;
    }
    .drag-field.amount {
        text-align: right;
        min-width: 92px;
        font-weight: 600;
    }
    .drag-field.small {
        font-size: 10px;
    }
    .drag-field.active {
        z-index: 10;
        background: #fff7ed;
        border-style: solid;
        box-shadow: 0 0 0 3px rgba(254, 121, 13, .18);
    }
    .field-list {
        max-height: calc(100vh - 270px);
        overflow: auto;
    }
    .field-list button {
        text-align: left;
    }
    .coordinate-pill {
        font-family: Consolas, "Courier New", monospace;
        font-size: 12px;
    }
    .delete-custom-field {
        flex: 0 0 auto;
    }
    .field-remove-inside {
        position: absolute;
        top: -10px;
        right: -10px;
        width: 20px;
        height: 20px;
        border: 0;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        background: #dc3545;
        color: #fff;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .24);
        z-index: 12;
    }
    .custom-drag-field:hover .field-remove-inside,
    .custom-drag-field.active .field-remove-inside {
        display: inline-flex;
    }
    .template-muted .layout-logo-preview,
    .template-muted .layout-company-preview,
    .template-muted .layout-title-preview,
    .template-muted .layout-template-guide,
    .template-muted .layout-guide-line,
    .template-muted .layout-guide-vline,
    .template-muted .layout-label-preview {
        opacity: .35;
    }
    @media (max-width: 1199.98px) {
        .layout-editor-shell {
            grid-template-columns: 1fr;
        }
        .field-list {
            max-height: 360px;
        }
    }
</style>
@endpush

@section('content')
    @php
        $samples = [
            'cash_sales' => ['label' => 'Cash Sales', 'value' => '[ ]', 'class' => ''],
            'charge_sales' => ['label' => 'Charge Sales', 'value' => '[x]', 'class' => ''],
            'customer' => ['label' => 'Customer', 'value' => 'PHILUSA CORPORATION', 'class' => ''],
            'address' => ['label' => 'Address', 'value' => "28 SHAW BLVD, KAPITOLYO 1603\nCITY OF PASIG", 'class' => 'small'],
            'tin' => ['label' => 'TIN', 'value' => '000-281-014-00000', 'class' => ''],
            'mawb' => ['label' => 'MAWB B/L No.', 'value' => 'OOLU32322350120', 'class' => ''],
            'hawb' => ['label' => 'HAWB B/L No.', 'value' => '2GJXM412-G1', 'class' => ''],
            'date_shipped' => ['label' => 'Date Shipped', 'value' => '05/19/2026', 'class' => ''],
            'origin_dest' => ['label' => "Origin/Dest'n", 'value' => 'ROTTERDAM/MNL', 'class' => ''],
            'commodity' => ['label' => 'Commodity', 'value' => "3 X 40'HC", 'class' => ''],
            'invoice_no' => ['label' => 'Invoice No.', 'value' => '0001031', 'class' => ''],
            'invoice_date' => ['label' => 'Invoice Date', 'value' => '05/19/2026', 'class' => ''],
            'line_description' => ['label' => 'Line Description', 'value' => "OCEAN FREIGHT (5% OF FREIGHT CHARGES)\n(2,100 x 3 CNTRS = \$6,300 x 61.55 = 387,765 x 5%)\n\nSHIPPER: LEMOINE HOLLAND B.V.", 'class' => ''],
            'soa_no' => ['label' => 'SOA No.', 'value' => 'SOA# 7187', 'class' => ''],
            'line_qty' => ['label' => 'Line Qty', 'value' => '1', 'class' => 'amount'],
            'line_unit_price' => ['label' => 'Line Unit Price', 'value' => '19,388.25', 'class' => 'amount'],
            'line_amount' => ['label' => 'Line Amount', 'value' => '19,388.25', 'class' => 'amount'],
            'vatable_sales' => ['label' => 'VATable Sales', 'value' => 'PHP 19,388.25', 'class' => 'amount'],
            'vat' => ['label' => 'VAT', 'value' => 'PHP 2,326.59', 'class' => 'amount'],
            'zero_rated_sales' => ['label' => 'Zero-rated Sales', 'value' => '0.00', 'class' => 'amount'],
            'vat_exempt_sales' => ['label' => 'VAT-exempt Sales', 'value' => '0.00', 'class' => 'amount'],
            'total_sales' => ['label' => 'Total Sales', 'value' => 'PHP 21,714.84', 'class' => 'amount'],
            'less_vat' => ['label' => 'Less VAT', 'value' => 'PHP 2,326.59', 'class' => 'amount'],
            'amount_net_vat' => ['label' => 'Amount Net of VAT', 'value' => 'PHP 19,388.25', 'class' => 'amount'],
            'discount' => ['label' => 'Discount', 'value' => '0.00', 'class' => 'amount'],
            'add_vat' => ['label' => 'Add VAT', 'value' => 'PHP 2,326.59', 'class' => 'amount'],
            'withholding_tax' => ['label' => 'Withholding Tax', 'value' => 'PHP -387.77', 'class' => 'amount'],
            'amount_due' => ['label' => 'Amount Due', 'value' => 'PHP 21,327.07', 'class' => 'amount'],
            'prepared_by' => ['label' => 'Prepared By', 'value' => 'MABEL PLATA', 'class' => ''],
        ];
        $tableFields = [
            ['label' => 'Invoice Table: Item', 'text' => 'Ocean Freight', 'width' => 150, 'font_size' => 12],
            ['label' => 'Invoice Table: Description', 'text' => 'OCEAN FREIGHT (5% OF FREIGHT CHARGES)', 'width' => 360, 'font_size' => 12],
            ['label' => 'Invoice Table: Unit Cost', 'text' => '19,388.25', 'width' => 110, 'font_size' => 12],
            ['label' => 'Invoice Table: Qty', 'text' => '1', 'width' => 70, 'font_size' => 12],
            ['label' => 'Invoice Table: Tax Category', 'text' => '12% VAT', 'width' => 120, 'font_size' => 12],
            ['label' => 'Invoice Table: Total', 'text' => '19,388.25', 'width' => 120, 'font_size' => 12],
            ['label' => 'Client', 'text' => 'PHILUSA CORPORATION', 'width' => 220, 'font_size' => 12],
            ['label' => 'Invoice Number', 'text' => '0001031', 'width' => 120, 'font_size' => 12],
            ['label' => 'SOA Number', 'text' => 'SOA# 7187', 'width' => 120, 'font_size' => 12],
            ['label' => 'Shipment Job ID', 'text' => 'JOB-SEA-2026-0423', 'width' => 170, 'font_size' => 12],
        ];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Drag Billing Invoice Fields</h1>
            <p class="text-muted mb-0">Move each content field directly on the preview. The system will print only the content, aligned to your pre-printed form.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.billings.print-layout.invoice') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            <button type="button" id="saveLayout" class="btn btn-success"><i class="bi bi-save me-1"></i>Save Layout</button>
            <button type="submit" form="layoutForm" id="applyPreview" class="btn btn-primary"><i class="bi bi-eye me-1"></i>Apply and Preview</button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="get" action="{{ route('erp.billings.print-invoice') }}" id="layoutForm">
        <input type="hidden" name="_token" id="layoutCsrfToken" value="{{ csrf_token() }}" disabled>
        <input type="hidden" name="paper_size" id="paper_size_hidden" value="{{ $settings['paper_size'] }}">
        <input type="hidden" name="paper_width" id="paper_width_hidden" value="{{ $settings['paper_width'] }}">
        <input type="hidden" name="paper_height" id="paper_height_hidden" value="{{ $settings['paper_height'] }}">
        <input type="hidden" name="template_url" id="template_url_hidden" value="{{ $settings['template_url'] }}">
        <input type="hidden" name="print_template" id="print_template_hidden" value="{{ $settings['print_template'] ? '1' : '0' }}">
        <input type="hidden" name="custom_fields_present" value="1">
        @foreach($positions as $field => $point)
            <input type="hidden" name="x_{{ $field }}" id="x_{{ $field }}" value="{{ $point[0] }}">
            <input type="hidden" name="y_{{ $field }}" id="y_{{ $field }}" value="{{ $point[1] }}">
            <input type="hidden" name="w_{{ $field }}" id="w_{{ $field }}" value="{{ $fieldStyles[$field]['width'] }}">
            <input type="hidden" name="fs_{{ $field }}" id="fs_{{ $field }}" value="{{ $fieldStyles[$field]['font_size'] }}">
        @endforeach
        <div id="customHiddenInputs">
            @foreach($customFields as $custom)
                <input type="hidden" name="custom_key[]" id="custom_key_{{ $custom['key'] }}" value="{{ $custom['key'] }}">
                <input type="hidden" name="custom_label[]" id="custom_label_{{ $custom['key'] }}" value="{{ $custom['label'] }}">
                <input type="hidden" name="custom_text[]" id="custom_text_{{ $custom['key'] }}" value="{{ $custom['text'] }}">
                <input type="hidden" name="custom_x[]" id="custom_x_{{ $custom['key'] }}" value="{{ $custom['x'] }}">
                <input type="hidden" name="custom_y[]" id="custom_y_{{ $custom['key'] }}" value="{{ $custom['y'] }}">
                <input type="hidden" name="custom_w[]" id="custom_w_{{ $custom['key'] }}" value="{{ $custom['width'] }}">
                <input type="hidden" name="custom_fs[]" id="custom_fs_{{ $custom['key'] }}" value="{{ $custom['font_size'] }}">
            @endforeach
        </div>

        <div class="layout-editor-shell">
            <div class="layout-sheet-wrap">
                <div class="layout-sheet" id="layoutSheet">
                    <img src="{{ asset('images/image.png') }}" class="layout-logo-preview" alt="SeaJet logo">
                    <div class="layout-company-preview">
                        SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
                        <span style="font-size: 10px; font-weight: 600">UNIT 18 & 19 BLDG. B, CARGO VILLAGE COMPLEX, PARANAQUE CITY</span>
                    </div>
                    <div class="layout-title-preview">BILLING INVOICE</div>
                    <div class="layout-template-guide"></div>
                    <div class="layout-guide-line" style="top: 242px"></div>
                    <div class="layout-guide-line" style="top: 330px"></div>
                    <div class="layout-guide-line" style="top: 350px"></div>
                    <div class="layout-guide-line" style="top: 798px"></div>
                    <div class="layout-guide-line" style="top: 988px"></div>
                    <div class="layout-guide-vline" style="left: 470px"></div>
                    <div class="layout-guide-vline" style="left: 590px"></div>
                    <div class="layout-guide-vline" style="left: 705px"></div>

                    <div class="layout-label-preview" style="left: 34px; top: 135px">Cash Sales</div>
                    <div class="layout-label-preview" style="left: 118px; top: 135px">Charge Sales</div>
                    <div class="layout-label-preview" style="left: 34px; top: 158px">Customer</div>
                    <div class="layout-label-preview" style="left: 34px; top: 183px">Address</div>
                    <div class="layout-label-preview" style="left: 34px; top: 225px">TIN</div>
                    <div class="layout-label-preview" style="left: 34px; top: 263px">MAWB B/L No.</div>
                    <div class="layout-label-preview" style="left: 34px; top: 287px">HAWB B/L No.</div>
                    <div class="layout-label-preview" style="left: 34px; top: 311px">Date Shipped</div>
                    <div class="layout-label-preview" style="left: 478px; top: 263px">Origin/Dest'n</div>
                    <div class="layout-label-preview" style="left: 478px; top: 287px">Commodity</div>
                    <div class="layout-label-preview" style="left: 52px; top: 351px">Item Description/Nature of Service</div>
                    <div class="layout-label-preview" style="left: 504px; top: 351px">Quantity</div>
                    <div class="layout-label-preview" style="left: 600px; top: 351px">Unit Price</div>
                    <div class="layout-label-preview" style="left: 752px; top: 351px">Amount</div>

                    @foreach($positions as $field => $point)
                        @php $sample = $samples[$field]; @endphp
                        <div class="drag-field {{ $sample['class'] }}"
                             data-kind="standard"
                             data-field="{{ $field }}"
                             data-label="{{ $sample['label'] }}"
                             style="left: {{ $point[0] }}px; top: {{ $point[1] }}px; width: {{ $fieldStyles[$field]['width'] }}px; font-size: {{ $fieldStyles[$field]['font_size'] }}px"
                             title="{{ $sample['label'] }}">
                            {{ $sample['value'] }}
                        </div>
                    @endforeach
                    @foreach($customFields as $custom)
                        <div class="drag-field custom-drag-field"
                             data-kind="custom"
                             data-field="{{ $custom['key'] }}"
                             data-label="{{ $custom['label'] }}"
                             style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px"
                             title="{{ $custom['label'] }}">
                            <button type="button" class="field-remove-inside" data-field="{{ $custom['key'] }}" title="Remove field"><i class="bi bi-x"></i></button>
                            {{ $custom['text'] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="erp-card p-3">
                <h2 class="h6 fw-bold mb-1">Paper & Template</h2>
                <p class="text-muted small mb-3">Change the sheet size and set a background image for alignment.</p>

                <div class="mb-3">
                    <label class="form-label small text-muted">Paper Size</label>
                    <select class="form-select form-select-sm" id="paperSizeControl">
                        <option value="letter" data-width="816" data-height="1056" {{ $settings['paper_size'] === 'letter' ? 'selected' : '' }}>Letter 8.5 x 11</option>
                        <option value="legal" data-width="816" data-height="1344" {{ $settings['paper_size'] === 'legal' ? 'selected' : '' }}>Legal 8.5 x 14</option>
                        <option value="a4" data-width="794" data-height="1123" {{ $settings['paper_size'] === 'a4' ? 'selected' : '' }}>A4</option>
                        <option value="custom" data-width="{{ $settings['paper_width'] }}" data-height="{{ $settings['paper_height'] }}" {{ $settings['paper_size'] === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">Width px</label>
                        <input type="number" class="form-control form-control-sm" id="paperWidthControl" value="{{ $settings['paper_width'] }}" min="400" max="1600">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Height px</label>
                        <input type="number" class="form-control form-control-sm" id="paperHeightControl" value="{{ $settings['paper_height'] }}" min="400" max="2200">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted">Background Template Image URL</label>
                    <input type="url" class="form-control form-control-sm" id="templateUrlControl" value="{{ $settings['template_url'] }}" placeholder="https://example.com/billing-template.jpg">
                    <div class="form-text">Use a public image URL of the scanned pre-printed form.</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="printTemplateControl" {{ $settings['print_template'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="printTemplateControl">Print background template too</label>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="applyTemplateSettings"><i class="bi bi-image me-1"></i>Update Sheet</button>
                </div>

                <hr>
                <h2 class="h6 fw-bold mb-1">Selected Field Size</h2>
                <p class="text-muted small mb-3">Select a field, then adjust its printed column width and text size.</p>
                <div class="mb-2">
                    <label class="form-label small text-muted">Selected Field</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="selectedFieldLabel" value="None selected" readonly>
                        <button type="button" class="btn btn-outline-danger" id="deleteSelectedField" title="Remove selected custom field"><i class="bi bi-trash"></i></button>
                    </div>
                    <div class="form-text">The remove icon works for custom fields and added columns.</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">Width px</label>
                        <input type="number" class="form-control form-control-sm" id="fieldWidthControl" min="20" max="900" value="160">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Font px</label>
                        <input type="number" class="form-control form-control-sm" id="fieldFontControl" min="6" max="48" value="12">
                    </div>
                </div>

                <hr>
                <h2 class="h6 fw-bold mb-1">Add Custom Column / Field</h2>
                <p class="text-muted small mb-3">Add extra printable content, then drag and size it on the sheet.</p>
                <div class="mb-2">
                    <label class="form-label small text-muted">Add Based From Table</label>
                    <select class="form-select form-select-sm" id="tableFieldControl">
                        <option value="">Choose table field...</option>
                        @foreach($tableFields as $field)
                            <option value="{{ $loop->index }}"
                                    data-label="{{ $field['label'] }}"
                                    data-text="{{ $field['text'] }}"
                                    data-width="{{ $field['width'] }}"
                                    data-font="{{ $field['font_size'] }}">
                                {{ $field['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted">Label</label>
                    <input type="text" class="form-control form-control-sm" id="customLabelControl" value="Custom Column">
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted">Text to Print</label>
                    <textarea class="form-control form-control-sm" id="customTextControl" rows="2">New Column</textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">Width px</label>
                        <input type="number" class="form-control form-control-sm" id="customWidthControl" min="20" max="900" value="160">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Font px</label>
                        <input type="number" class="form-control form-control-sm" id="customFontControl" min="6" max="48" value="12">
                    </div>
                </div>
                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addCustomField"><i class="bi bi-plus-circle me-1"></i>Add Column / Field</button>
                </div>

                <hr>
                <h2 class="h6 fw-bold mb-1">Field Positions</h2>
                <p class="text-muted small mb-3">Click a field name to highlight it. Drag the field on the sheet to update its print position.</p>
                <div class="field-list list-group list-group-flush">
                    <div id="customFieldList">
                        @foreach($customFields as $custom)
                            <button type="button" class="list-group-item list-group-item-action px-0 field-focus" data-kind="custom" data-field="{{ $custom['key'] }}">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span class="fw-semibold">{{ $custom['label'] }}</span>
                                    <span class="d-flex align-items-center gap-1">
                                        <span class="badge badge-soft coordinate-pill" id="pos_{{ $custom['key'] }}">{{ $custom['x'] }}, {{ $custom['y'] }}</span>
                                        <span class="btn btn-sm btn-outline-danger delete-custom-field" data-field="{{ $custom['key'] }}"><i class="bi bi-trash"></i></span>
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                    @foreach($positions as $field => $point)
                        @php $sample = $samples[$field]; @endphp
                        <button type="button" class="list-group-item list-group-item-action px-0 field-focus" data-kind="standard" data-field="{{ $field }}">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold">{{ $sample['label'] }}</span>
                                <span class="badge badge-soft coordinate-pill" id="pos_{{ $field }}">{{ $point[0] }}, {{ $point[1] }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </aside>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    $(function () {
        var active = null;
        var offsetX = 0;
        var offsetY = 0;
        var sheet = document.getElementById('layoutSheet');
        var selectedField = null;
        var customCounter = {{ count($customFields) + 1 }};

        $('#saveLayout').on('click', function () {
            applyPaperAndTemplate();
            $('#layoutCsrfToken').prop('disabled', false);
            $('#layoutForm')
                .attr('method', 'post')
                .attr('action', '{{ route('erp.billings.print-layout.invoice.save') }}')
                .trigger('submit');
        });

        $('#applyPreview').on('click', function () {
            applyPaperAndTemplate();
            $('#layoutCsrfToken').prop('disabled', true);
            $('#layoutForm')
                .attr('method', 'get')
                .attr('action', '{{ route('erp.billings.print-invoice') }}');
        });

        function setActive(field) {
            $('.drag-field').removeClass('active');
            var el = $('[data-field="' + field + '"].drag-field');
            el.addClass('active');
            selectedField = field;
            $('#selectedFieldLabel').val(el.data('label') || field);
            $('#fieldWidthControl').val(parseInt(el[0].style.width, 10) || el.outerWidth());
            $('#fieldFontControl').val(parseInt(el[0].style.fontSize, 10) || 12);
        }

        function setHiddenValue(field, key, value, kind) {
            if (kind === 'custom') {
                $('#custom_' + key + '_' + field).val(value);
            } else {
                $('#' + key + '_' + field).val(value);
            }
        }

        function updatePosition(el, x, y) {
            var maxX = sheet.clientWidth - el.offsetWidth;
            var maxY = sheet.clientHeight - el.offsetHeight;
            x = Math.max(0, Math.min(Math.round(x), maxX));
            y = Math.max(0, Math.min(Math.round(y), maxY));
            el.style.left = x + 'px';
            el.style.top = y + 'px';

            var field = el.getAttribute('data-field');
            var kind = el.getAttribute('data-kind');
            setHiddenValue(field, 'x', x, kind);
            setHiddenValue(field, 'y', y, kind);
            $('#pos_' + field).text(x + ', ' + y);
        }

        function updateSize(el, width, fontSize) {
            width = Math.max(20, Math.min(parseInt(width, 10) || 160, 900));
            fontSize = Math.max(6, Math.min(parseInt(fontSize, 10) || 12, 48));
            el.style.width = width + 'px';
            el.style.fontSize = fontSize + 'px';

            var field = el.getAttribute('data-field');
            var kind = el.getAttribute('data-kind');
            setHiddenValue(field, 'w', width, kind);
            setHiddenValue(field, 'fs', fontSize, kind);
            updatePosition(el, parseInt(el.style.left, 10) || 0, parseInt(el.style.top, 10) || 0);
        }

        function selectedElement() {
            if (!selectedField) {
                return null;
            }

            return $('[data-field="' + selectedField + '"].drag-field')[0] || null;
        }

        function removeCustomField(field) {
            var el = $('[data-field="' + field + '"].drag-field');
            if (!el.length || el.attr('data-kind') !== 'custom') {
                return;
            }

            el.remove();
            $('[data-field="' + field + '"].field-focus').remove();
            $('#custom_key_' + field).remove();
            $('#custom_label_' + field).remove();
            $('#custom_text_' + field).remove();
            $('#custom_x_' + field).remove();
            $('#custom_y_' + field).remove();
            $('#custom_w_' + field).remove();
            $('#custom_fs_' + field).remove();

            if (selectedField === field) {
                selectedField = null;
                $('#selectedFieldLabel').val('None selected');
            }
        }

        function bindDrag(el) {
            $(el).on('pointerdown', function (event) {
                active = this;
                active.setPointerCapture(event.originalEvent.pointerId);
                var box = active.getBoundingClientRect();
                offsetX = event.originalEvent.clientX - box.left;
                offsetY = event.originalEvent.clientY - box.top;
                setActive(active.getAttribute('data-field'));
                event.preventDefault();
            });

            $(el).on('pointermove', function (event) {
                if (!active || active !== this) {
                    return;
                }

                var sheetBox = sheet.getBoundingClientRect();
                var x = event.originalEvent.clientX - sheetBox.left - offsetX;
                var y = event.originalEvent.clientY - sheetBox.top - offsetY;
                updatePosition(active, x, y);
            });

            $(el).on('pointerup pointercancel', function () {
                active = null;
            });
        }

        function applyPaperAndTemplate() {
            var selected = $('#paperSizeControl option:selected');
            var paperSize = $('#paperSizeControl').val();
            var width = parseInt($('#paperWidthControl').val(), 10) || parseInt(selected.data('width'), 10) || 816;
            var height = parseInt($('#paperHeightControl').val(), 10) || parseInt(selected.data('height'), 10) || 1056;
            var templateUrl = $('#templateUrlControl').val();
            var printTemplate = $('#printTemplateControl').is(':checked') ? '1' : '0';

            width = Math.max(400, Math.min(width, 1600));
            height = Math.max(400, Math.min(height, 2200));

            $('#paper_size_hidden').val(paperSize);
            $('#paper_width_hidden').val(width);
            $('#paper_height_hidden').val(height);
            $('#template_url_hidden').val(templateUrl);
            $('#print_template_hidden').val(printTemplate);

            $('#paperWidthControl').val(width);
            $('#paperHeightControl').val(height);

            sheet.style.width = width + 'px';
            sheet.style.height = height + 'px';
            if (templateUrl) {
                sheet.style.backgroundImage = 'url("' + templateUrl.replace(/"/g, '\\"') + '")';
                sheet.style.backgroundSize = '100% 100%';
                sheet.style.backgroundRepeat = 'no-repeat';
                sheet.style.backgroundPosition = 'center';
                sheet.classList.add('template-muted');
            } else {
                sheet.style.backgroundImage = '';
                sheet.classList.remove('template-muted');
            }

            $('.drag-field').each(function () {
                updatePosition(this, parseInt(this.style.left, 10) || 0, parseInt(this.style.top, 10) || 0);
            });
        }

        $('#paperSizeControl').on('change', function () {
            var selected = $('#paperSizeControl option:selected');
            if ($(this).val() !== 'custom') {
                $('#paperWidthControl').val(selected.data('width'));
                $('#paperHeightControl').val(selected.data('height'));
            }
            applyPaperAndTemplate();
        });

        $('#paperWidthControl, #paperHeightControl').on('input', function () {
            $('#paperSizeControl').val('custom');
            applyPaperAndTemplate();
        });

        $('#templateUrlControl, #printTemplateControl').on('input change', applyPaperAndTemplate);
        $('#applyTemplateSettings').on('click', applyPaperAndTemplate);

        $('.drag-field').each(function () {
            bindDrag(this);
        });

        $('#fieldWidthControl, #fieldFontControl').on('input', function () {
            var el = selectedElement();
            if (el) {
                updateSize(el, $('#fieldWidthControl').val(), $('#fieldFontControl').val());
            }
        });

        $('#tableFieldControl').on('change', function () {
            var option = $('#tableFieldControl option:selected');
            if (!option.val()) {
                return;
            }

            $('#customLabelControl').val(option.data('label'));
            $('#customTextControl').val(option.data('text'));
            $('#customWidthControl').val(option.data('width'));
            $('#customFontControl').val(option.data('font'));
        });

        $(document).on('click', '.field-focus', function () {
            var field = $(this).data('field');
            setActive(field);
            var el = $('[data-field="' + field + '"].drag-field')[0];
            if (el && el.scrollIntoView) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            }
        });

        $('#addCustomField').on('click', function () {
            var key = 'custom_' + Date.now() + '_' + customCounter;
            customCounter++;

            var label = $('#customLabelControl').val() || 'Custom Field';
            var text = $('#customTextControl').val() || 'Custom Text';
            var width = Math.max(20, Math.min(parseInt($('#customWidthControl').val(), 10) || 160, 900));
            var fontSize = Math.max(6, Math.min(parseInt($('#customFontControl').val(), 10) || 12, 48));
            var x = 60;
            var y = 60;

            $('#customHiddenInputs').append(
                '<input type="hidden" name="custom_key[]" id="custom_key_' + key + '" value="' + key + '">' +
                '<input type="hidden" name="custom_label[]" id="custom_label_' + key + '">' +
                '<input type="hidden" name="custom_text[]" id="custom_text_' + key + '">' +
                '<input type="hidden" name="custom_x[]" id="custom_x_' + key + '" value="' + x + '">' +
                '<input type="hidden" name="custom_y[]" id="custom_y_' + key + '" value="' + y + '">' +
                '<input type="hidden" name="custom_w[]" id="custom_w_' + key + '" value="' + width + '">' +
                '<input type="hidden" name="custom_fs[]" id="custom_fs_' + key + '" value="' + fontSize + '">'
            );
            $('#custom_label_' + key).val(label);
            $('#custom_text_' + key).val(text);

            var field = $('<div class="drag-field custom-drag-field" data-kind="custom"></div>');
            field.attr('data-field', key);
            field.attr('data-label', label);
            field.attr('title', label);
            field.css({ left: x + 'px', top: y + 'px', width: width + 'px', fontSize: fontSize + 'px' });
            field.append('<button type="button" class="field-remove-inside" data-field="' + key + '" title="Remove field"><i class="bi bi-x"></i></button>');
            field.append(document.createTextNode(text));
            $('#layoutSheet').append(field);
            bindDrag(field[0]);

            $('#customFieldList').prepend(
                '<button type="button" class="list-group-item list-group-item-action px-0 field-focus" data-kind="custom" data-field="' + key + '">' +
                '<div class="d-flex align-items-center justify-content-between gap-2">' +
                '<span class="fw-semibold"></span>' +
                '<span class="d-flex align-items-center gap-1">' +
                '<span class="badge badge-soft coordinate-pill" id="pos_' + key + '">' + x + ', ' + y + '</span>' +
                '<span class="btn btn-sm btn-outline-danger delete-custom-field" data-field="' + key + '"><i class="bi bi-trash"></i></span>' +
                '</span>' +
                '</div></button>'
            );
            $('#customFieldList button:first .fw-semibold').text(label);
            setActive(key);
        });

        $('#deleteSelectedField').on('click', function () {
            if (selectedField) {
                removeCustomField(selectedField);
            }
        });

        $(document).on('click', '.delete-custom-field', function (event) {
            event.preventDefault();
            event.stopPropagation();
            removeCustomField($(this).data('field'));
        });

        $(document).on('pointerdown click', '.field-remove-inside', function (event) {
            event.preventDefault();
            event.stopPropagation();
            removeCustomField($(this).data('field'));
        });

        applyPaperAndTemplate();
    });
</script>
@endpush
