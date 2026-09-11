<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Currency;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Services\DocumentNumberService;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->createCurrencies();
        $this->createDemoCompany();
    }

    protected function createCurrencies(): void
    {
        $currencies = [
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'decimal_places' => 2],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency + ['status' => 'active']
            );
        }
    }

    protected function createDemoCompany(): void
    {
        $currency = Currency::where('code', 'BDT')->first();

        $company = Company::firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Company Ltd.',
                'legal_name' => 'Demo Company Limited',
                'address' => '123 Business Street, Dhaka, Bangladesh',
                'phone' => '+880 1234-567890',
                'email' => 'info@democompany.com',
                'tax_number' => 'TAX-123456789',
                'registration_number' => 'REG-123456',
                'base_currency_id' => $currency->id,
                'timezone' => 'Asia/Dhaka',
                'fiscal_year_start' => now()->startOfYear()->format('Y-m-d'),
                'status' => 'active',
            ]
        );

        $this->createFiscalYear($company);
        $this->createChartOfAccounts($company);
        $this->initializeDocumentSequences($company);
    }

    protected function createFiscalYear(Company $company): void
    {
        $startDate = now()->startOfYear();
        $endDate = now()->endOfYear();

        $fiscalYear = FiscalYear::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => now()->format('Y'),
            ],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'OPEN',
                'is_current' => true,
            ]
        );

        $periodStart = $startDate->copy();
        for ($i = 1; $i <= 12; $i++) {
            $periodEnd = $periodStart->copy()->endOfMonth();

            if ($periodEnd->gt($endDate)) {
                $periodEnd = $endDate;
            }

            FiscalPeriod::firstOrCreate(
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'period_number' => $i,
                ],
                [
                    'period_name' => $periodStart->format('F'),
                    'start_date' => $periodStart->format('Y-m-d'),
                    'end_date' => $periodEnd->format('Y-m-d'),
                    'status' => 'OPEN',
                ]
            );

            $periodStart = $periodEnd->copy()->addDay();
        }
    }

    protected function createAccountCategories(Company $company): void
    {
        $categories = [
            ['code' => 'OPE', 'name' => 'Operating', 'description' => 'Core business operating accounts', 'sort_order' => 1],
            ['code' => 'NON', 'name' => 'Non-Operating', 'description' => 'Non-operating income and expenses', 'sort_order' => 2],
            ['code' => 'DIR', 'name' => 'Direct', 'description' => 'Direct costs related to revenue', 'sort_order' => 3],
            ['code' => 'IND', 'name' => 'Indirect', 'description' => 'Indirect operating expenses', 'sort_order' => 4],
            ['code' => 'ADM', 'name' => 'Administrative', 'description' => 'Administrative expenses', 'sort_order' => 5],
            ['code' => 'FIN', 'name' => 'Financial', 'description' => 'Financial costs and income', 'sort_order' => 6],
            ['code' => 'TAX', 'name' => 'Tax', 'description' => 'Tax related accounts', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            \Modules\Finance\Models\AccountCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $category['code'],
                ],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                    'status' => 'active',
                ]
            );
        }
    }

    protected function createChartOfAccounts(Company $company): void
    {
        $this->createAccountCategories($company);

        $accounts = [
            '1' => ['code' => '1000', 'name' => 'Assets', 'type' => 'ASSET', 'is_group' => true],
            '1.1' => ['code' => '1100', 'name' => 'Current Assets', 'type' => 'ASSET', 'is_group' => true],
            '1.1.1' => ['code' => '1110', 'name' => 'Cash', 'type' => 'ASSET', 'is_group' => false],
            '1.1.2' => ['code' => '1120', 'name' => 'Bank', 'type' => 'ASSET', 'is_group' => false],
            '1.1.3' => ['code' => '1130', 'name' => 'Accounts Receivable', 'type' => 'ASSET', 'is_group' => false],
            '1.1.4' => ['code' => '1140', 'name' => 'Inventory', 'type' => 'ASSET', 'is_group' => false],
            '1.2' => ['code' => '1200', 'name' => 'Fixed Assets', 'type' => 'ASSET', 'is_group' => true],
            '1.2.1' => ['code' => '1210', 'name' => 'Property & Equipment', 'type' => 'ASSET', 'is_group' => false],
            '1.2.2' => ['code' => '1220', 'name' => 'Accumulated Depreciation', 'type' => 'ASSET', 'is_group' => false],

            '2' => ['code' => '2000', 'name' => 'Liabilities', 'type' => 'LIABILITY', 'is_group' => true],
            '2.1' => ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'LIABILITY', 'is_group' => true],
            '2.1.1' => ['code' => '2110', 'name' => 'Accounts Payable', 'type' => 'LIABILITY', 'is_group' => false],
            '2.1.2' => ['code' => '2120', 'name' => 'Tax Payable', 'type' => 'LIABILITY', 'is_group' => false],
            '2.1.3' => ['code' => '2130', 'name' => 'Salary Payable', 'type' => 'LIABILITY', 'is_group' => false],
            '2.2' => ['code' => '2200', 'name' => 'Long-term Liabilities', 'type' => 'LIABILITY', 'is_group' => true],
            '2.2.1' => ['code' => '2210', 'name' => 'Loans Payable', 'type' => 'LIABILITY', 'is_group' => false],

            '3' => ['code' => '3000', 'name' => 'Equity', 'type' => 'EQUITY', 'is_group' => true],
            '3.1' => ['code' => '3100', 'name' => 'Owners Equity', 'type' => 'EQUITY', 'is_group' => true],
            '3.1.1' => ['code' => '3110', 'name' => 'Share Capital', 'type' => 'EQUITY', 'is_group' => false],
            '3.1.2' => ['code' => '3120', 'name' => 'Retained Earnings', 'type' => 'EQUITY', 'is_group' => false],

            '4' => ['code' => '4000', 'name' => 'Revenue', 'type' => 'REVENUE', 'is_group' => true],
            '4.1' => ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'REVENUE', 'is_group' => true],
            '4.1.1' => ['code' => '4110', 'name' => 'Sales', 'type' => 'REVENUE', 'is_group' => false],
            '4.2' => ['code' => '4200', 'name' => 'Other Income', 'type' => 'REVENUE', 'is_group' => true],
            '4.2.1' => ['code' => '4210', 'name' => 'Interest Income', 'type' => 'REVENUE', 'is_group' => false],

            '5' => ['code' => '5000', 'name' => 'Expenses', 'type' => 'EXPENSE', 'is_group' => true],
            '5.1' => ['code' => '5100', 'name' => 'Operating Expenses', 'type' => 'EXPENSE', 'is_group' => true],
            '5.1.1' => ['code' => '5110', 'name' => 'Salary Expense', 'type' => 'EXPENSE', 'is_group' => false],
            '5.1.2' => ['code' => '5120', 'name' => 'Rent Expense', 'type' => 'EXPENSE', 'is_group' => false],
            '5.1.3' => ['code' => '5130', 'name' => 'Utilities Expense', 'type' => 'EXPENSE', 'is_group' => false],
            '5.1.4' => ['code' => '5140', 'name' => 'Office Supplies', 'type' => 'EXPENSE', 'is_group' => false],
            '5.1.5' => ['code' => '5150', 'name' => 'Travel Expense', 'type' => 'EXPENSE', 'is_group' => false],
            '5.2' => ['code' => '5200', 'name' => 'Cost of Sales', 'type' => 'EXPENSE', 'is_group' => true],
            '5.2.1' => ['code' => '5210', 'name' => 'Cost of Goods Sold', 'type' => 'EXPENSE', 'is_group' => false],
            '5.3' => ['code' => '5300', 'name' => 'Financial Expenses', 'type' => 'EXPENSE', 'is_group' => true],
            '5.3.1' => ['code' => '5310', 'name' => 'Interest Expense', 'type' => 'EXPENSE', 'is_group' => false],
        ];

        $accountIds = [];

        foreach ($accounts as $key => $account) {
            $parentId = null;

            if (str_contains($key, '.')) {
                $parentKey = substr($key, 0, strrpos($key, '.'));
                $parentId = $accountIds[$parentKey] ?? null;
            }

            $level = substr_count($key, '.') + 1;

            $created = \Modules\Finance\Models\Account::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'account_code' => $account['code'],
                ],
                [
                    'parent_id' => $parentId,
                    'account_name' => $account['name'],
                    'account_type' => $account['type'],
                    'normal_balance' => in_array($account['type'], ['ASSET', 'EXPENSE']) ? 'DEBIT' : 'CREDIT',
                    'level' => $level,
                    'is_group' => $account['is_group'],
                    'is_postable' => !$account['is_group'],
                    'status' => 'active',
                ]
            );

            $accountIds[$key] = $created->id;
        }
    }

    protected function initializeDocumentSequences(Company $company): void
    {
        $documentTypes = [
            ['document_type' => 'JV', 'prefix' => 'JV'],
            ['document_type' => 'PV', 'prefix' => 'PV'],
            ['document_type' => 'RV', 'prefix' => 'RV'],
            ['document_type' => 'BRV', 'prefix' => 'BRV'],
            ['document_type' => 'BPV', 'prefix' => 'BPV'],
            ['document_type' => 'SI', 'prefix' => 'SI'],
            ['document_type' => 'CI', 'prefix' => 'CI'],
            ['document_type' => 'SP', 'prefix' => 'SP'],
            ['document_type' => 'CR', 'prefix' => 'CR'],
        ];

        foreach ($documentTypes as $type) {
            \Modules\Core\Models\NumberSequence::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'document_type' => $type['document_type'],
                ],
                [
                    'prefix' => $type['prefix'],
                    'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}',
                    'last_number' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
