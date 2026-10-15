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

Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/login', 'Auth\LoginController@login');
Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
Route::get('/password/change', 'Auth\ChangePasswordController@show')->middleware('auth')->name('password.change');
Route::post('/password/change', 'Auth\ChangePasswordController@update')->middleware('auth')->name('password.update');

Route::get('/client/invoices/{token}', 'Erp\ModuleController@publicInvoice')->name('erp.public.invoice');
Route::get('/client/invoices/{token}/checkout', 'Erp\ModuleController@publicCheckout')->name('erp.public.checkout');
Route::post('/webhooks/stripe', 'Erp\ModuleController@stripeWebhook')->name('erp.webhooks.stripe');

Route::middleware(['auth', 'permission.access'])->group(function () {
    Route::get('/', 'Erp\DashboardController@index')->name('erp.dashboard');
    Route::get('/erp/transactions/data', 'Erp\DashboardController@transactions')->name('erp.transactions.data');
    Route::get('/erp/notifications', 'Erp\ModuleController@notifications')->name('erp.notifications');
    Route::get('/erp/action/approval-matrix', 'Erp\ModuleController@approvalMatrix')->name('erp.approval-matrix');
    Route::get('/erp/action/{action}', 'Erp\ModuleController@action')->name('erp.action');
    Route::get('/erp/export/{type}', 'Erp\ModuleController@export')->name('erp.export');
    Route::get('/erp/customers', 'Erp\ModuleController@customers')->name('erp.customers.index');
    Route::get('/erp/customers/create', 'Erp\ModuleController@customerCreate')->name('erp.customers.create');
    Route::post('/erp/customers', 'Erp\ModuleController@customerStore')->name('erp.customers.store');
    Route::get('/erp/customers/{customer}/edit', 'Erp\ModuleController@customerEdit')->name('erp.customers.edit');
    Route::put('/erp/customers/{customer}', 'Erp\ModuleController@customerUpdate')->name('erp.customers.update');
    Route::get('/erp/customers/{customer}', 'Erp\ModuleController@customerShow')->name('erp.customers.show');
    Route::get('/erp/suppliers', 'Erp\ModuleController@suppliers')->name('erp.suppliers.index');
    Route::get('/erp/suppliers/create', 'Erp\ModuleController@supplierCreate')->name('erp.suppliers.create');
    Route::post('/erp/suppliers', 'Erp\ModuleController@supplierStore')->name('erp.suppliers.store');
    Route::get('/erp/suppliers/{supplier}/edit', 'Erp\ModuleController@supplierEdit')->name('erp.suppliers.edit');
    Route::put('/erp/suppliers/{supplier}', 'Erp\ModuleController@supplierUpdate')->name('erp.suppliers.update');
});
Route::prefix('/erp/users')->name('erp.users.')->middleware(['auth', 'permission.access'])->group(function () {
    Route::get('/', 'Erp\ModuleController@usersIndex')->name('index');
    Route::get('/create', 'Erp\ModuleController@userCreate')->name('create');
    Route::post('/review', 'Erp\ModuleController@userReview')->name('review');
    Route::get('/{user}/edit', 'Erp\ModuleController@userEdit')->name('edit');
    Route::get('/{user}/delete', 'Erp\ModuleController@userDelete')->name('delete');
    Route::post('/delete/review', 'Erp\ModuleController@userDeleteReview')->name('delete.review');
    Route::get('/status/update', 'Erp\ModuleController@userStatus')->name('status');
    Route::post('/status/review', 'Erp\ModuleController@userStatusReview')->name('status.review');
    Route::post('/roles', 'Erp\ModuleController@roleStore')->name('roles.store');
    Route::put('/roles/{role}/permissions', 'Erp\ModuleController@rolePermissionsUpdate')->name('roles.permissions.update');
});
Route::middleware(['auth', 'permission.access'])->group(function () {
    Route::get('/erp/payables/vendor-bills/create', 'Erp\ModuleController@vendorBillCreate')->name('erp.payables.vendor-bills.create');
    Route::get('/erp/payables/vendor-bills/view/{bill}', 'Erp\ModuleController@vendorBillView')->name('erp.payables.vendor-bills.view');
    Route::get('/erp/payables/vendor-bills/print/{bill}', 'Erp\ModuleController@vendorBillPrint')->name('erp.payables.vendor-bills.print');
    Route::post('/erp/payables/vendor-bills/print-2307/layout/save', 'Erp\ModuleController@saveVendorBill2307Layout')->name('erp.payables.vendor-bills.print-2307.layout.save');
    Route::get('/erp/payables/vendor-bills/print-2307/{bill}', 'Erp\ModuleController@vendorBill2307Print')->name('erp.payables.vendor-bills.print-2307');
    Route::post('/erp/payables/vendor-bills/{bill}/attachments', 'Erp\ModuleController@uploadVendorBillAttachments')->name('erp.payables.vendor-bills.attachments');
    Route::delete('/erp/payables/vendor-bills/{bill}', 'Erp\ModuleController@deleteVendorBill')->name('erp.payables.vendor-bills.delete');
    Route::post('/erp/payables/vendor-bills/review', 'Erp\ModuleController@vendorBillReview')->name('erp.payables.vendor-bills.review');
    Route::get('/erp/expenses', 'Erp\ModuleController@expensesIndex')->name('erp.expenses.index');
    Route::get('/erp/payables/payments/create', 'Erp\ModuleController@apPaymentCreate')->name('erp.payables.payments.create');
    Route::post('/erp/payables/payments/review', 'Erp\ModuleController@apPaymentReview')->name('erp.payables.payments.review');
    Route::get('/erp/payables/payment-approvals', 'Erp\ModuleController@paymentApprovals')->name('erp.payables.approvals');
    Route::post('/erp/payables/payments/{payment}/update-preparation', 'Erp\ModuleController@updateApPaymentPreparation')->name('erp.payables.payments.update-preparation');
    Route::post('/erp/payables/payments/{payment}/approve', 'Erp\ModuleController@approveApPayment')->name('erp.payables.payments.approve');
    Route::post('/erp/payables/payments/{payment}/release', 'Erp\ModuleController@releaseApPayment')->name('erp.payables.payments.release');
    Route::post('/erp/payables/payments/{payment}/void', 'Erp\ModuleController@voidApPayment')->name('erp.payables.payments.void');
    Route::get('/erp/payables/check-register', 'Erp\ModuleController@checkRegister')->name('erp.payables.check-register');
    Route::post('/erp/payables/checks/{check}/replace', 'Erp\ModuleController@replaceCheck')->name('erp.payables.checks.replace');
    Route::post('/erp/payables/checks/{check}/clear', 'Erp\ModuleController@clearCheck')->name('erp.payables.checks.clear');
    Route::get('/erp/payables/check-voucher/print-layout', 'Erp\ModuleController@checkVoucherPrintLayout')->name('erp.payables.check-voucher.layout');
    Route::post('/erp/payables/check-voucher/print-layout/save', 'Erp\ModuleController@saveCheckVoucherPrintLayout')->name('erp.payables.check-voucher.layout.save');
    Route::get('/erp/payables/check-voucher/bill/{bill}', 'Erp\ModuleController@checkVoucherForBill')->name('erp.payables.check-voucher.bill');
    Route::get('/erp/payables/check-voucher/preview/{payment?}', 'Erp\ModuleController@checkVoucherPreview')->name('erp.payables.check-voucher');
    Route::post('/erp/payables/check-voucher/{payment}/update', 'Erp\ModuleController@updateCheckVoucher')->name('erp.payables.check-voucher.update');
    Route::post('/erp/payables/check-voucher/print/{payment}/event', 'Erp\ModuleController@recordCheckVoucherPrint')->name('erp.payables.check-voucher.print.event');
    Route::post('/erp/payables/check/print-layout/save', 'Erp\ModuleController@saveCheckPrintLayout')->name('erp.payables.check-print.layout.save');
    Route::post('/erp/payables/check/print/{payment}/payee', 'Erp\ModuleController@updateCheckPayee')->name('erp.payables.check-print.payee.update');
    Route::post('/erp/payables/check/print/{payment}/event', 'Erp\ModuleController@recordCheckPrint')->name('erp.payables.check-print.event');
    Route::get('/erp/payables/check/print/{payment?}', 'Erp\ModuleController@checkPrint')->name('erp.payables.check-print');
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
    Route::get('/erp/accounting/chart-of-accounts/create', 'Erp\ModuleController@chartOfAccountsCreate')->name('erp.accounting.coa.create');
    Route::post('/erp/accounting/chart-of-accounts', 'Erp\ModuleController@chartOfAccountsStore')->name('erp.accounting.coa.store');
    Route::get('/erp/accounting/chart-of-accounts/{account}/edit', 'Erp\ModuleController@chartOfAccountsEdit')->name('erp.accounting.coa.edit');
    Route::put('/erp/accounting/chart-of-accounts/{account}', 'Erp\ModuleController@chartOfAccountsUpdate')->name('erp.accounting.coa.update');
    Route::post('/erp/accounting/chart-of-accounts/{account}/toggle-status', 'Erp\ModuleController@chartOfAccountsToggleStatus')->name('erp.accounting.coa.toggle-status');
    Route::get('/erp/accounting/journal-entries', 'Erp\ModuleController@journalEntries')->name('erp.accounting.journals');
    Route::post('/erp/accounting/journal-entries/review', 'Erp\ModuleController@journalEntryReview')->name('erp.accounting.journals.review');
    Route::get('/erp/accounting/opening-balances/manual', 'Erp\ModuleController@manualOpeningBalance')->name('erp.accounting.opening-balances.manual');
    Route::post('/erp/accounting/opening-balances/manual', 'Erp\ModuleController@storeManualOpeningBalance')->name('erp.accounting.opening-balances.manual.store');
    Route::get('/erp/accounting/opening-balances/import', 'Erp\ModuleController@openingBalanceImport')->name('erp.accounting.opening-balances.import');
    Route::delete('/erp/accounting/opening-balances/ar/{row}', 'Erp\ModuleController@deleteImportedArBalance')->name('erp.accounting.opening-balances.ar.delete');
    Route::get('/erp/accounting/opening-balances/ar-export', 'Erp\ModuleController@exportImportedArBalances')->name('erp.accounting.opening-balances.ar.export');
    Route::delete('/erp/accounting/opening-balances/ar-delete-all', 'Erp\ModuleController@deleteAllImportedArBalances')->name('erp.accounting.opening-balances.ar.delete-all');
    Route::post('/erp/accounting/opening-balances/ar/{row}/payments/{receipt}/void', 'Erp\ModuleController@voidImportedArPayment')->name('erp.accounting.opening-balances.ar.payment.void');
    Route::post('/erp/accounting/opening-balances/import/preview', 'Erp\ModuleController@openingBalanceImportPreview')->name('erp.accounting.opening-balances.preview');
    Route::post('/erp/accounting/opening-balances/import/commit', 'Erp\ModuleController@openingBalanceImportCommit')->name('erp.accounting.opening-balances.commit');
    Route::get('/erp/accounting/journal-entries/{journal}', 'Erp\ModuleController@journalEntryShow')->name('erp.accounting.journals.show');

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
    Route::get('/erp/billings/{invoice}/void', 'Erp\ModuleController@billingVoid')->name('erp.billings.void');
    Route::post('/erp/billings/{invoice}/void', 'Erp\ModuleController@billingVoidStore')->name('erp.billings.void.store');
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
    Route::get('/erp/invoices/{invoice}', 'Erp\ModuleController@invoiceShow')->name('erp.invoices.show');
    Route::post('/erp/invoices/{invoice}/portal-link', 'Erp\ModuleController@createInvoicePortalLink')->name('erp.invoices.portal-link');
    Route::post('/erp/invoices/review', 'Erp\ModuleController@invoiceReview')->name('erp.invoices.review');
    Route::get('/erp/payments', 'Erp\ModuleController@paymentCreate')->name('erp.payments.index');
    Route::get('/erp/payments/create', 'Erp\ModuleController@paymentCreate')->name('erp.payments.create');
    Route::post('/erp/payments/receipt', 'Erp\ModuleController@paymentReceipt')->name('erp.payments.receipt');
    Route::post('/erp/payments/{receipt}/correct', 'Erp\ModuleController@correctPayment')->name('erp.payments.correct');
    Route::delete('/erp/payments/{receipt}', 'Erp\ModuleController@deletePayment')->name('erp.payments.delete');

    Route::get('/erp/invoice/preview', 'Erp\ModuleController@invoicePreview')->name('erp.invoice.preview');
    Route::get('/erp/statement-of-account/preview', 'Erp\ModuleController@soaPreview')->name('erp.soa.preview');
    Route::get('/erp/statement-of-account-a/preview', 'Erp\ModuleController@soaAPreview')->name('erp.soa-a.preview');
    Route::get('/erp/debit-note/preview', 'Erp\ModuleController@debitNotePreview')->name('erp.debit-note.preview');
    Route::get('/erp/official-receipt/preview', 'Erp\ModuleController@officialReceiptPreview')->name('erp.official-receipt.preview');
    Route::post('/erp/official-receipt/print-layout/save', 'Erp\ModuleController@saveOfficialReceiptPrintLayout')->name('erp.official-receipt.layout.save');
    Route::get('/erp/acknowledgement-receipt/preview', 'Erp\ModuleController@acknowledgementReceiptPreview')->name('erp.acknowledgement-receipt.preview');
    Route::post('/erp/acknowledgement-receipt/print-layout/save', 'Erp\ModuleController@saveAcknowledgementReceiptPrintLayout')->name('erp.acknowledgement-receipt.layout.save');
    Route::get('/erp/non-vat-acknowledgement-report/preview', 'Erp\ModuleController@nonVatAcknowledgementPreview')->name('erp.non-vat-acknowledgement.preview');
    Route::post('/erp/non-vat-acknowledgement-report/print-layout/save', 'Erp\ModuleController@saveNonVatAcknowledgementPrintLayout')->name('erp.non-vat-acknowledgement.layout.save');

    Route::get('/erp/reports', 'Erp\ModuleController@reportsIndex')->name('erp.reports.index');
    Route::get('/erp/reports/trial-balance', 'Erp\ModuleController@trialBalanceReport')->name('erp.reports.trial-balance');
    Route::get('/erp/reports/trial-balance/drilldown', 'Erp\ModuleController@trialBalanceDrilldown')->name('erp.reports.trial-balance.drilldown');
    Route::get('/erp/reports/financial-statements', 'Erp\ModuleController@financialStatementsReport')->name('erp.reports.financial-statements');
    Route::get('/erp/reports/workbook/{report?}', 'Erp\ModuleController@workbookReport')->name('erp.reports.workbook');
    Route::get('/erp/reports/client-statement-of-account', 'Erp\ModuleController@clientStatementOfAccount')->name('erp.reports.client-soa');
    Route::get('/erp/customer-billing/templates', 'Erp\ModuleController@customerBillingTemplates')->name('erp.customer-billing.templates');
    Route::get('/erp/customer-billing/aging', 'Erp\ModuleController@agingReport')->name('erp.customer-billing.aging');
});
Route::get('/erp/settings', 'Erp\ModuleController@financeSettings')->name('erp.settings.index')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/company', 'Erp\ModuleController@saveFinanceCompanySettings')->name('erp.settings.company.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/bir-2307/payee-representative', 'Erp\ModuleController@saveFinanceBir2307PayeeRepresentative')->name('erp.settings.bir-2307.payee.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/branches', 'Erp\ModuleController@saveFinanceBranch')->name('erp.settings.branches.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/cost-centers', 'Erp\ModuleController@saveFinanceCostCenter')->name('erp.settings.cost-centers.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/items', 'Erp\ModuleController@saveFinanceItem')->name('erp.settings.items.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/number-series', 'Erp\ModuleController@saveFinanceNumberSeries')->name('erp.settings.number-series.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/tax-codes', 'Erp\ModuleController@saveFinanceTaxCode')->name('erp.settings.tax-codes.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/currency-rates', 'Erp\ModuleController@saveFinanceCurrencyRate')->name('erp.settings.currency-rates.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/bank-accounts', 'Erp\ModuleController@saveFinanceBankAccount')->name('erp.settings.bank-accounts.save')->middleware(['auth', 'permission.access']);
Route::post('/erp/settings/transaction-data/reset', 'Erp\ModuleController@resetTransactionData')->name('erp.settings.transaction-data.reset')->middleware(['auth', 'permission.access']);
Route::get('/erp/system-update', 'Erp\SystemUpdateController@index')->name('erp.system-update.index')->middleware(['auth', 'permission.access:system_updates,view']);
Route::get('/erp/system-update/status', 'Erp\SystemUpdateController@status')->name('erp.system-update.status')->middleware(['auth', 'permission.access:system_updates,view']);
Route::post('/erp/system-update/run', 'Erp\SystemUpdateController@run')->name('erp.system-update.run')->middleware(['auth', 'permission.access:system_updates,edit']);
Route::get('/erp/{module}', 'Erp\ModuleController@show')->name('erp.modules.show')->middleware(['auth', 'permission.access']);
