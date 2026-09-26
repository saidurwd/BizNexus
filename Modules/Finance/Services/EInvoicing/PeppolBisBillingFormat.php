<?php

namespace Modules\Finance\Services\EInvoicing;

use DOMDocument;
use DOMElement;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\EInvoiceFormat;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\DocumentTaxService;
use Modules\Finance\Support\TaxCalculation;

/**
 * UBL 2.1 invoice following Peppol BIS Billing 3.0 (EN 16931). VAT categories: S (standard), Z (zero rated),
 * AE (reverse charge) and E (exempt, for untaxed lines).
 */
class PeppolBisBillingFormat implements EInvoiceFormat
{
    private const NS_INVOICE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    private const NS_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private const NS_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    private DOMDocument $document;

    public function __construct(protected DocumentTaxService $documentTax) {}

    public function key(): string
    {
        return 'peppol-bis-3';
    }

    public function mimeType(): string
    {
        return 'application/xml';
    }

    public function fileExtension(): string
    {
        return 'xml';
    }

    public function render(CustomerInvoice $invoice): string
    {
        $invoice->loadMissing(['company.baseCurrency', 'customer', 'currency']);
        $currency = $invoice->currency?->code ?? $invoice->company->baseCurrency->code;
        $calculations = $this->documentTax->calculations($invoice);

        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;
        $root = $this->document->createElementNS(self::NS_INVOICE, 'Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::NS_CAC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::NS_CBC);
        $this->document->appendChild($root);

        $this->cbc($root, 'CustomizationID', 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0');
        $this->cbc($root, 'ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
        $this->cbc($root, 'ID', $invoice->invoice_number);
        $this->cbc($root, 'IssueDate', $invoice->invoice_date->toDateString());
        if ($invoice->due_date) {
            $this->cbc($root, 'DueDate', $invoice->due_date->toDateString());
        }
        $this->cbc($root, 'InvoiceTypeCode', '380');
        $this->cbc($root, 'DocumentCurrencyCode', $currency);

        $this->party($root, 'AccountingSupplierParty', $invoice->company->name, $invoice->company->legal_name, $invoice->company->address, $invoice->company->country_code, $invoice->company->tax_number, $invoice->company->email, $invoice->company->registration_number);
        $customer = $invoice->customer;
        $this->party($root, 'AccountingCustomerParty', $customer->name, $customer->name, $customer->address, $customer->country_code, $customer->tax_number, $customer->email, null);

        $lines = $invoice->lines;
        $categories = [];
        $lineExtension = Money::zero($currency);
        $totalTax = Money::zero($currency);

        foreach ($lines as $line) {
            [$category, $percent] = $this->category($line->is_reverse_charge, $calculations[$line->id]);
            $tax = $line->is_reverse_charge ? Money::zero($currency) : $calculations[$line->id]->totalTax();
            $key = $category.'|'.$percent;
            $categories[$key] ??= ['category' => $category, 'percent' => $percent, 'taxable' => Money::zero($currency), 'tax' => Money::zero($currency)];
            $categories[$key]['taxable'] = $categories[$key]['taxable']->plus($calculations[$line->id]->net);
            $categories[$key]['tax'] = $categories[$key]['tax']->plus($tax);
            $lineExtension = $lineExtension->plus($calculations[$line->id]->net);
            $totalTax = $totalTax->plus($tax);
        }

        $taxTotal = $this->cac($root, 'TaxTotal');
        $this->amount($taxTotal, 'TaxAmount', $totalTax);
        foreach ($categories as $subtotal) {
            $element = $this->cac($taxTotal, 'TaxSubtotal');
            $this->amount($element, 'TaxableAmount', $subtotal['taxable']);
            $this->amount($element, 'TaxAmount', $subtotal['tax']);
            $this->taxCategory($element, 'TaxCategory', $subtotal['category'], $subtotal['percent']);
        }

        $totals = $this->cac($root, 'LegalMonetaryTotal');
        $this->amount($totals, 'LineExtensionAmount', $lineExtension);
        $this->amount($totals, 'TaxExclusiveAmount', $lineExtension);
        $this->amount($totals, 'TaxInclusiveAmount', $lineExtension->plus($totalTax));
        $this->amount($totals, 'PayableAmount', $lineExtension->plus($totalTax));

        foreach ($lines->values() as $index => $line) {
            $calculation = $calculations[$line->id];
            [$category, $percent] = $this->category($line->is_reverse_charge, $calculation);
            $element = $this->cac($root, 'InvoiceLine');
            $this->cbc($element, 'ID', (string) ($index + 1));
            $this->cbc($element, 'InvoicedQuantity', rtrim(rtrim((string) $line->quantity, '0'), '.'))->setAttribute('unitCode', 'C62');
            $this->amount($element, 'LineExtensionAmount', $calculation->net);
            $item = $this->cac($element, 'Item');
            $this->cbc($item, 'Name', $line->description);
            $this->taxCategory($item, 'ClassifiedTaxCategory', $category, $percent);
            $price = $this->cac($element, 'Price');
            $this->cbc($price, 'PriceAmount', bcdiv($calculation->net->amount, (string) ($line->quantity ?: 1), 4))->setAttribute('currencyID', $currency);
        }

        return $this->document->saveXML();
    }

    /**
     * @return array{0: string, 1: string} EN 16931 VAT category code and percent
     */
    protected function category(bool $reverseCharge, TaxCalculation $calculation): array
    {
        if ($reverseCharge) {
            return ['AE', '0'];
        }

        if ($calculation->components === []) {
            return ['E', '0'];
        }

        if ($calculation->net->isZero() || $calculation->totalTax()->isZero()) {
            return ['Z', '0'];
        }

        return ['S', bcround(bcmul(bcdiv($calculation->totalTax()->amount, $calculation->net->amount, 8), '100', 8), 2)];
    }

    protected function party(DOMElement $root, string $role, string $name, ?string $legalName, ?string $address, ?string $country, ?string $taxNumber, ?string $email, ?string $registration): void
    {
        $party = $this->cac($this->cac($root, $role), 'Party');

        if ($email) {
            $this->cbc($party, 'EndpointID', $email)->setAttribute('schemeID', 'EM');
        }

        $this->cbc($this->cac($party, 'PartyName'), 'Name', $name);

        $postalAddress = $this->cac($party, 'PostalAddress');
        if ($address) {
            $this->cbc($postalAddress, 'StreetName', $address);
        }
        $this->cbc($this->cac($postalAddress, 'Country'), 'IdentificationCode', $country ?? 'XX');

        if ($taxNumber) {
            $taxScheme = $this->cac($party, 'PartyTaxScheme');
            $this->cbc($taxScheme, 'CompanyID', $taxNumber);
            $this->cbc($this->cac($taxScheme, 'TaxScheme'), 'ID', 'VAT');
        }

        $legalEntity = $this->cac($party, 'PartyLegalEntity');
        $this->cbc($legalEntity, 'RegistrationName', $legalName ?: $name);
        if ($registration) {
            $this->cbc($legalEntity, 'CompanyID', $registration);
        }
    }

    protected function taxCategory(DOMElement $parent, string $elementName, string $category, string $percent): void
    {
        $element = $this->cac($parent, $elementName);
        $this->cbc($element, 'ID', $category);
        $this->cbc($element, 'Percent', $percent);
        if ($category === 'AE') {
            $this->cbc($element, 'TaxExemptionReasonCode', 'VATEX-EU-AE');
            $this->cbc($element, 'TaxExemptionReason', 'Reverse charge');
        } elseif ($category === 'E') {
            $this->cbc($element, 'TaxExemptionReason', 'Exempt');
        }
        $this->cbc($this->cac($element, 'TaxScheme'), 'ID', 'VAT');
    }

    protected function amount(DOMElement $parent, string $name, Money $money): void
    {
        $this->cbc($parent, $name, $money->amount)->setAttribute('currencyID', $money->currency);
    }

    protected function cac(DOMElement $parent, string $name): DOMElement
    {
        return $parent->appendChild($this->document->createElementNS(self::NS_CAC, 'cac:'.$name));
    }

    protected function cbc(DOMElement $parent, string $name, string $value): DOMElement
    {
        $element = $this->document->createElementNS(self::NS_CBC, 'cbc:'.$name);
        $element->appendChild($this->document->createTextNode($value));

        return $parent->appendChild($element);
    }
}
