---
paths:
  - 'Modules/Finance/Services/**'
---

# Services

## Posting financial documents
Source documents (invoices, payments, receipts) post their journal via JournalService::postFromSource() inside a transaction that first re-reads the document with lockForUpdate() and re-checks its status. Do not create journals from *Approved event listeners. Journals get a DRAFT-###### reference and their official gapless number only at posting (DocumentNumberService locks the sequence row). Approvals call ensureApproverIsNotCreator() (config finance.controls.*). Never cascade deletes on financial tables.

## Money, currencies and account determination in finance code
Use Modules\Core\Support\Money (bcmath, ISO 4217 minor units) for amounts; never float arithmetic. Journal lines passed to JournalService take TRANSACTION-currency debit/credit; the engine derives functional amounts from the journal rate (only system FX lines use line_type + functional_amount). Get rates only via ExchangeRateService (spot/average/closing, company-specific). Never look accounts up by code pattern: use DefaultAccountService::forPurpose(companyId, AccountPurpose). Invoice tax goes through DocumentTaxService (recalculate/calculations/recordTransactions) and the TaxCalculator contract. Ledger queries use Journal::posted() / LEDGER_STATUSES (POSTED + REVERSED).

## Finance reaches inventory only through the PurchaseMatching and SalesCostOfGoods contracts
Supplier invoices call PurchaseMatching (check on submit and post, costLines when building the journal, invoicePosted after it). Customer invoices call SalesCostOfGoods (check on submit, invoicePosted inside the posting transaction). FinanceServiceProvider binds no-op defaults with bindIf, and InventoryServiceProvider overrides them. Add new stock or matching behaviour to the Inventory implementations (PurchaseInvoiceMatcher, SalesCostService), not to the Finance services. Invoices matched to a purchase order (purchase_order_id set) are not edited through the generic invoice form.
