<?php

namespace Modules\Sales\Services\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\TaxCalculator;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;

/**
 * Lines of quotations and sales orders: net is quantity × price less the line discount, tax comes from the
 * line's tax code. The customer invoice recalculates tax (with tax rules) when it is raised.
 */
trait PricesSalesLines
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function replacePricedLines(Model $document, array $lines, string $currency, CarbonInterface $date): void
    {
        $document->lines()->delete();
        $subtotal = Money::zero($currency);
        $tax = Money::zero($currency);

        foreach ($lines as $line) {
            $product = Product::findOrFail($line['product_id']);
            $net = Money::of((string) $line['quantity'], $currency)->multipliedBy((string) $line['unit_price'])->minus(Money::of($line['discount_amount'] ?? 0, $currency));

            if ($net->isNegative()) {
                throw new InvalidAccountingTransactionException(__('A line discount cannot be more than the line amount.'));
            }

            $taxCode = ! empty($line['tax_id']) ? Tax::find($line['tax_id']) : null;
            $lineTax = $taxCode ? app(TaxCalculator::class)->calculate($taxCode, $net, $date)->totalTax() : Money::zero($currency);

            $document->lines()->create([
                'product_id' => $product->id,
                'description' => ($line['description'] ?? '') !== '' ? $line['description'] : $product->name,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_amount' => $line['discount_amount'] ?? 0,
                'tax_id' => $taxCode?->id,
                'subtotal' => $net->amount,
                'tax_amount' => $lineTax->amount,
                'total_amount' => $net->plus($lineTax)->amount,
            ]);

            $subtotal = $subtotal->plus($net);
            $tax = $tax->plus($lineTax);
        }

        $document->update(['subtotal' => $subtotal->amount, 'tax_amount' => $tax->amount, 'total_amount' => $subtotal->plus($tax)->amount]);
    }
}
