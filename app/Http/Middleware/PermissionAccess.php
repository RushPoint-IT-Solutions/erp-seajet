<?php

namespace App\Http\Middleware;

use App\Services\Security\RbacService;
use Closure;

class PermissionAccess
{
    private $rbac;

    public function __construct(RbacService $rbac)
    {
        $this->rbac = $rbac;
    }

    public function handle($request, Closure $next, $module = null, $action = null)
    {
        if (!$request->user()) {
            return redirect()->guest(route('login'));
        }
        if (isset($request->user()->is_active) && !$request->user()->is_active) {
            abort(403, 'This user account is inactive.');
        }

        list($resolvedModule, $resolvedAction) = $this->resolvePermission($request, $module, $action);
        if ($this->rbac->allows($request->user(), $resolvedModule, $resolvedAction)) {
            return $next($request);
        }

        if ((string) optional($request->route())->getName() === 'erp.dashboard') {
            $routeName = $this->rbac->firstAccessibleRoute($request->user());
            if ($routeName && $routeName !== 'erp.dashboard') {
                return redirect()->route($routeName);
            }
        }

        abort(403, 'You do not have permission to '.$resolvedAction.' this ERP area.');
    }

    private function resolvePermission($request, $module, $action)
    {
        if ($module) {
            return [$module, $action ?: $this->actionFromRequest($request)];
        }

        $name = (string) optional($request->route())->getName();
        $map = [
            'erp.dashboard' => 'dashboard', 'erp.transactions.' => 'dashboard', 'erp.notifications' => 'dashboard',
            'erp.customers.' => 'clients', 'erp.suppliers.' => 'vendors',
            'erp.expenses.' => 'payables', 'erp.payables.' => 'payables',
            'erp.billings.' => 'invoices', 'erp.invoices.' => 'invoices', 'erp.invoice.' => 'invoices',
            'erp.soa' => 'invoices', 'erp.debit-note.' => 'invoices', 'erp.official-receipt.' => 'payments',
            'erp.acknowledgement-receipt.' => 'payments', 'erp.payments.' => 'payments',
            'erp.reports.client-soa' => 'ar_reports', 'erp.customer-billing.' => 'ar_reports',
            'erp.accounting.' => 'accounting', 'erp.statements.' => 'reports', 'erp.reports.' => 'reports',
            'erp.banking.' => 'banking', 'erp.fx.' => 'fx',
            'erp.tax.' => 'tax', 'erp.users.' => 'user_access_control', 'erp.settings.' => 'finance_settings',
            'erp.approval-matrix' => 'user_access_control', 'erp.export' => 'reports',
        ];

        foreach ($map as $prefix => $mappedModule) {
            if ($name === $prefix || strpos($name, $prefix) === 0) {
                return [$mappedModule, $action ?: $this->actionFromRequest($request, $name)];
            }
        }

        if ($name === 'erp.modules.show') {
            $slug = strtolower((string) $request->route('module'));
            $aliases = ['receivables' => 'invoices', 'billing' => 'invoices', 'collections' => 'payments', 'users' => 'user_access_control'];
            return [$aliases[$slug] ?? $slug, 'view'];
        }

        if ($name === 'erp.action') {
            $slug = strtolower((string) $request->route('action'));
            if (in_array($slug, ['invite-user', 'create-role'], true)) {
                return ['user_access_control', 'create'];
            }
            if ($slug === 'audit-logs') {
                return ['user_access_control', 'view'];
            }
            if (strpos($slug, 'bank') !== false) {
                return ['banking', 'create'];
            }
            if (strpos($slug, 'journal') !== false) {
                return ['accounting', 'create'];
            }
            if (strpos($slug, 'vendor') !== false || strpos($slug, 'pr') !== false) {
                return ['payables', 'create'];
            }
            return ['dashboard', 'view'];
        }

        return ['dashboard', 'view'];
    }

    private function actionFromRequest($request, $routeName = '')
    {
        $routeName = strtolower($routeName ?: (string) optional($request->route())->getName());
        if ($routeName === 'erp.users.review' && request()->filled('user_id')) {
            return 'edit';
        }
        if (in_array($routeName, ['erp.users.status.review', 'erp.billings.status.review', 'erp.banking.reconciliation.review'], true)) {
            return 'edit';
        }
        if (strpos($routeName, '.approve') !== false) {
            return 'approve';
        }
        if (strpos($routeName, '.release') !== false) {
            return 'release';
        }
        if (strpos($routeName, 'delete') !== false || strpos($routeName, 'void') !== false) {
            return 'delete';
        }
        if (strpos($routeName, 'edit') !== false || strpos($routeName, 'update') !== false || strpos($routeName, 'save') !== false || strpos($routeName, 'toggle') !== false || strpos($routeName, 'replace') !== false || strpos($routeName, 'clear') !== false) {
            return 'edit';
        }
        if (strpos($routeName, 'create') !== false || strpos($routeName, 'store') !== false || strpos($routeName, 'review') !== false || strpos($routeName, 'receipt') !== false || strpos($routeName, 'attachments') !== false) {
            return 'create';
        }

        return $request->isMethod('get') ? 'view' : 'edit';
    }
}
