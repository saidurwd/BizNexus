---
paths:
  - 'Modules/Sales/**'
---

# Sales

## Sales cost of goods goes through SalesOrderCostService (bound over Inventory's)
SalesServiceProvider binds SalesCostOfGoods to Sales\Services\SalesOrderCostService, which extends Inventory\Services\SalesCostService. Deliveries issue stock and move its cost to Goods Delivered Not Invoiced (AccountPurpose::GoodsDeliveredNotInvoiced). An invoice line with sales_order_line_id moves that cost to COGS, and isFulfilledElsewhere() stops the parent from issuing the stock a second time. Invoice lines without an order still issue stock when the invoice posts. Change costing by overriding invoiceCostEntries(), and keep a single cost journal per invoice (cost_journal_id).
