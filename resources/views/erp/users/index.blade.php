@extends('erp.layout')

@section('title', 'User Access Control')
@section('page-title', 'User Access Control')

@push('styles')
<style>
    .permission-toolbar {
        background: #fff;
        border: 1px solid var(--erp-border);
        border-radius: 8px;
    }
    .role-radio-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        border-bottom: 1px solid var(--erp-border);
    }
    .role-radio {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 10px;
        cursor: pointer;
        color: #24304f;
        border-bottom: 2px solid transparent;
    }
    .role-radio input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .role-radio .role-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--role-color);
        box-shadow: 0 0 0 4px rgba(15, 23, 42, .04);
    }
    .role-radio input:checked + .role-dot {
        box-shadow: 0 0 0 4px rgba(254, 121, 13, .14);
    }
    .role-radio:has(input:checked) {
        color: #111827;
        border-bottom-color: #2f5597;
        font-weight: 700;
    }
    .permission-panel {
        display: none;
    }
    .permission-panel.active {
        display: block;
    }
    .permission-role-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 14px;
        border-radius: 999px;
        color: #fff;
        background: #1f2937;
        font-size: 12px;
        font-weight: 700;
    }
    .permission-section-title {
        color: #657489;
        font-size: 12px;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .permission-badge {
        min-width: 46px;
        display: inline-flex;
        justify-content: center;
        padding: 3px 8px;
        border-radius: 999px;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .permission-view { background: #2f5597; }
    .permission-create { background: #00a99d; }
    .permission-edit { background: #f6b23f; }
    .permission-delete { background: #f15b45; }
    .permission-choice {
        display: inline-grid;
        grid-template-columns: 1fr 1fr;
        width: 82px;
        padding: 3px;
        border-radius: 999px;
        background: #e5e9ee;
        gap: 2px;
    }
    .permission-choice label {
        margin: 0;
    }
    .permission-choice input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .permission-choice span {
        display: block;
        min-height: 24px;
        border-radius: 999px;
        color: #657489;
        font-size: 11px;
        font-weight: 700;
        line-height: 24px;
        text-align: center;
        cursor: pointer;
    }
    .permission-choice input:checked + span {
        background: #fff;
        color: #111827;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .16);
    }
    .permission-choice input[value="1"]:checked + span {
        background: #d9fbe9;
        color: #047857;
    }
    .permission-choice.disabled {
        opacity: .72;
    }
    .permission-choice.disabled span {
        cursor: not-allowed;
    }
    .permission-table td,
    .permission-table th {
        vertical-align: middle;
    }
    .permission-table tbody td:first-child {
        min-width: 220px;
    }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Role & Permission Management</h1>
            <p class="text-muted mb-0">Control what each role can view, create, edit, or delete per module.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <span class="btn btn-sm btn-outline-success disabled"><i class="bi bi-shield-check me-1"></i>Permission draft</span>
            <a href="{{ route('erp.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>New User</a>
        </div>
    </div>

    <div class="permission-toolbar p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
            <span class="fw-semibold">Legend:</span>
            <span class="permission-badge permission-view">View</span>
            <span class="permission-badge permission-create">Create</span>
            <span class="permission-badge permission-edit">Edit</span>
            <span class="permission-badge permission-delete">Delete</span>
            <span class="vr d-none d-md-inline-block"></span>
            <span>Administrator always has full access and cannot be restricted.</span>
        </div>
    </div>

    <div class="erp-card">
        <div class="role-radio-row px-3">
            @foreach($roles as $index => $role)
                <label class="role-radio" style="--role-color: {{ $role['color'] }}">
                    <input type="radio" name="active_role" value="{{ $role['key'] }}" {{ $index === 0 ? 'checked' : '' }}>
                    <span class="role-dot"></span>
                    <span>{{ $role['label'] }}</span>
                    @if($role['locked'])
                        <i class="bi bi-lock-fill text-muted small"></i>
                    @endif
                </label>
            @endforeach
        </div>

        @foreach($roles as $roleIndex => $role)
            @php
                $isAdmin = $role['locked'];
            @endphp
            <div class="permission-panel p-3 {{ $roleIndex === 0 ? 'active' : '' }}" data-role-panel="{{ $role['key'] }}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="permission-role-chip" style="background: {{ $role['color'] }}">{{ $role['label'] }}</span>
                        <span class="text-muted small">{{ $role['note'] }}</span>
                    </div>
                    <div class="d-flex gap-2 no-print">
                        <button type="button" class="btn btn-sm btn-outline-primary permission-enable-all" {{ $isAdmin ? 'disabled' : '' }}>
                            <i class="bi bi-check2-circle me-1"></i>Enable All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger permission-reset-all" {{ $isAdmin ? 'disabled' : '' }}>
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset All
                        </button>
                    </div>
                </div>

                @foreach($permissionGroups as $group => $modules)
                    <div class="mb-4">
                        <div class="permission-section-title fw-bold mb-2">
                            <i class="bi bi-folder2-open me-1"></i>{{ $group }}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle permission-table mb-0">
                                <thead>
                                <tr>
                                    <th>Module</th>
                                    <th class="text-center"><span class="permission-badge permission-view">View</span></th>
                                    <th class="text-center"><span class="permission-badge permission-create">Create</span></th>
                                    <th class="text-center"><span class="permission-badge permission-edit">Edit</span></th>
                                    <th class="text-center"><span class="permission-badge permission-delete">Delete</span></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($modules as $module)
                                    <tr>
                                        <td class="fw-semibold">{{ $module['label'] }}</td>
                                        @foreach(['view', 'create', 'edit', 'delete'] as $permission)
                                            @php
                                                $available = in_array($permission, $module['permissions']);
                                                $defaultOn = $isAdmin
                                                    || ($role['key'] === 'accounting' && in_array($module['key'], ['dashboard', 'clients', 'invoices', 'payments', 'reports']))
                                                    || ($role['key'] === 'finance' && in_array($module['key'], ['dashboard', 'invoices', 'payments', 'reports']));
                                                $checkedOn = $available && $defaultOn;
                                                $fieldName = 'permissions['.$role['key'].']['.$module['key'].']['.$permission.']';
                                            @endphp
                                            <td class="text-center">
                                                @if($available)
                                                    <div class="permission-choice {{ $isAdmin ? 'disabled' : '' }}">
                                                        <label>
                                                            <input type="radio" name="{{ $fieldName }}" value="0" {{ !$checkedOn ? 'checked' : '' }} {{ $isAdmin ? 'disabled' : '' }}>
                                                            <span>Off</span>
                                                        </label>
                                                        <label>
                                                            <input type="radio" name="{{ $fieldName }}" value="1" {{ $checkedOn ? 'checked' : '' }} {{ $isAdmin ? 'disabled' : '' }}>
                                                            <span>On</span>
                                                        </label>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">N/A</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
    $('[name="active_role"]').on('change', function () {
        $('.permission-panel').removeClass('active');
        $('[data-role-panel="' + $(this).val() + '"]').addClass('active');
    });

    $('.permission-enable-all').on('click', function () {
        $(this).closest('.permission-panel').find('.permission-choice input[value="1"]:not(:disabled)').prop('checked', true);
    });

    $('.permission-reset-all').on('click', function () {
        $(this).closest('.permission-panel').find('.permission-choice input[value="0"]:not(:disabled)').prop('checked', true);
    });
</script>
@endpush
