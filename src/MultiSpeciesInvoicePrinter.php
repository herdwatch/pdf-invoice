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
            $cHeight = $this->printDescription($item->getExtendedDescription(), $x, $cHeight, $cHeight);
            $this->colorService->setTextColorData(Color::createGrey());
            $this->SetFont($this->font, '', 8);
            $this->addSpacingCell($cHeight);
            $this->Cell($widthQuantity, $cHeight, $item->getQuantity(), 0, 0, 'C', 1);
            $this->printNotesField($cHeight, $item, $width_other, $bgColor);
            if ($item->isNegative()) {
                $this->setStandardFont();
                $this->SetTextColor(255, 0, 0);
            }
            $this->printCommonField(
                $item->getTotal(),
                $cHeight,
                $width_other
            );
            $this->colorService->setTextColorData(Color::createGrey());
            $this->SetFont($this->font, '', 8);
            $this->addSpacing();
        }
    }

    protected function addTotals(int $bgColor, int $cellHeight, int $widthQuantity, int $width_other): void
    {
        $this->addTotalsVertical($bgColor, $cellHeight, $widthQuantity, $width_other);
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
    protected function addTotalsVertical(int $bgColor, float $cellHeight, float $widthQuantity, float $width_other): void
    {
        foreach ($this->totals as $total) {
            $this->initTotals($bgColor, $cellHeight, $widthQuantity, $width_other, 5);
            if ($total->isColored()) {
                $this->colorService->setTextColorData(Color::createWhite());
                $this->colorService->setFillColorData($this->colorData);
            }
            $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
            $this->Cell(
                $width_other * 2 - 1,
                $cellHeight,
                $this->changeCharset($total->getName()),
                0,
                0,
                'L',
                1
            );
            $this->addSpacingCell($cellHeight);
            $this->setStandardFont();
            $this->colorService->setFillColorData(Color::createGrey($bgColor));
            if ($total->isColored()) {
                $this->colorService->setTextColorData(Color::createWhite());
                $this->colorService->setFillColorData($this->colorData);
            }
            if ($total->isNegativeRed()) {
                $this->setStandardFont();
                $this->SetTextColor(255, 0, 0);
            }
            $this->Cell(
                $width_other,
                $cellHeight,
                $this->changeCharset($total->getValue()),
                0,
                0,
                'C',
                1
            );
            $this->addSpacing();
        }
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
        $this->colorService->setTextColorData(Color::createGrey());
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, $lineHeight, $this->servicePeriod, 0, 1, 'R');
    }

    /**
     * @throws PDFInvoiceException
     */
    private function printNotesField(float $cHeight, MultiSpeciesInvoiceItem $item, float $width_other, int $bgColor): void
    {
        $this->addSpacingCell($cHeight);
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
    private function addSpecialTotals(int $bgColor, float $cellHeight, float $widthQuantity, float $width_other): void
    {
        foreach ($this->specialTotals as $specialTotal) {
            $this->initTotals($bgColor, $cellHeight, $widthQuantity, $width_other, 5);
            $this->setColors($specialTotal);
            $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
            $this->Cell(
                $width_other * 2 - 1,
                $cellHeight,
                $this->changeCharset($specialTotal->getName()),
                0,
                0,
                'L',
                1
            );
            $this->addSpacingCell($cellHeight);
            $this->setStandardFont();
            $this->colorService->setTextColorData(Color::createGrey($bgColor));
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
            $this->addSpacing();
        }
    }
}
