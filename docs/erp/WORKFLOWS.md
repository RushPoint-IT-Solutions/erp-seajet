# ERP Workflows

## Invoice to Payment

1. Select or create customer in the customer masterlist.
2. Create invoice.
3. Enter customer ID, job/shipment ID, invoice number, VAT classification, class, credit terms, currency, AR account, and billing lines.
4. Review invoice.
5. Proceed to payment.
6. Receive payment using a supplementary Collection Receipt, Acknowledgement Receipt, or SOA A collection.
7. Print or email receipt.
8. Deposit collection and reconcile bank.

## Customer Aging

1. Select as-of date.
2. Filter by class: MANILA, PAMPANGA, CEBU.
3. Filter by currency: PHP or USD.
4. Filter by VAT classification: 12%, zero-rated, exempt.
5. Bucket unpaid balances into Current, 30, 60, and 90+ days.

## Purchase to Payment

Supplier payments follow a controlled lifecycle: prepare as For Approval, approve by a different authorized user, print and record the cheque where applicable, release and post, then clear through the check register. Approved payments do not affect the ledger until release. Spoiled checks may be replaced before release; released payments require a reversal-based void, and cleared checks require a bank-correction workflow.

1. Create purchase request.
2. Approve request through approval workflow.
3. Generate purchase order.
4. Receive goods or services.
5. Record vendor bill with Shipment Job ID and client tagging.
6. Compute input VAT and withholding tax.
7. Send vendor bill/payment for approval.
8. Process payment by bank, cheque, or online transfer in PHP or USD.
9. Print check voucher and check when cheque payment is selected.
10. Post AP voucher to GL.
11. Reconcile bank transaction.

## Banking and Reconciliation

1. Maintain bank accounts for each operating, payroll, and foreign currency account.
2. Record deposits, withdrawals, fund transfers, or FX conversions in the cashbook.
3. Attach Shipment Job ID when the bank transaction is related to a shipment collection or expense.
4. Capture document currency, exchange rate, foreign amount, and PHP base amount.
5. Review the cashbook running balance by bank account.
6. Prepare bank reconciliation using book balance, bank statement balance, outstanding deposits, and outstanding withdrawals.
7. Review the daily cash position by bank and currency.

## Multi-Currency and FX

1. Maintain USD/PHP rates by date and source.
2. Capture document rate on invoices, SOA A, vendor bills, payments, and bank transactions.
3. When settlement occurs, compare document rate against settlement rate.
4. Post realized FX gain or loss for settled receivables, payables, and bank conversions.
5. At period end, revalue open USD balances using the current reporting rate.
6. Post unrealized FX gain or loss with reference number and Shipment Job ID.

## Customer and Vendor Master

1. Maintain customer credit limit, payment terms, tax classification, class, and default currency.
2. Review customer credit status before creating an invoice.
3. Maintain vendor payment terms, tax classification, preferred payment method, withholding setup, class, and default currency.
4. Use master profile defaults when creating invoices, vendor bills, payments, and tax computations.

## Tax Computation and Reporting

1. Select the document type and reference number.
2. Enter the required Shipment Job ID for traceability.
3. Choose VAT classification: 12% VAT, zero-rated export services, or VAT-exempt.
4. Compute output VAT for sales or input VAT for purchases when 12% VAT applies.
5. Apply expanded withholding tax or creditable withholding tax when required.
6. Review the summary list of sales and summary list of purchases.
7. Review the tax audit trail before filing or period closing.

## Period Closing

1. Review unposted documents.
2. Run AR aging, AP aging, and bank reconciliation.
3. Post recurring journals and accruals.
4. Review trial balance.
5. Generate P&L, balance sheet, cash flow, tax reports.
6. Lock posting period.
