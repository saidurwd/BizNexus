---
paths:
  - 'Modules/Finance/Services/**'
---

# Services

## Posting financial documents
Source documents (invoices, payments, receipts) post their journal via JournalService::postFromSource() inside a transaction that first re-reads the document with lockForUpdate() and re-checks its status. Do not create journals from *Approved event listeners. Journals get a DRAFT-###### reference and their official gapless number only at posting (DocumentNumberService locks the sequence row). Approvals call ensureApproverIsNotCreator() (config finance.controls.*). Never cascade deletes on financial tables.

## Money, currencies and account determination in finance code
Use Modules\Core\Support\Money (bcmath, ISO 4217 minor units) for amounts; never float arithmetic. Journal lines passed to JournalService take TRANSACTION-currency debit/credit; the engine derives functional amounts from the journal rate (only system FX lines use line_type + functional_amount). Get rates only via ExchangeRateService (spot/average/closing, company-specific). Never look accounts up by code pattern: use DefaultAccountService::forPurpose(companyId, AccountPurpose). Invoice tax goes through DocumentTaxService (recalculate/calculations/recordTransactions) and the TaxCalculator contract. Ledger queries use Journal::posted() / LEDGER_STATUSES (POSTED + REVERSED).
