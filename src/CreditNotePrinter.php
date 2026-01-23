<?php

namespace Herdwatch\PdfInvoice;

use Herdwatch\PdfInvoice\Data\CreditNoteItem;

class CreditNotePrinter extends InvoicePrinter
{
    protected int $firstColumnWidth = 76;
    private bool $vatPercentField = true;

    #[\Override]
    public function addItem(
        string $item,
        string $description,
        float $quantity,
        ?float $vat,
        ?float $price,
        ?float $discount,
        ?float $total,
        ?string $currency = null,
        ?string $alignment = null,
        ?float $vatPercent = 0.0,
    ): void {
        $vatField = '';
        $priceField = '';
        $totalField = '';
        $discountField = '';
        $vatPercentField = '';
        if (null !== $vat) {
            $vatField = $this->price($vat);
            $this->vatField = true;
            $this->recalculateColumns();
        }
        if (null !== $price) {
            $priceField = $this->price($price, $currency, $alignment);
            $this->priceField = true;
            $this->recalculateColumns();
        }
        if (null !== $total) {
            $totalField = $this->price($total);
            $this->totalField = true;
            $this->recalculateColumns();
        }
        if (null !== $discount) {
            $this->firstColumnWidth = 58;
            $discountField = $this->price($discount);
            $this->discountField = true;
            $this->recalculateColumns();
        }
        $this->SetFont($this->font, 'b', 8);
        if (null !== $vatPercent) {
            $vatPercentField = $this->price($vatPercent, '% ');
        }
        ++$this->columns;
        $this->items[] = new CreditNoteItem(
            $item,
            $this->utilsService->br2nl($description),
            (string) $quantity,
            $priceField,
            $discountField,
            $vatField,
            $totalField,
            $vatPercentField
        );
    }

    #[\Override]
    protected function printTableHeader(): void
    {
        if (!$this->productsEnded) {
            $this->Ln(12);

            return;
        }
        $width_other = $this->addHeaderStartTuning();
        $this->addHeaderItem($this->lang['product'], $this->firstColumnWidth);
        $this->addHeaderItem($this->lang['qty'], $width_other - 15.0);

        if ($this->priceField) {
            $this->addHeaderItem($this->lang['price'], $width_other + 5.0);
        }

        if ($this->vatField) {
            $this->addHeaderItem($this->lang['vat'], $width_other);
        }

        if ($this->vatPercentField) {
            $this->addHeaderItem('VAT %', $width_other);
        }

        if ($this->discountField) {
            $this->addHeaderItem($this->lang['discount'], $width_other);
        }

        if ($this->totalField) {
            $this->addHeaderItem($this->lang['total'], $width_other);
        }

        $this->addHeaderEndLine();
    }

    #[\Override]
    protected function addItems(float $cellHeight, int $bgColor, float $widthQuantity, float $width_other): void
    {
        /** @var CreditNoteItem $item */
        foreach ($this->items as $item) {
            $cHeight = $this->printStandardItems($item, $cellHeight, $bgColor, $widthQuantity);
            if ($this->priceField) {
                $this->printCommonField(
                    $item->getPrice(),
                    $cHeight,
                    $width_other
                );
            }
            if ($this->vatField) {
                $this->printCommonField(
                    $item->getVat(),
                    $cHeight,
                    $width_other
                );
            }
            if ($this->vatPercentField) {
                $this->printCommonField(
                    $item->getVatPercent(),
                    $cHeight,
                    $width_other
                );
            }
            if ($this->discountField) {
                $this->printCommonField(
                    $item->getDiscount(),
                    $cHeight,
                    $width_other
                );
            }
            if ($this->totalField) {
                $this->printCommonField(
                    $item->getTotal(),
                    $cHeight,
                    $width_other
                );
            }
            $this->Ln();
            $this->Ln($this->columnSpacing);
        }
    }
}
