<?php

namespace Modules\Finance\Enums;

/**
 * Roles an account plays in automatic postings, mapped per company (account determination).
 */
enum AccountPurpose: string
{
    case Payable = 'payable';
    case Receivable = 'receivable';
    case Cash = 'cash';
    case Bank = 'bank';
    case FxRounding = 'fx_rounding';
    case RealizedFxGain = 'realized_fx_gain';
    case RealizedFxLoss = 'realized_fx_loss';
    case UnrealizedFxGain = 'unrealized_fx_gain';
    case UnrealizedFxLoss = 'unrealized_fx_loss';
    case RetainedEarnings = 'retained_earnings';
    case IntercompanyReceivable = 'intercompany_receivable';
    case IntercompanyPayable = 'intercompany_payable';
    case TranslationReserve = 'translation_reserve';
    case Inventory = 'inventory';
    case CostOfGoodsSold = 'cost_of_goods_sold';
    case GoodsReceivedNotInvoiced = 'goods_received_not_invoiced';
    case PurchasePriceVariance = 'purchase_price_variance';
    case InventoryAdjustment = 'inventory_adjustment';
    case GoodsDeliveredNotInvoiced = 'goods_delivered_not_invoiced';

    public function label(): string
    {
        return match ($this) {
            self::Payable => 'Accounts payable (default)',
            self::Receivable => 'Accounts receivable (default)',
            self::Cash => 'Cash (default)',
            self::Bank => 'Bank (default)',
            self::FxRounding => 'Currency rounding differences',
            self::RealizedFxGain => 'Realised exchange gain',
            self::RealizedFxLoss => 'Realised exchange loss',
            self::UnrealizedFxGain => 'Unrealised exchange gain (revaluation)',
            self::UnrealizedFxLoss => 'Unrealised exchange loss (revaluation)',
            self::RetainedEarnings => 'Retained earnings (year-end close)',
            self::IntercompanyReceivable => 'Intercompany receivable (due from)',
            self::IntercompanyPayable => 'Intercompany payable (due to)',
            self::TranslationReserve => 'Foreign currency translation reserve (CTA)',
            self::Inventory => 'Inventory (when the product and its category have none)',
            self::CostOfGoodsSold => 'Cost of goods sold (when the product and its category have none)',
            self::GoodsReceivedNotInvoiced => 'Goods received not invoiced (GRNI)',
            self::PurchasePriceVariance => 'Purchase price variance',
            self::InventoryAdjustment => 'Inventory adjustments and count differences',
            self::GoodsDeliveredNotInvoiced => 'Goods delivered not invoiced (cost awaiting the invoice)',
        };
    }

    /**
     * Account code pattern used when no mapping exists (legacy config/finance/accounts.php).
     */
    public function legacyPatternKey(): ?string
    {
        return match ($this) {
            self::Payable => 'finance.accounts.payable.pattern',
            self::Receivable => 'finance.accounts.receivable.pattern',
            self::Cash => 'finance.accounts.cash.pattern',
            self::Bank => 'finance.accounts.bank.pattern',
            default => null,
        };
    }
}
