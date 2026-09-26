---
paths:
  - 'Modules/Inventory/**'
---

# Inventory

## Stock only moves through StockService; value is company-wide weighted average
Never write stock_balances, stock_moves or products.stock_quantity/stock_value directly: call StockService::receive/issue/transfer inside the document's transaction, then post the matching journal (functional currency, via JournalService::postFromSource) and set journal_id on the moves. Value is kept per product (company-wide weighted average, IAS 2); warehouses hold quantities only, so transfers post no journal. Moves are never edited or deleted: corrections are new moves. Inventory/COGS accounts come from Product::accountIdFor() (product, then category), falling back to DefaultAccountService::forPurpose(AccountPurpose::Inventory/CostOfGoodsSold).
