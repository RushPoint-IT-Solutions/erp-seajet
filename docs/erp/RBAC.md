# ERP role-based access control

RBAC is enforced on both the server routes and the rendered navigation. Hiding a menu item is not treated as authorization.

## Default roles

| Role | Default scope |
| --- | --- |
| Administrator | Full access; grants cannot be restricted |
| Accounting Staff | AR/AP preparation, GL, tax, banking, FX, and reports; no AP approval/release by default |
| Finance Staff | Collections, banking, FX, AP approval/release/void, and reports |
| Accounts Receivable (AR) | Customers, invoices, collections, AR aging, and client SOA |
| Accounts Payable (AP) | Vendors, vendor bills, AP payments preparation, and AP aging; no approval/release by default |

Company administrators can create roles and update grants from **User Access Control**. User role choices are loaded from active database roles rather than a hard-coded list.

## Permission rules

- Permissions use `module.action` slugs, such as `payables.create` or `invoices.view`.
- Supported actions include `view`, `create`, `edit`, `delete`, and, where relevant, `approve` and `release`.
- Enabling a write, approval, or release grant also enables that module's `view` grant.
- AP approval and release are separate from bill/payment preparation to preserve maker-checker separation.
- Dashboard KPIs, charts, Ajax transaction data, quick actions, and approval queues are filtered using the same grants as the routes.
- API v1 resource routes enforce the corresponding module `view` permission after API authentication.
- Administrator access is a break-glass full-access role. Role and permission changes are written to the activity log.

## Adding a protected route

Place authenticated ERP routes behind `permission.access`. The middleware maps existing route-name prefixes to modules and infers CRUD actions. For a new or exceptional route, pass the permission explicitly, for example:

```php
->middleware('permission.access:payables,approve')
```

Add new modules/actions to `config/rbac.php`; the RBAC data migration provisions their permission records.
