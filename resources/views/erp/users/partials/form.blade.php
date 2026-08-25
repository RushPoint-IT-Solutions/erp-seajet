<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">User ID</label>
        <input name="user_id" class="form-control" value="{{ old('user_id', isset($record) ? $record->id : '') }}" placeholder="Assigned automatically" {{ $mode === 'edit' ? 'readonly' : '' }}>
    </div>
    <div class="col-md-5">
        <label class="form-label">Full Name</label>
        <input name="name" class="form-control" value="{{ old('name', isset($record) ? $record->name : '') }}" placeholder="User full name" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', isset($record) ? $record->email : '') }}" placeholder="user@company.com" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select erp-select" required>
            @php($selectedRole = app(\App\Services\Security\RbacService::class)->normalizeRole(old('role', isset($record) ? $record->role : 'accounting')))
            @foreach($roles as $role)
                <option value="{{ $role['key'] }}" {{ $selectedRole === $role['key'] ? 'selected' : '' }}>{{ $role['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Department</label>
        <select name="department" class="form-select erp-select">@foreach(['Accounting', 'Finance', 'Operations', 'Management'] as $department)<option {{ old('department', isset($record) ? $record->department : '') === $department ? 'selected' : '' }}>{{ $department }}</option>@endforeach</select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Branch</label>
        <select name="branch" class="form-select erp-select"><option>Main</option><option>Manila</option><option>Pampanga</option><option>Cebu</option><option>All</option></select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select erp-select"><option>Active</option><option {{ isset($record) && !$record->is_active ? 'selected' : '' }}>Inactive</option><option>Pending Invite</option><option>Locked</option></select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ $mode === 'edit' ? 'New Temporary Password (optional)' : 'Temporary Password' }}</label>
        <input type="password" name="password" class="form-control" minlength="12" {{ $mode === 'create' ? 'required' : '' }} autocomplete="new-password">
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm Temporary Password</label>
        <input type="password" name="password_confirmation" class="form-control" minlength="12" {{ $mode === 'create' ? 'required' : '' }} autocomplete="new-password">
    </div>
    <div class="col-12">
        <label class="form-label">Access Scope</label>
        <textarea name="access_scope" class="form-control" rows="3">Dashboard, assigned module records, reports, print/export according to role.</textarea>
    </div>
</div>
