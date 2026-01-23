<?php

namespace Herdwatch\PdfInvoice;

use Herdwatch\PdfInvoice\Data\Color;
use Herdwatch\PdfInvoice\Data\MultiSpeciesInvoiceItem;
use Herdwatch\PdfInvoice\Data\SpecialTotalItem;

/**
 * @phpstan-param MultiSpeciesInvoiceItem[] $items
 */
class MultiSpeciesInvoicePrinter extends InvoicePrinter
{
    private const string SERVICE_PERIOD = 'Service period';

    private string $servicePeriod = '';

    /**
     * @var SpecialTotalItem[]
     */
    private array $specialTotals = [];

    public function addMultiSpecieItem(
        string $item,
        string $description,
        float $quantity,
        string $notes,
        float $total,
        ?string $extendedDescription = null,
    ): void {
        $this->columns = 5;
        $this->items[] = new MultiSpeciesInvoiceItem(
            $item,
            $this->utilsService->br2nl($description),
            $this->utilsService->br2nl((string) $extendedDescription),
            $this->utilsService->br2nl($notes),
            (string) $quantity,
            $this->price($total),
            $total < 0.0
        );
    }

    /**
     * @throws PDFInvoiceException
     */
    #[\Override]
    public function headerItems(): void
    {
        $lineHeight = 5;
        // Calculate the position of strings
        $this->SetFont($this->font, 'B', 9);
        $positionX = $this->document['w'] - $this->margins['l'] - $this->margins['r']
            - max(
                $this->GetStringWidth(mb_strtoupper($this->lang['number'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->lang['date'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->lang['payment'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->lang['due'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper(self::SERVICE_PERIOD, self::ICONV_CHARSET_INPUT))
            )
            - max(
                $this->GetStringWidth(mb_strtoupper($this->reference, self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->date, self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->servicePeriod, self::ICONV_CHARSET_INPUT))
            );

        // Number
        $this->printHeaderItem(
            $this->reference,
            $this->lang['number'],
            $positionX,
            $lineHeight
        );
        // Date
        $this->printHeaderItem(
            $this->date,
            $this->lang['date'],
            $positionX,
            $lineHeight
        );
        // Time
        $this->printHeaderItem(
            $this->time,
            $this->lang['time'],
            $positionX,
            $lineHeight
        );
        // Due date
        $this->printHeaderItem(
            $this->due,
            $this->lang['due'],
            $positionX,
            $lineHeight
        );
        // Service period
        $this->printServicePeriod($positionX, $lineHeight);
        // Custom Headers
        $this->printCustomHeaders($positionX, $lineHeight);
        // First page
        $this->printFirstPage($lineHeight);
        // Table header
        $this->printTableHeader();
    }

    public function setServicePeriod(string $servicePeriod): void
    {
        $this->servicePeriod = $servicePeriod;
    }

    public function addSpecialTotalItem(
        string $name,
        float $value,
        bool $colored = false,
        ?Color $bgColor = null,
        ?Color $textColor = null,
    ): void {
        $this->specialTotals[] = new SpecialTotalItem(
            $name,
            $this->price($value),
            $colored,
            $bgColor,
            $textColor
        );
    }

    #[\Override]
    protected function printTableHeader(): void
    {
        if ($this->productsEnded) {
            $this->Ln(12);

            return;
        }

        $width_other = $this->addHeaderStartTuning();
        $this->addHeaderItem($this->lang['product'], $this->firstColumnWidth);
        $this->addHeaderItem($this->lang['qty'], $width_other - 15);
        $this->addHeaderItem('Notes', $width_other * 2);
        $this->addHeaderItem($this->lang['total'], $width_other + 10);
        $this->addHeaderEndLine();
    }

    #[\Override]
    protected function addItems(float $cellHeight, int $bgColor, float $widthQuantity, float $width_other): void
    {
        /** @var MultiSpeciesInvoiceItem $item */
        foreach ($this->items as $item) {
            $cHeight = $this->printStandardFirstDescription($item, $cellHeight, $bgColor);
            $x = $this->GetX();
            $this->fixedHeightCell(
                $this->firstColumnWidth,
                $cHeight,
                $this->changeCharset($item->getName()),
                [$bgColor, $bgColor, $bgColor],
                0,
            );
            $cHeight = $this->printDescription($item->getDescription(), $x, $cHeight);
            $cHeight = $this->printExtendedDescription($item->getExtendedDescription(), $x, $cHeight);
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 8);
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            $this->Cell($widthQuantity, $cHeight, $item->getQuantity(), 0, 0, 'C', 1);
            $this->printNotesField($cHeight, $item, $width_other, $bgColor);
            if ($item->isNegative()) {
                $this->SetFont($this->font, 'B', 8);
                $this->SetTextColor(255, 0, 0);
            }
            $this->printCommonField(
                $item->getTotal(),
                $cHeight,
                $width_other
            );
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 8);
            $this->Ln();
            $this->Ln($this->columnSpacing);
        }
    }

    protected function addTotals(int $bgColor, int $cellHeight, int $widthQuantity, int $width_other): void
    {
        parent::addTotals($bgColor, $cellHeight, $widthQuantity, $width_other);
        $this->addSpecialTotals($bgColor, $cellHeight, $widthQuantity, $width_other);
    }

    protected function setColors(SpecialTotalItem $specialTotal): void
    {
        if (!$specialTotal->isColored()) {
            return;
        }
        $tempBgColor = $specialTotal->getBgColor() ?? $this->colorData;
        $tempTextColor = $specialTotal->getTextColor() ?? Color::createWhite();
        $this->colorService->setTextColorData($tempTextColor);
        $this->colorService->setFillColorData($tempBgColor);
    }

    /**
     * @throws PDFInvoiceException
     */
    private function printServicePeriod(int $positionX, int $lineHeight): void
    {
        if (empty($this->servicePeriod)) {
            return;
        }
        $this->Cell($positionX, $lineHeight);
        $this->SetFont($this->font, 'B', 9);
        $this->colorService->setTextColorData($this->colorData);
        $this->Cell(
            32,
            $lineHeight,
            $this->changeCharset(self::SERVICE_PERIOD, true) . ':',
            0,
            0,
            'L'
        );
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, $lineHeight, $this->servicePeriod, 0, 1, 'R');
    }

    /**
     * @throws PDFInvoiceException
     */
    private function printExtendedDescription(string $extendedDescription, float $x, float $cHeight): float
    {
        if (empty($extendedDescription)) {
            return $cHeight;
        }
        $resetX = (float) $this->GetX();
        $resetY = (float) $this->GetY();
        $this->SetTextColor(120, 120, 120);
        $this->SetXY($x, $this->GetY() + $cHeight);
        $this->SetFont($this->font, '', $this->fontSizeProductDescription);
        $this->MultiCell(
            $this->firstColumnWidth,
            floor($this->fontSizeProductDescription / 2),
            $this->changeCharset($extendedDescription),
            0,
            'L',
            1
        );
        // Calculate Height
        $newY = (float) $this->GetY();
        $cHeight = $newY - $resetY + 2;
        // Make our spacer cell the same height
        $this->SetXY($x - 1, $resetY);
        $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
        // Draw an empty cell
        $this->SetXY($x, $newY);
        $this->Cell($this->firstColumnWidth, 2, '', 0, 0, 'L', 1);
        $this->SetXY($resetX, $resetY);

        return $cHeight;
    }

    /**
     * @throws PDFInvoiceException
     */
    private function printNotesField(float $cHeight, MultiSpeciesInvoiceItem $item, float $width_other, int $bgColor): void
    {
        $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
        if (!empty($item->getNotes())) {
            $x = $this->GetX();
            $y = $this->GetY();

            $this->fixedHeightCell(
                $width_other * 2,
                $cHeight,
                $this->changeCharset($item->getNotes()),
                [$bgColor, $bgColor, $bgColor],
                0,
                'C'
            );
            $this->SetXY($x + ($width_other * 2), $y);
        } else {
            $this->Cell($width_other * 2, $cHeight, '', 0, 0, 'C', 1);
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    private function addSpecialTotals(mixed $bgColor, int $cellHeight, mixed $widthQuantity, mixed $width_other): void
    {
        foreach ($this->specialTotals as $specialTotal) {
            $this->SetTextColor(50, 50, 50);
            $this->SetFillColor($bgColor, $bgColor, $bgColor);
            $this->Cell(1 + $this->firstColumnWidth, $cellHeight, '', 0, 0, 'L', 0);
            $this->Cell($widthQuantity, $cellHeight, '', 0, 0, 'L', 0);
            $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
            for ($i = 0; $i < $this->columns - 4; ++$i) {
                $this->Cell($width_other, $cellHeight, '', 0, 0, 'L', 0);
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
            }
            $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
            $this->setColors($specialTotal);
            $this->SetFont($this->font, 'b', 8);
            $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
            $this->Cell(
                $width_other - 1,
                $cellHeight,
                $this->changeCharset($specialTotal->getName()),
                0,
                0,
                'L',
                1
            );
            $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
            $this->SetFont($this->font, 'b', 8);
            $this->SetFillColor($bgColor, $bgColor, $bgColor);
            $this->setColors($specialTotal);
            $this->Cell(
                $width_other,
                $cellHeight,
                $this->changeCharset($specialTotal->getValue()),
                0,
                0,
                'C',
                1
            );
            $this->Ln();
            $this->Ln($this->columnSpacing);
        }
    }
}
