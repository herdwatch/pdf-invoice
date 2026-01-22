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

use Herdwatch\PdfInvoice\Data\AbstractInvoiceItem;
use Herdwatch\PdfInvoice\Data\InvoiceItem;

/**
 * @phpstan-param InvoiceItem[] $items
 */
class InvoicePrinter extends AbstractDocumentPrinter
{
    public string $due = '';
    public string $paymentDate = '';
    public string $totalsAlignment = self::TOTAL_ALIGNMENT_VERTICAL;

    /**
     * @var array<int, string[]>
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
        $this->addText[] = ['title', $title, $toEncoding];
    }

    public function addParagraph(
        string $paragraph,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $paragraph = $this->utilsService->br2nl($paragraph);
        $this->addText[] = ['paragraph', $paragraph, $toEncoding];
    }

    public function addBoldParagraph(
        string $paragraph,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $paragraph = $this->utilsService->br2nl($paragraph);
        $this->addText[] = ['bold_paragraph', $paragraph, $toEncoding];
    }

    public function addBoldBlueLink(
        string $link,
        string $toEncoding = self::ICONV_CHARSET_OUTPUT_A,
    ): void {
        $this->addText[] = ['link', $link, $toEncoding];
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
    public function printPaymentDate(int $positionX, int $lineHeight): void
    {
        if (!empty($this->paymentDate)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(
                32,
                $lineHeight,
                $this->changeCharset($this->lang['payment'], true) . ':',
                0,
                0,
                'L'
            );
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 9);
            $this->Cell(0, $lineHeight, $this->paymentDate, 0, 1, 'R');
        }
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
        $this->printReference($positionX, $lineHeight);
        // Date
        $this->printDate($positionX, $lineHeight);
        // Time
        $this->printTime($positionX, $lineHeight);
        // Payment date
        $this->printPaymentDate($positionX, $lineHeight);
        // Due date
        $this->printDueDate($positionX, $lineHeight);
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
            for ($y = 0; $y < $totalsCount; ++$y) {
                $totalData = $this->totals[$i];
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
        } else {
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
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function badge(int $badgeX, int $badgeY): void
    {
        if ($this->badge) {
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
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addInformation(): void
    {
        foreach ($this->addText as $text) {
            if ('title' === $text[0]) {
                $this->SetFont($this->font, 'b', 9);
                $this->SetTextColor(50, 50, 50);
                $this->Cell(
                    0,
                    10,
                    $this->changeCharset($text[1], true),
                    0,
                    0,
                    'L',
                    0
                );
                $this->Ln();
                $this->SetLineWidth(0.3);
                $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Line(
                    $this->margins['l'],
                    $this->GetY(),
                    $this->document['w'] - $this->margins['r'],
                    $this->GetY()
                );
                $this->Ln(2);
            }
            if ('paragraph' === $text[0]) {
                $this->SetTextColor(80, 80, 80);
                $this->SetFont($this->font, '', 8);
                $this->MultiCell(
                    0,
                    4,
                    $this->changeCharset($text[1], false, $text[2]),
                    0,
                    'L',
                    0
                );
                $this->Ln(2);
            }
            if ('bold_paragraph' === $text[0]) {
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor(50, 50, 50);
                $this->MultiCell(
                    0,
                    4,
                    $this->changeCharset($text[1], false, $text[2]),
                    0,
                    'L',
                    0
                );
            }
            if ('link' === $text[0]) {
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->MultiCell(
                    0,
                    4,
                    $this->changeCharset($text[1], false, $text[2]),
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
            $this->printVatField($cHeight, $item, $width_other);
            $this->printPriceField($cHeight, $item, $width_other);
            $this->printDiscountField($cHeight, $item, $width_other);
            $this->printTotalField($cHeight, $item, $width_other);
            $this->Ln();
            $this->Ln($this->columnSpacing);
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printReference(float $positionX, float $lineHeight): void
    {
        if (!empty($this->reference)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(
                32,
                $lineHeight,
                $this->changeCharset($this->lang['number'], true),
                0,
                0,
                'L'
            );
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 9);
            $this->Cell(0, $lineHeight, $this->reference, 0, 1, 'R');
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printDate(float $positionX, float $lineHeight): void
    {
        $this->Cell($positionX, $lineHeight);
        $this->SetFont($this->font, 'B', 9);
        $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
        $this->Cell(
            32,
            $lineHeight,
            $this->changeCharset($this->lang['date'], true) . ':',
            0,
            0,
            'L'
        );
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, $lineHeight, $this->date, 0, 1, 'R');
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printTime(float $positionX, float $lineHeight): void
    {
        if (!empty($this->time)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(
                32,
                $lineHeight,
                $this->changeCharset($this->lang['time'], true) . ':',
                0,
                0,
                'L'
            );
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 9);
            $this->Cell(0, $lineHeight, $this->time, 0, 1, 'R');
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printCustomHeaders(float $positionX, float $lineHeight): void
    {
        if (count($this->customHeaders) > 0) {
            foreach ($this->customHeaders as $customHeader) {
                $this->Cell($positionX, $lineHeight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(
                    32,
                    $lineHeight,
                    $this->changeCharset($this->lang['title'], true) . ':',
                    0,
                    0,
                    'L'
                );
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineHeight, $customHeader['content'], 0, 1, 'R');
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
        $this->addHeaderProduct();
        $this->addHeaderQTY($width_other);
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

        $this->addHeaderTotal($width_other);
        $this->Ln();
        $this->SetLineWidth(0.3);
        $this->SetDrawColor(
            $this->color[0],
            $this->color[1],
            $this->color[2]
        );
        $this->Line(
            $this->margins['l'],
            $this->GetY(),
            $this->document['w'] - $this->margins['r'],
            $this->GetY()
        );
        $this->Ln(2);
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printVatField(float $cHeight, InvoiceItem $item, float $width_other): void
    {
        if ($this->vatField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (!empty($item->getVat())) {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    $this->changeCharset($item->getVat()),
                    0,
                    0,
                    'C',
                    1
                );
            } else {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    '',
                    0,
                    0,
                    'C',
                    1
                );
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printPriceField(float $cHeight, InvoiceItem $item, float $width_other): void
    {
        if ($this->priceField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (!empty($item->getPrice())) {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    $this->changeCharset($item->getPrice()),
                    0,
                    0,
                    'C',
                    1
                );
            } else {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    '',
                    0,
                    0,
                    'C',
                    1
                );
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printDiscountField(float $cHeight, InvoiceItem $item, float $width_other): void
    {
        if ($this->discountField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (!empty($item->getDiscount())) {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    $this->changeCharset($item->getDiscount()),
                    0,
                    0,
                    'C',
                    1
                );
            } else {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    '',
                    0,
                    0,
                    'C',
                    1
                );
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printTotalField(float $cHeight, AbstractInvoiceItem $item, float $width_other): void
    {
        if ($this->totalField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (!empty($item->getTotal())) {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    $this->changeCharset($item->getTotal()),
                    0,
                    0,
                    'C',
                    1
                );
            } else {
                $this->Cell(
                    $width_other,
                    $cHeight,
                    '',
                    0,
                    0,
                    'C',
                    1
                );
            }
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printFirstDescription(?string $description): void
    {
        if (empty($description)) {
            return;
        }
        // Precalculate height
        $calculateHeight = new self();
        $calculateHeight->AddPage();
        $calculateHeight->SetXY(0, 0);
        $calculateHeight->SetFont($this->font, '', 7);
        $calculateHeight->MultiCell(
            $this->firstColumnWidth,
            3,
            $this->changeCharset($description),
            0,
            'L',
            1
        );
        $pageHeight = $this->document['h'] - $this->GetY() - $this->margins['t'] - $this->margins['t'];
        if ($pageHeight < 35) {
            $this->AddPage();
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printDueDate(int $positionX, int $lineHeight): void
    {
        if (empty($this->due)) {
            return;
        }
        $this->Cell($positionX, $lineHeight);
        $this->SetFont($this->font, 'B', 9);
        $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
        $this->Cell(
            32,
            $lineHeight,
            $this->changeCharset($this->lang['due'], true) . ':',
            0,
            0,
            'L'
        );
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, $lineHeight, $this->due, 0, 1, 'R');
    }
}
