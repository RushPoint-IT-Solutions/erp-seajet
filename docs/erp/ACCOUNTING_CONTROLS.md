# SeaJet Accounting Controls

Updated: 2026-08-21

## Implemented baseline

- Sales invoices and vendor bills are saved as drafts unless an authorized Finance or Administrator user posts them.
- Posting creates a source-linked, balanced journal exactly once. Posted source documents cannot be edited back into drafts.
- Collections post cash/bank, trade receivables, customer deposits, and creditable withholding tax (BIR Form 2307) as applicable.
- Supplier payments post accounts payable and the selected cash/bank account using the entered foreign-exchange rate.
- Posted documents are corrected through a dated reversal journal. The original journal is retained; related cashbook movements are reversed as separate entries.
- Closed fiscal years and closed accounting periods reject new postings and reversals.
- Financial statements and the trial balance use posted journal entries only, avoiding duplicate recognition from source documents.
- Company-scoped queries protect core dashboards, master data, AR/AP queues, cash accounts, journals, and statements from cross-company disclosure.
- Posting, reversal, status, user-access, secure-link, password, and verified webhook events are recorded in the activity log.
- Public invoice links use random 256-bit tokens, expire after 30 days, and are tied to one customer and one invoice.
- Stripe webhook requests require a current, valid HMAC signature and are idempotent by event ID.
- Known demo passwords are flagged for compulsory change. User creation, update, deactivation, and status changes are database-backed and audited.
- Demo API endpoints require API authentication and no longer expose sample records anonymously.

## Controlled supplier-payment and check workflow

- New supplier payments are always submitted as **For Approval**. The creation screen cannot directly mark a payment approved or released.
- Maker-checker control prevents the recorded preparer from approving or releasing the same payment.
- Approval does not create a journal entry. Release is a separate action and is the point at which the AP/cash journal and cashbook movement are created.
- Payment amount must be positive, have no more than two decimal places, match the bill and bank currency, and not exceed the current bill balance.
- A cheque requires an active company bank account and a check number that has never been used for that bank account, including voided and replaced stock.
- Check printing is restricted to approved or released cheque payments. The first print and every reprint create an append-only event containing the user, time, immutable printed values, layout version, payload hash, device metadata, and reprint reason.
- Payee and amount-in-words values are snapshotted on the payment/check issuance so later supplier-master changes cannot alter historical print evidence.
- Check statuses are assigned, printed, released, cleared, voided, replaced, or exception. The check register retains the complete chain.
- A released payment may be voided only through a reversal journal and bank-movement reversal. A cleared check is blocked from ordinary voiding and requires a documented bank-correction process.
- Layouts are versioned by company, bank account, profile, and printer. Physical dimensions, feed orientation, and global X/Y offsets are stored with the field matrix.

## Philippine invoicing treatment

Revenue Regulations No. 7-2024 treats an invoice as the principal document evidencing a sale of goods or services. Collection and acknowledgement receipts are supplementary documents. SeaJet therefore labels the collection document as **Collection Receipt** while keeping the legacy route/template identifier for backward compatibility.

Before production use, the printed VAT or Non-VAT Invoice must be matched to the taxpayer's BIR registration, Authority to Print or approved computerized invoicing setup, registered name, TIN/branch code, address, serial numbering, VAT classification, and other mandatory invoice particulars.

References:

- [BIR Revenue Regulations No. 7-2024](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%207-%202024.pdf)
- [BIR Revenue Memorandum Order No. 9-2021](https://bir-cdn.bir.gov.ph/local/pdf/RMO%20No.%209-2021.pdf)
- [BIR Revenue Memorandum Circular No. 65-2025 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2065-2025%20Digest.pdf)
- [BIR Citizen's Charter, 2025 Edition](https://bir-cdn.bir.gov.ph/BIR/pdf/BIR%20Citizen%27s%20Charter%20%282025-Edition%29.pdf)

## Configuration required before live posting

1. Replace all demo company, TIN, address, branch, customer, supplier, bank, and opening-balance data with approved production masters.
2. Review and approve every control-account mapping: cash, bank, trade AR, reimbursable AR, input VAT, CWT receivable, AP, output VAT, EWT payable, deposits, revenue, and expense.
3. Enter the fiscal year and accounting periods, then test month-end closing and authorized reopening.
4. Load official document number series and approved print layouts. Never reuse or manually overwrite issued serial numbers.
   Measure each bank's actual check stock, qualify each physical printer at 100% scale, save a named bank/printer profile, and retain the signed calibration test sheet.
5. Confirm VAT, zero-rated, VAT-exempt, EWT, and CWT rules with the company's CPA/tax adviser and current Certificate of Registration.
6. Register the CAS/CBA or applicable component with the BIR and obtain the required Acknowledgment Certificate or permit before operational use.
7. Configure HTTPS, production mail, backups, restore testing, log retention, Stripe secrets if online payment will be used, and separate named user accounts.
8. Reconcile opening AR, AP, bank, tax, and general-ledger balances to the last approved manual or legacy books before cutover.

## Not yet certified or production-complete

- This code change is an accounting-control baseline, not BIR approval, an audit opinion, or a CAS certification.
- Bank-statement import/matching and final bank-reconciliation persistence are still presentation prototypes.
- The check register can record bank clearing manually, but automatic statement matching to check issuance is not yet implemented.
- FX conversion and period-end realized/unrealized revaluation screens are still prototypes.
- Tax-return preparation, SLSP/QAP/alphalist generation, eFPS/eBIRForms submission, and electronic invoicing transmission are not implemented.
- Inventory costing, payroll, fixed assets, budget controls, purchase-order receiving, and three-way matching are outside the current posting scope.
- The read-only API remains a stub and should not be enabled until token lifecycle, scopes, rate limiting, and real resource controllers are implemented.

These remaining items must be completed or formally handled outside SeaJet before the system is represented as the company's complete production CAS.
