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
        $this->addHeaderProduct();
        $this->addHeaderQTY($width_other);

        if ($this->priceField) {
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell(
                $width_other + 5,
                10,
                $this->changeCharset($this->lang['price'], true),
                0,
                0,
                'C',
                0
            );
        }

        if ($this->vatField) {
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell(
                $width_other,
                10,
                $this->changeCharset($this->lang['vat'], true),
                0,
                0,
                'C',
                0
            );
        }

        if ($this->vatPercentField) {
            $this->lang['vat_percent'] = 'VAT %';
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell(
                $width_other,
                10,
                $this->changeCharset($this->lang['vat_percent'], true),
                0,
                0,
                'C',
                0
            );
        }

        if ($this->discountField) {
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell(
                $width_other,
                10,
                $this->changeCharset($this->lang['discount'], true),
                0,
                0,
                'C',
                0
            );
        }

        if ($this->totalField) {
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell(
                $width_other + 10,
                10,
                $this->changeCharset($this->lang['total'], true),
                0,
                0,
                'C',
                0
            );
        }

        $this->addHeaderEndLine();
    }

    #[\Override]
    protected function addItems(float $cellHeight, int $bgColor, float $widthQuantity, float $width_other): void
    {
        if ($this->items) {
            /** @var CreditNoteItem $item */
            foreach ($this->items as $item) {
                if ((empty($item->getName())) || (empty($item->getDescription()))) {
                    $this->Ln($this->columnSpacing);
                }
                $this->printFirstDescription($item->getDescription());
                $cHeight = $cellHeight;
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor(50, 50, 50);
                $this->SetFillColor($bgColor, $bgColor, $bgColor);
                $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
                $x = $this->GetX();
                $this->Cell(
                    $this->firstColumnWidth,
                    $cHeight,
                    $this->changeCharset($item->getName()),
                    0,
                    0,
                    'L',
                    1
                );
                $cHeight = $this->printDescription($item->getDescription(), $x, $cHeight);
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 8);
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($widthQuantity, $cHeight, $item->getQuantity(), 0, 0, 'C', 1);
                $this->printPriceField($cHeight, $item, $width_other);
                $this->printVatField($cHeight, $item, $width_other);
                $this->printVatPercentField($cHeight, $item, $width_other);
                $this->printDiscountField($cHeight, $item, $width_other);
                $this->printTotalField($cHeight, $item, $width_other);
                $this->Ln();
                $this->Ln($this->columnSpacing);
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    private function printVatPercentField(float $cHeight, CreditNoteItem $item, float $width_other): void
    {
        if ($this->vatPercentField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (!empty($item->getVatPercent())) {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    $this->changeCharset($item->getVatPercent()),
                    0,
                    0,
                    'C',
                    1
                );
            } else {
                $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
            }
        }
    }
}
