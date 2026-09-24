---
paths:
  - 'Modules/Finance/Services/**'
---

# Services

## Posting financial documents
Source documents (invoices, payments, receipts) post their journal via JournalService::postFromSource() inside a transaction that first re-reads the document with lockForUpdate() and re-checks its status. Do not create journals from *Approved event listeners. Journals get a DRAFT-###### reference and their official gapless number only at posting (DocumentNumberService locks the sequence row). Approvals call ensureApproverIsNotCreator() (config finance.controls.*). Never cascade deletes on financial tables.
