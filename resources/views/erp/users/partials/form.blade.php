<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">User ID</label>
        <input name="user_id" class="form-control" value="{{ isset($userId) ? $userId : 'USR-005' }}" required>
    </div>
    <div class="col-md-5">
        <label class="form-label">Full Name</label>
        <input name="name" class="form-control" value="{{ $mode === 'edit' ? 'Maria Santos' : '' }}" placeholder="User full name" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ $mode === 'edit' ? 'maria.santos@seajet.test' : '' }}" placeholder="user@company.com" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select erp-select"><option>Admin</option><option>Accounting</option><option>Finance</option><option>Sales</option><option>Payables</option><option>Warehouse</option><option>Viewer</option></select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Department</label>
        <select name="department" class="form-select erp-select"><option>Accounting</option><option>Finance</option><option>Sales</option><option>Payables</option><option>Operations</option><option>Management</option></select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Branch</label>
        <select name="branch" class="form-select erp-select"><option>Main</option><option>Manila</option><option>Pampanga</option><option>Cebu</option><option>All</option></select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select erp-select"><option>Active</option><option>Pending Invite</option><option>Inactive</option><option>Locked</option></select>
    </div>
    <div class="col-12">
        <label class="form-label">Access Scope</label>
        <textarea name="access_scope" class="form-control" rows="3">Dashboard, assigned module records, reports, print/export according to role.</textarea>
    </div>
</div>
