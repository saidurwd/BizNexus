<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Finance Module Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Route name => breadcrumb config
    | 'parent' references the parent route name
    | 'dynamic' indicates the label uses a route parameter
    |
    */

    // Dashboard
    'finance.dashboard' => [
        'label' => 'Dashboard',
        'parent' => null,
    ],

    // Chart of Accounts
    'finance.accounts' => [
        'label' => 'Chart of Accounts',
        'parent' => null,
    ],
    'finance.accounts.index' => [
        'label' => 'Chart of Accounts',
        'parent' => 'finance.accounts',
    ],
    'finance.accounts.create' => [
        'label' => 'Create Account',
        'parent' => 'finance.accounts',
    ],
    'finance.accounts.show' => [
        'label' => 'Account Details',
        'parent' => 'finance.accounts',
        'dynamic' => true,
    ],
    'finance.accounts.edit' => [
        'label' => 'Edit Account',
        'parent' => 'finance.accounts',
        'dynamic' => true,
    ],

    // Journals
    'finance.journals' => [
        'label' => 'Journals',
        'parent' => null,
    ],
    'finance.journals.index' => [
        'label' => 'Journal Register',
        'parent' => 'finance.journals',
    ],
    'finance.journals.create' => [
        'label' => 'New Journal Entry',
        'parent' => 'finance.journals',
    ],
    'finance.journals.show' => [
        'label' => 'Journal Details',
        'parent' => 'finance.journals',
        'dynamic' => true,
    ],

    // General Ledger
    'finance.general-ledger' => [
        'label' => 'General Ledger',
        'parent' => null,
    ],
    'finance.ap-aging' => [
        'label' => 'AP Aging',
        'parent' => null,
    ],
    'finance.ar-aging' => [
        'label' => 'AR Aging',
        'parent' => null,
    ],
    'finance.bank-accounts.index' => [
        'label' => 'Bank Accounts',
        'parent' => null,
    ],

    // Suppliers
    'finance.suppliers' => [
        'label' => 'Suppliers',
        'parent' => null,
    ],
    'finance.suppliers.index' => [
        'label' => 'Supplier List',
        'parent' => 'finance.suppliers',
    ],
    'finance.suppliers.create' => [
        'label' => 'Add Supplier',
        'parent' => 'finance.suppliers',
    ],
    'finance.suppliers.show' => [
        'label' => 'Supplier Details',
        'parent' => 'finance.suppliers',
        'dynamic' => true,
    ],
    'finance.suppliers.edit' => [
        'label' => 'Edit Supplier',
        'parent' => 'finance.suppliers',
        'dynamic' => true,
    ],

    // Customers
    'finance.customers' => [
        'label' => 'Customers',
        'parent' => null,
    ],
    'finance.customers.index' => [
        'label' => 'Customer List',
        'parent' => 'finance.customers',
    ],
    'finance.customers.create' => [
        'label' => 'Add Customer',
        'parent' => 'finance.customers',
    ],
    'finance.customers.show' => [
        'label' => 'Customer Details',
        'parent' => 'finance.customers',
        'dynamic' => true,
    ],
    'finance.customers.edit' => [
        'label' => 'Edit Customer',
        'parent' => 'finance.customers',
        'dynamic' => true,
    ],

    // Payments
    'finance.payments' => [
        'label' => 'Payments',
        'parent' => null,
    ],
    'finance.payments.index' => [
        'label' => 'Payment Register',
        'parent' => 'finance.payments',
    ],
    'finance.payments.create' => [
        'label' => 'New Payment',
        'parent' => 'finance.payments',
    ],
    'finance.payments.show' => [
        'label' => 'Payment Details',
        'parent' => 'finance.payments',
        'dynamic' => true,
    ],

    // Receipts
    'finance.receipts' => [
        'label' => 'Receipts',
        'parent' => null,
    ],
    'finance.receipts.index' => [
        'label' => 'Receipt Register',
        'parent' => 'finance.receipts',
    ],
    'finance.receipts.create' => [
        'label' => 'New Receipt',
        'parent' => 'finance.receipts',
    ],
    'finance.receipts.show' => [
        'label' => 'Receipt Details',
        'parent' => 'finance.receipts',
        'dynamic' => true,
    ],

    // Supplier Invoices
    'finance.supplier-invoices' => [
        'label' => 'Supplier Invoices',
        'parent' => null,
    ],
    'finance.supplier-invoices.index' => [
        'label' => 'Invoice List',
        'parent' => 'finance.supplier-invoices',
    ],
    'finance.supplier-invoices.create' => [
        'label' => 'New Invoice',
        'parent' => 'finance.supplier-invoices',
    ],
    'finance.supplier-invoices.show' => [
        'label' => 'Invoice Details',
        'parent' => 'finance.supplier-invoices',
        'dynamic' => true,
    ],

    // Customer Invoices
    'finance.customer-invoices' => [
        'label' => 'Customer Invoices',
        'parent' => null,
    ],
    'finance.customer-invoices.index' => [
        'label' => 'Invoice List',
        'parent' => 'finance.customer-invoices',
    ],
    'finance.customer-invoices.create' => [
        'label' => 'New Invoice',
        'parent' => 'finance.customer-invoices',
    ],
    'finance.customer-invoices.show' => [
        'label' => 'Invoice Details',
        'parent' => 'finance.customer-invoices',
        'dynamic' => true,
    ],

    // Taxes
    'finance.taxes' => [
        'label' => 'Tax',
        'parent' => null,
    ],
    'finance.taxes.index' => [
        'label' => 'Tax Codes',
        'parent' => 'finance.taxes',
    ],
    'finance.taxes.create' => [
        'label' => 'Add Tax Code',
        'parent' => 'finance.taxes',
    ],
    'finance.taxes.show' => [
        'label' => 'Tax Details',
        'parent' => 'finance.taxes',
        'dynamic' => true,
    ],
    'finance.taxes.edit' => [
        'label' => 'Edit Tax Code',
        'parent' => 'finance.taxes',
        'dynamic' => true,
    ],

    // Reports
    'finance.reports' => [
        'label' => 'Reports',
        'parent' => null,
    ],
    'finance.reports.general-ledger' => [
        'label' => 'General Ledger',
        'parent' => 'finance.reports',
    ],
    'finance.reports.trial-balance' => [
        'label' => 'Trial Balance',
        'parent' => 'finance.reports',
    ],
    'finance.reports.profit-loss' => [
        'label' => 'Profit & Loss',
        'parent' => 'finance.reports',
    ],
    'finance.reports.balance-sheet' => [
        'label' => 'Balance Sheet',
        'parent' => 'finance.reports',
    ],
    'finance.reports.cash-flow' => [
        'label' => 'Cash Flow',
        'parent' => 'finance.reports',
    ],
    'finance.reports.ap' => [
        'label' => 'AP Aging',
        'parent' => 'finance.reports',
    ],
    'finance.reports.ar' => [
        'label' => 'AR Aging',
        'parent' => 'finance.reports',
    ],

    // Cost Centers
    'finance.cost-centers' => [
        'label' => 'Cost Centers',
        'parent' => null,
    ],
    'finance.cost-centers.create' => [
        'label' => 'Add Cost Center',
        'parent' => 'finance.cost-centers',
    ],
    'finance.cost-centers.edit' => [
        'label' => 'Edit Cost Center',
        'parent' => 'finance.cost-centers',
        'dynamic' => true,
    ],
];
