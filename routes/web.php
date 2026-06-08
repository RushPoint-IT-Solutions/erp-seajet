<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Erp\DashboardController@index')->name('erp.dashboard');
Route::get('/erp/transactions/data', 'Erp\DashboardController@transactions')->name('erp.transactions.data');
Route::get('/client/invoices/{token}', 'Erp\ModuleController@publicInvoice')->name('erp.public.invoice');
Route::get('/client/invoices/{token}/checkout', 'Erp\ModuleController@publicCheckout')->name('erp.public.checkout');
Route::post('/webhooks/stripe', 'Erp\ModuleController@stripeWebhook')->name('erp.webhooks.stripe');
Route::middleware('user.access:administrator,admin,accounting,finance')->group(function () {
    Route::get('/erp/notifications', 'Erp\ModuleController@notifications')->name('erp.notifications');
    Route::get('/erp/action/approval-matrix', 'Erp\ModuleController@approvalMatrix')->name('erp.approval-matrix');
    Route::get('/erp/action/{action}', 'Erp\ModuleController@action')->name('erp.action');
    Route::get('/erp/export/{type}', 'Erp\ModuleController@export')->name('erp.export');
    Route::get('/erp/customers', 'Erp\ModuleController@customers')->name('erp.customers.index');
    Route::get('/erp/customers/create', 'Erp\ModuleController@customerCreate')->name('erp.customers.create');
    Route::get('/erp/suppliers', 'Erp\ModuleController@suppliers')->name('erp.suppliers.index');
    Route::get('/erp/suppliers/create', 'Erp\ModuleController@supplierCreate')->name('erp.suppliers.create');
});
Route::prefix('/erp/users')->name('erp.users.')->middleware('user.access:administrator,admin')->group(function () {
    Route::get('/', 'Erp\ModuleController@usersIndex')->name('index');
    Route::get('/create', 'Erp\ModuleController@userCreate')->name('create');
    Route::post('/review', 'Erp\ModuleController@userReview')->name('review');
    Route::get('/{user}/edit', 'Erp\ModuleController@userEdit')->name('edit');
    Route::get('/{user}/delete', 'Erp\ModuleController@userDelete')->name('delete');
    Route::post('/delete/review', 'Erp\ModuleController@userDeleteReview')->name('delete.review');
    Route::get('/status/update', 'Erp\ModuleController@userStatus')->name('status');
    Route::post('/status/review', 'Erp\ModuleController@userStatusReview')->name('status.review');
});
Route::middleware('user.access:administrator,admin,accounting,finance')->group(function () {
    Route::get('/erp/payables/vendor-bills/create', 'Erp\ModuleController@vendorBillCreate')->name('erp.payables.vendor-bills.create');
    Route::post('/erp/payables/vendor-bills/review', 'Erp\ModuleController@vendorBillReview')->name('erp.payables.vendor-bills.review');
    Route::get('/erp/expenses', 'Erp\ModuleController@expensesIndex')->name('erp.expenses.index');
    Route::get('/erp/payables/payments/create', 'Erp\ModuleController@apPaymentCreate')->name('erp.payables.payments.create');
    Route::post('/erp/payables/payments/review', 'Erp\ModuleController@apPaymentReview')->name('erp.payables.payments.review');
    Route::get('/erp/payables/payment-approvals', 'Erp\ModuleController@paymentApprovals')->name('erp.payables.approvals');
    Route::get('/erp/payables/check-voucher/preview', 'Erp\ModuleController@checkVoucherPreview')->name('erp.payables.check-voucher');
    Route::get('/erp/payables/check/print', 'Erp\ModuleController@checkPrint')->name('erp.payables.check-print');
    Route::get('/erp/payables/aging', 'Erp\ModuleController@apAging')->name('erp.payables.aging');

    Route::get('/erp/banking/cashbook', 'Erp\ModuleController@cashbook')->name('erp.banking.cashbook');
    Route::get('/erp/banking/transactions/create', 'Erp\ModuleController@bankTransactionCreate')->name('erp.banking.transactions.create');
    Route::post('/erp/banking/transactions/review', 'Erp\ModuleController@bankTransactionReview')->name('erp.banking.transactions.review');
    Route::get('/erp/banking/reconciliation', 'Erp\ModuleController@bankReconciliation')->name('erp.banking.reconciliation');
    Route::post('/erp/banking/reconciliation/review', 'Erp\ModuleController@bankReconciliationReview')->name('erp.banking.reconciliation.review');
    Route::get('/erp/banking/fx-tracking', 'Erp\ModuleController@fxTracking')->name('erp.banking.fx');
    Route::get('/erp/banking/daily-cash-position', 'Erp\ModuleController@dailyCashPosition')->name('erp.banking.daily-cash');

    Route::get('/erp/fx', 'Erp\ModuleController@multiCurrencyFx')->name('erp.fx.index');
    Route::get('/erp/fx/conversion', 'Erp\ModuleController@fxConversionCreate')->name('erp.fx.conversion');
    Route::post('/erp/fx/conversion/review', 'Erp\ModuleController@fxConversionReview')->name('erp.fx.conversion.review');
    Route::get('/erp/fx/gains-losses', 'Erp\ModuleController@fxGainsLosses')->name('erp.fx.gains-losses');

    Route::get('/erp/accounting/chart-of-accounts', 'Erp\ModuleController@chartOfAccounts')->name('erp.accounting.coa');
    Route::get('/erp/accounting/journal-entries', 'Erp\ModuleController@journalEntries')->name('erp.accounting.journals');
    Route::post('/erp/accounting/journal-entries/review', 'Erp\ModuleController@journalEntryReview')->name('erp.accounting.journals.review');

    Route::get('/erp/statements/balance-sheet', 'Erp\ModuleController@balanceSheet')->name('erp.statements.balance-sheet');
    Route::get('/erp/statements/profit-loss', 'Erp\ModuleController@profitLoss')->name('erp.statements.profit-loss');
    Route::get('/erp/statements/income-statement', 'Erp\ModuleController@incomeStatement')->name('erp.statements.income-statement');
    Route::get('/erp/statements/cash-flow', 'Erp\ModuleController@cashFlow')->name('erp.statements.cash-flow');

    Route::get('/erp/tax/vat-summary', 'Erp\ModuleController@vatSummary')->name('erp.tax.vat-summary');
    Route::get('/erp/tax/withholding', 'Erp\ModuleController@withholdingTax')->name('erp.tax.withholding');
    Route::get('/erp/tax/calculator', 'Erp\ModuleController@taxCalculator')->name('erp.tax.calculator');
    Route::post('/erp/tax/calculator/review', 'Erp\ModuleController@taxCalculatorReview')->name('erp.tax.calculator.review');
    Route::get('/erp/tax/sales-purchases', 'Erp\ModuleController@taxSalesPurchases')->name('erp.tax.sales-purchases');
    Route::get('/erp/tax/audit-trail', 'Erp\ModuleController@taxAuditTrail')->name('erp.tax.audit-trail');

    Route::get('/erp/billings', 'Erp\ModuleController@billingIndex')->name('erp.billings.index');
    Route::get('/erp/billings/{invoice}/edit', 'Erp\ModuleController@billingEdit')->name('erp.billings.edit');
    Route::get('/erp/billings/{invoice}/delete', 'Erp\ModuleController@billingDelete')->name('erp.billings.delete');
    Route::post('/erp/billings/delete/review', 'Erp\ModuleController@billingDeleteReview')->name('erp.billings.delete.review');
    Route::get('/erp/billings/status/update', 'Erp\ModuleController@billingStatus')->name('erp.billings.status');
    Route::post('/erp/billings/status/review', 'Erp\ModuleController@billingStatusReview')->name('erp.billings.status.review');
    Route::get('/erp/billings/print-layout/invoice', 'Erp\ModuleController@billingInvoicePrintLayout')->name('erp.billings.print-layout.invoice');
    Route::post('/erp/billings/print-layout/invoice/save', 'Erp\ModuleController@saveBillingInvoicePrintLayout')->name('erp.billings.print-layout.invoice.save');
    Route::get('/erp/billings/print-layout/soa-b', 'Erp\ModuleController@soaBPrintLayout')->name('erp.billings.print-layout.soa-b');
    Route::post('/erp/billings/print-layout/soa-b/save', 'Erp\ModuleController@saveSoaBPrintLayout')->name('erp.billings.print-layout.soa-b.save');
    Route::get('/erp/billings/print-layout/soa-a', 'Erp\ModuleController@soaAPrintLayout')->name('erp.billings.print-layout.soa-a');
    Route::post('/erp/billings/print-layout/soa-a/save', 'Erp\ModuleController@saveSoaAPrintLayout')->name('erp.billings.print-layout.soa-a.save');
    Route::get('/erp/billings/print/invoice', 'Erp\ModuleController@invoicePreview')->name('erp.billings.print-invoice');
    Route::get('/erp/billings/print/statement-of-account', 'Erp\ModuleController@soaPreview')->name('erp.billings.print-soa');

    Route::get('/erp/invoices', 'Erp\ModuleController@invoiceIndex')->name('erp.invoices.index');
    Route::get('/erp/invoices/create', 'Erp\ModuleController@invoiceCreate')->name('erp.invoices.create');
    Route::post('/erp/invoices/review', 'Erp\ModuleController@invoiceReview')->name('erp.invoices.review');
    Route::get('/erp/payments/create', 'Erp\ModuleController@paymentCreate')->name('erp.payments.create');
    Route::post('/erp/payments/receipt', 'Erp\ModuleController@paymentReceipt')->name('erp.payments.receipt');

    Route::get('/erp/invoice/preview', 'Erp\ModuleController@invoicePreview')->name('erp.invoice.preview');
    Route::get('/erp/statement-of-account/preview', 'Erp\ModuleController@soaPreview')->name('erp.soa.preview');
    Route::get('/erp/statement-of-account-a/preview', 'Erp\ModuleController@soaAPreview')->name('erp.soa-a.preview');
    Route::get('/erp/debit-note/preview', 'Erp\ModuleController@debitNotePreview')->name('erp.debit-note.preview');
    Route::get('/erp/official-receipt/preview', 'Erp\ModuleController@officialReceiptPreview')->name('erp.official-receipt.preview');
    Route::get('/erp/acknowledgement-receipt/preview', 'Erp\ModuleController@acknowledgementReceiptPreview')->name('erp.acknowledgement-receipt.preview');

    Route::get('/erp/reports', 'Erp\ModuleController@reportsIndex')->name('erp.reports.index');
    Route::get('/erp/reports/financial-statements', 'Erp\ModuleController@financialStatementsReport')->name('erp.reports.financial-statements');
    Route::get('/erp/customer-billing/templates', 'Erp\ModuleController@customerBillingTemplates')->name('erp.customer-billing.templates');
    Route::get('/erp/customer-billing/aging', 'Erp\ModuleController@agingReport')->name('erp.customer-billing.aging');
});
Route::get('/erp/settings', 'Erp\ModuleController@financeSettings')->name('erp.settings.index')->middleware('user.access:administrator,admin,accounting');
Route::get('/erp/{module}', 'Erp\ModuleController@show')->name('erp.modules.show')->middleware('user.access:administrator,admin,accounting,finance');
