<?php

/**
 * Contains the InvoicePrinter class.
 *
 * @author      Farjad Tahir
 *
 * @see         http://www.splashpk.com
 *
 * @license     GPL
 *
 * @since       2017-12-15
 */

namespace Herdwatch\PdfInvoice;

/* fork from konekt/pdf-invoice bundle */

use Herdwatch\PdfInvoice\Data\InvoiceItem;
use Herdwatch\PdfInvoice\Data\TextLinkItem;

/**
 * @phpstan-param InvoiceItem[] $items
 */
class InvoicePrinter extends AbstractDocumentPrinter
{
    public string $due = '';
    public string $paymentDate = '';
    public string $totalsAlignment = self::TOTAL_ALIGNMENT_VERTICAL;

    /**
     * @var TextLinkItem[]
     */
    public array $addText = [];

    public string $footerNote = '';
    public bool $productsEnded = false;

    public function setDue(string $date): void
    {
        $this->due = $date;
    }

    public function setPaymentDate(string $paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }

    public function setTotalsAlignment(string $alignment): void
    {
        $this->totalsAlignment = $alignment;
    }

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
    ): void {
        $vatField = '';
        $priceField = '';
        $totalField = '';
        $discountField = '';
        if (null !== $vat) {
            $vatField = $this->price($vat);
            $this->vatField = true;
        }
        if (null !== $price) {
            $priceField = $this->price($price, $currency, $alignment);
            $this->priceField = true;
        }
        if (null !== $total) {
            $totalField = $this->price($total);
            $this->totalField = true;
        }
        if (null !== $discount) {
            $this->firstColumnWidth = 58;
            $discountField = $this->price($discount);
            $this->discountField = true;
        }
        $this->recalculateColumns();
        $this->items[] = new InvoiceItem(
            $item,
            $this->utilsService->br2nl($description),
            (string) $quantity,
            $priceField,
            $discountField,
            $vatField,
            $totalField
        );
    }

    public function addTitle(
        string $title,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $this->addText[] = new TextLinkItem(
            'title',
            $title,
            $toEncoding
        );
    }

    public function addParagraph(
        string $paragraph,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $this->addText[] = new TextLinkItem(
            'paragraph',
            $this->utilsService->br2nl($paragraph),
            $toEncoding
        );
    }

    public function addBoldParagraph(
        string $paragraph,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $this->addText[] = new TextLinkItem(
            'bold_paragraph',
            $this->utilsService->br2nl($paragraph),
            $toEncoding
        );
    }

    public function addBoldBlueLink(
        string $link,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $this->addText[] = new TextLinkItem(
            'link',
            $link,
            $toEncoding
        );
    }

    public function setFooterNote(string $note): void
    {
        $this->footerNote = $note;
    }

    /**
     * @throws PDFInvoiceException
     */
    public function render(string $name = '', string $destination = ''): string
    {
        $this->AddPage();
        $this->Body();
        $this->AliasNbPages();

        return $this->Output($destination, $name);
    }

    /**
     * @throws PDFInvoiceException
     */
    public function Body(): void
    {
        $defaultColumnsWidth = 0;
        if ($this->columns > 1) {
            $defaultColumnsWidth = ($this->document['w']
                - $this->margins['l']
                - $this->margins['r']
                - $this->firstColumnWidth
                - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
        }
        $widthQuantity = $defaultColumnsWidth - 15;
        $width_other = 0;
        if ($this->columns > 2) {
            $width_other = ($this->document['w']
                - $this->margins['l']
                - $this->margins['r']
                - $this->firstColumnWidth
                - $widthQuantity
                + 1.2
                - ($this->columns - 1)) / ($this->columns - 2);
        }

        $cellHeight = 8;
        $bgColor = (int) ((1 - $this->columnOpacity) * 255);
        $this->addItems($cellHeight, $bgColor, $widthQuantity, $width_other);
        $badgeX = $this->GetX();
        $badgeY = $this->GetY();

        // Add totals
        $this->addTotals($bgColor, $cellHeight, (int) $widthQuantity, (int) $width_other);
        $this->productsEnded = true;
        $this->Ln();

        // Badge
        $this->badge($badgeX, $badgeY);

        // Add information
        $this->addInformation();
    }

    /**
     * @throws PDFInvoiceException
     */
    public function Footer(): void
    {
        $bottomMargin = $this->margins['b'];
        $bottomMargin += 5 * count(explode(PHP_EOL, $this->footerNote));
        $this->SetY(-$bottomMargin);
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(50, 50, 50);
        $this->MultiCell(0, 5, $this->footerNote, 0, 'L');
        $this->Cell(
            0,
            10,
            "{$this->changeCharset($this->lang['page'])} {$this->PageNo()} {$this->lang['page_of']} {nb}",
            0,
            0,
            'R'
        );
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addTotalsHorizontal(int $bgColor): void
    {
        $this->Ln(2);
        $totalsCount = count($this->totals);
        $cellWidth = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / $totalsCount;
        // Colors, line width and bold font
        $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
        $this->SetLineWidth(.3);
        $this->SetFont($this->font, 'b', 8);
        // Header
        foreach ($this->totals as $i => $totalData) {
            $this->Cell(
                0 == $totalsCount % 2 ? (0 == $i % 2 ? $cellWidth + 5 : $cellWidth - 5) : $cellWidth,
                7,
                $this->changeCharset($totalData->getName()),
                1,
                0,
                'C',
                true
            );
        }
        $this->Ln();
        // Values
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, 'b', 8);
        $this->SetFillColor($bgColor, $bgColor, $bgColor);
        foreach ($this->totals as $y => $yValue) {
            $totalData = $yValue;
            $this->Cell(
                0 == $totalsCount % 2 ? (0 == $y % 2 ? $cellWidth + 5 : $cellWidth - 5) : $cellWidth,
                6,
                $this->changeCharset($totalData->getValue()),
                'LRB',
                0,
                'C',
                $totalData->isColored()
            );
        }
        $this->Ln();
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addTotalsVertical(int $bgColor, int $cellHeight, int $widthQuantity, int $width_other): void
    {
        foreach ($this->totals as $total) {
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
            if ($total->isColored()) {
                $this->SetTextColor(255, 255, 255);
                $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
            }
            $this->SetFont($this->font, 'b', 8);
            $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
            $this->Cell(
                $width_other - 1,
                $cellHeight,
                $this->changeCharset($total->getName()),
                0,
                0,
                'L',
                1
            );
            $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
            $this->SetFont($this->font, 'b', 8);
            $this->SetFillColor($bgColor, $bgColor, $bgColor);
            if ($total->isColored()) {
                $this->SetTextColor(255, 255, 255);
                $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
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
            $this->Ln();
            $this->Ln($this->columnSpacing);
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printHeaderItem(
        string $name,
        string $translation,
        float $positionX,
        float $lineHeight,
    ): void {
        if (empty($name)) {
            return;
        }
        $this->Cell($positionX, $lineHeight);
        $this->SetFont($this->font, 'B', 9);
        $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
        $this->Cell(
            32,
            $lineHeight,
            $this->changeCharset($translation, true) . ':',
            0,
            0,
            'L'
        );
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, $lineHeight, $name, 0, 1, 'R');
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function headerItems(): void
    {
        $lineHeight = 5;
        // Calculate the position of strings
        $this->SetFont($this->font, 'B', 9);
        $positionX = $this->document['w'] - $this->margins['l'] - $this->margins['r']
            - max(
                $this->GetStringWidth(mb_strtoupper($this->lang['number'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->lang['date'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->lang['payment'], self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->lang['due'], self::ICONV_CHARSET_INPUT))
            )
            - max(
                $this->GetStringWidth(mb_strtoupper($this->reference, self::ICONV_CHARSET_INPUT)),
                $this->GetStringWidth(mb_strtoupper($this->date, self::ICONV_CHARSET_INPUT))
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
        // Payment date
        $this->printHeaderItem(
            $this->paymentDate,
            $this->lang['payment'],
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
        // Custom Headers
        $this->printCustomHeaders($positionX, $lineHeight);
        // First page
        $this->printFirstPage($lineHeight);
        // Table header
        $this->printTableHeader();
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addTotals(int $bgColor, int $cellHeight, int $widthQuantity, int $width_other): void
    {
        if (empty($this->totals)) {
            return;
        }
        if (self::TOTAL_ALIGNMENT_HORIZONTAL === $this->totalsAlignment) {
            $this->addTotalsHorizontal($bgColor);

            return;
        }

        $this->addTotalsVertical($bgColor, $cellHeight, $widthQuantity, $width_other);
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function badge(float $badgeX, float $badgeY): void
    {
        if (empty($this->badge)) {
            return;
        }
        $tmpBadge = ' ' . mb_strtoupper($this->badge, self::ICONV_CHARSET_INPUT) . ' ';
        $resetX = $this->GetX();
        $resetY = $this->GetY();
        $this->SetXY($badgeX, $badgeY + 15);
        $this->SetLineWidth(0.4);
        $this->SetDrawColor($this->badgeColor[0], $this->badgeColor[1], $this->badgeColor[2]);
        $this->SetTextColor($this->badgeColor[0], $this->badgeColor[1], $this->badgeColor[2]);
        $this->SetFont($this->font, 'b', 15);
        $this->Rotate(10, $this->GetX(), $this->GetY());
        $this->Rect($this->GetX(), $this->GetY(), $this->GetStringWidth($tmpBadge) + 2, 10);
        $this->Write(
            10,
            $this->changeCharset($tmpBadge, true)
        );
        $this->Rotate(0);
        if ($resetY > $this->GetY() + 20) {
            $this->SetXY($resetX, $resetY);
        } else {
            $this->Ln(18);
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addInformation(): void
    {
        foreach ($this->addText as $text) {
            if ('title' === $text->getType()) {
                $this->SetFont($this->font, 'b', 9);
                $this->SetTextColor(50, 50, 50);
                $this->Cell(
                    0,
                    10,
                    $this->changeCharset($text->getText(), true, $text->getEncoding()),
                    0,
                    0,
                    'L',
                    0
                );
                $this->addHeaderEndLine();
            }
            if ('paragraph' === $text->getType()) {
                $this->SetTextColor(80, 80, 80);
                $this->SetFont($this->font, '', 8);
                $this->MultiCell(
                    0,
                    4,
                    $this->changeCharset($text->getText(), false, $text->getEncoding()),
                    0,
                    'L',
                    0
                );
                $this->Ln(2);
            }
            if ('bold_paragraph' === $text->getType()) {
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor(50, 50, 50);
                $this->MultiCell(
                    0,
                    4,
                    $this->changeCharset($text->getText(), false, $text->getEncoding()),
                    0,
                    'L',
                    0
                );
            }
            if ('link' === $text->getType()) {
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->MultiCell(
                    0,
                    4,
                    $this->changeCharset($text->getText(), false, $text->getEncoding()),
                    0,
                    'L',
                    0
                );
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addItems(float $cellHeight, int $bgColor, float $widthQuantity, float $width_other): void
    {
        /** @var InvoiceItem $item */
        foreach ($this->items as $item) {
            $cHeight = $this->printStandardItems($item, $cellHeight, $bgColor, $widthQuantity);
            if ($this->vatField) {
                $this->printCommonField(
                    $item->getVat(),
                    $cHeight,
                    $width_other
                );
            }
            if ($this->priceField) {
                $this->printCommonField(
                    $item->getPrice(),
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

    /**
     * @throws PDFInvoiceException
     */
    protected function printCustomHeaders(float $positionX, float $lineHeight): void
    {
        if (count($this->customHeaders) > 0) {
            foreach ($this->customHeaders as $customHeader) {
                $this->printHeaderItem(
                    $customHeader->getContent(),
                    $this->lang['title'],
                    $positionX,
                    $lineHeight
                );
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printTableHeader(): void
    {
        if ($this->productsEnded) {
            $this->Ln(12);

            return;
        }
        $width_other = $this->addHeaderStartTuning();
        $this->addHeaderItem($this->lang['product'], $this->firstColumnWidth);
        if ($this->vatField) {
            $this->addHeaderItem($this->lang['vat'], $width_other);
        }
        if ($this->priceField) {
            $this->addHeaderItem($this->lang['price'], $width_other + 5.0);
        }
        if ($this->discountField) {
            $this->addHeaderItem($this->lang['discount'], $width_other);
        }

        $this->addHeaderItem($this->lang['total'], $width_other);
        $this->addHeaderEndLine();
    }
}
