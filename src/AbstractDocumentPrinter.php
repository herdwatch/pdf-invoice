<?php

namespace Herdwatch\PdfInvoice;

use Herdwatch\PdfInvoice\Data\AbstractInvoiceItem;
use Herdwatch\PdfInvoice\Data\CustomHeaderItem;
use Herdwatch\PdfInvoice\Data\InvoiceItem;
use Herdwatch\PdfInvoice\Data\TotalItem;

abstract class AbstractDocumentPrinter extends ExtendedFPDF
{
    public string $reference = '';
    public string $date = '';
    public string $time = '';

    /**
     * @var string[]
     */
    public array $from = [''];

    /**
     * @var string[]
     */
    public array $to = [''];

    /**
     * @var AbstractInvoiceItem[]
     */
    public array $items = [];

    /**
     * @var TotalItem[]
     */
    public array $totals = [];

    /**
     * @var CustomHeaderItem[]
     */
    public array $customHeaders = [];

    public bool $displayToFrom = true;
    public int $fromToBoldLineNumber = 0;

    /**
     * @var int[]
     */
    public array $badgeColor = [];

    public string $badge = '';
    protected int $columns = 2;
    protected bool $vatField = false;
    protected bool $priceField = false;
    protected bool $totalField = false;
    protected bool $discountField = false;
    protected string $title = '';
    protected bool $displayToFromHeaders = true;
    protected bool $flipFlop = false;
    protected int $firstColumnWidth = 92;

    /**
     * @var int[]
     */
    protected array $maxImageDimensions = [230, 130];

    protected string $logo = '';

    /**
     * @var float[]
     */
    protected array $dimensions = [61.0, 34.0];

    public function setVatField(bool $vatField): void
    {
        $this->vatField = $vatField;
    }

    public function addBadge(string $badge, ?string $color = null): void
    {
        $this->badge = $badge;

        if (!empty($color)) {
            $this->badgeColor = $this->utilsService->hex2rgb($color);
        } else {
            $this->badgeColor = $this->color;
        }
    }

    public function setLogo(string $logo = '', int $maxWidth = 0, int $maxHeight = 0): void
    {
        if ($maxWidth && $maxHeight) {
            $this->maxImageDimensions = [$maxWidth, $maxHeight];
        }
        $this->logo = $logo;
        $this->dimensions = $this->resizeToFit($logo);
    }

    public function setType(string $title): void
    {
        $this->title = $title;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function setTime(string $time): void
    {
        $this->time = $time;
    }

    public function hideToFrom(): void
    {
        $this->displayToFrom = false;
    }

    public function hideToFromHeaders(): void
    {
        $this->displayToFromHeaders = false;
    }

    /**
     * @param string[] $data
     */
    public function setFrom(array $data): void
    {
        $this->from = $data;
    }

    /**
     * @param string[] $data
     */
    public function setTo(array $data): void
    {
        $this->to = $data;
    }

    public function setFromToBoldLineNumber(int $fromToBoldLineNumber): void
    {
        $this->fromToBoldLineNumber = $fromToBoldLineNumber;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function flipFlop(): void
    {
        $this->flipFlop = true;
    }

    public function addCustomHeader(string $title, string $content): void
    {
        $this->customHeaders[] = new CustomHeaderItem($title, $content);
    }

    public function addTotal(string $name, float $value, bool $colored = false): void
    {
        $this->totals[] = new TotalItem(
            $name,
            $this->price($value),
            $colored
        );
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * @throws PDFInvoiceException
     */
    #[\Override]
    public function Header(): void
    {
        if (!empty($this->logo)) {
            $this->Image(
                $this->logo,
                $this->margins['l'],
                $this->margins['t'],
                $this->dimensions[0],
                $this->dimensions[1]
            );
        }
        // Title
        $this->printTitle();
        $this->headerItems();
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function addHeaderItem(
        string $name,
        float $width_other,
    ): void {
        $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
        $this->Cell(
            $width_other,
            10,
            $this->changeCharset($name, true),
            0,
            0,
            'C',
            0
        );
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
        $calculateHeight = new static();
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
    protected function printStandardFirstDescription(AbstractInvoiceItem|InvoiceItem $item, float $cellHeight, int $bgColor): float
    {
        if ((empty($item->getName())) || (empty($item->getDescription()))) {
            $this->Ln($this->columnSpacing);
        }
        $this->printFirstDescription($item->getDescription());
        $cHeight = $cellHeight;
        $this->SetFont($this->font, 'b', 8);
        $this->SetTextColor(50, 50, 50);
        $this->SetFillColor($bgColor, $bgColor, $bgColor);
        $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);

        return $cHeight;
    }

    protected function recalculateColumns(): void
    {
        $this->columns = 2;

        if ($this->vatField) {
            ++$this->columns;
        }

        if ($this->priceField) {
            ++$this->columns;
        }

        if ($this->discountField) {
            ++$this->columns;
        }

        if ($this->totalField) {
            ++$this->columns;
        }
    }

    protected function printFirstPage(int $lineHeight): void
    {
        if (1 === $this->PageNo()) {
            $tmpDimensions = $this->dimensions[1] ?? 0;
            if (($this->margins['t'] + $tmpDimensions) > $this->GetY()) {
                $this->SetY($this->margins['t'] + $tmpDimensions + 5);
            } else {
                $this->SetY($this->GetY());
            }
            $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);

            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetFont($this->font, 'B', 10);
            $width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;

            $this->doFlipFlop();
            $this->doDisplayToFrom($width, $lineHeight);
        }
    }

    protected function doFlipFlop(): void
    {
        if ($this->flipFlop) {
            $tmpTo = $this->lang['to'];
            $tmpFrom = $this->lang['from'];
            $this->lang['to'] = $tmpFrom;
            $this->lang['from'] = $tmpTo;
            $tmpTo = $this->to;
            $tmpFrom = $this->from;
            $this->to = $tmpFrom;
            $this->from = $tmpTo;
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function doDisplayToFrom(mixed $width, int $lineHeight): void
    {
        if ($this->displayToFrom) {
            $this->doDisplayToFromHeaders($width, $lineHeight);

            // Information
            $this->SetFont($this->font, '', 8);
            $this->SetTextColor(100, 100, 100);
            $countFrom = count($this->from);
            $countTo = count($this->to);
            $iMax = max($countFrom, $countTo);
            for ($i = 0; $i < $iMax; ++$i) {
                if ($i === $this->fromToBoldLineNumber) {
                    $this->SetTextColor(50, 50, 50);
                    $this->SetFont($this->font, 'B', 10);
                }
                // avoid undefined error if TO and FROM array lengths are different
                $isEmptyFrom = empty($this->from[$i]);
                $isEmptyTo = empty($this->to[$i]);
                $this->printLineDisplayToFrom($isEmptyFrom, $isEmptyTo, $i, $width, $lineHeight);
                if ($i === $this->fromToBoldLineNumber) {
                    $this->SetFont($this->font, '', 8);
                    $this->SetTextColor(100, 100, 100);
                    $this->Ln(7);
                } else {
                    $this->Ln(5);
                }
            }
            $this->Ln(-6);
            $this->Ln(5);

            return;
        }
        $this->Ln(-10);
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printLineDisplayToFrom(bool $isEmptyFrom, bool $isEmptyTo, int $i, mixed $width, int $lineHeight): void
    {
        if (!$isEmptyFrom || !$isEmptyTo) {
            $tmpFrom = $isEmptyFrom ? '' : $this->from[$i];
            $tmpTo = $isEmptyTo ? '' : $this->to[$i];
            $this->Cell(
                $width,
                $lineHeight,
                $this->changeCharset($tmpFrom),
                0,
                0,
                'L'
            );
            $this->Cell(
                0,
                $lineHeight,
                $this->changeCharset($tmpTo),
                0,
                0,
                'L'
            );
        }
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function doDisplayToFromHeaders(mixed $width, int $lineHeight): void
    {
        if ($this->displayToFromHeaders) {
            $this->Cell($width, $lineHeight, $this->changeCharset(mb_strtoupper($this->lang['from'], self::ICONV_CHARSET_INPUT)), 0, 0, 'L');
            $this->Cell(0, $lineHeight, $this->changeCharset(mb_strtoupper($this->lang['to'], self::ICONV_CHARSET_INPUT)), 0, 0, 'L');
            $this->Ln(7);
            $this->SetLineWidth(0.4);
            $this->Line($this->margins['l'], $this->GetY(), $this->margins['l'] + $width - 10, $this->GetY());
            $this->Line(
                $this->margins['l'] + $width,
                $this->GetY(),
                $this->margins['l'] + $width + $width,
                $this->GetY()
            );

            return;
        }
        $this->Ln(2);
    }

    protected function addHeaderStartTuning(): float
    {
        $width_other = ($this->document['w']
                - $this->margins['l']
                - $this->margins['r']
                - $this->firstColumnWidth
                - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
        $this->SetTextColor(50, 50, 50);
        $this->Ln(12);
        $this->SetFont($this->font, 'B', 9);
        $this->Cell(1, 10, '', 0, 0, 'L', 0);

        return (float) $width_other;
    }

    protected function addHeaderEndLine(): void
    {
        $this->Ln();
        $this->SetLineWidth(0.3);
        $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
        $this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'], $this->GetY());
        $this->Ln(2);
    }

    abstract protected function headerItems(): void;

    /**
     * @throws PDFInvoiceException
     */
    protected function printTitle(): void
    {
        $this->SetTextColor(0, 0, 0);
        $this->SetFont($this->font, 'B', 20);
        if (!empty($this->title)) {
            $this->Cell(
                0,
                5,
                $this->changeCharset($this->title, true),
                0,
                1,
                'R'
            );
        }
        $this->SetFont($this->font, '', 9);
        $this->Ln(5);
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printStandardItems(AbstractInvoiceItem|InvoiceItem $item, float $cellHeight, int $bgColor, float $widthQuantity): float
    {
        $cHeight = $this->printStandardFirstDescription($item, $cellHeight, $bgColor);
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

        return $cHeight;
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function printDescription(?string $description, int $x, float $cHeight): float
    {
        if (empty($description)) {
            return $cHeight;
        }

        $resetX = (float) $this->GetX();
        $resetY = (float) $this->GetY();
        $this->SetTextColor(120, 120, 120);
        $this->SetXY($x, $this->GetY() + 8);
        $this->SetFont($this->font, '', $this->fontSizeProductDescription);
        $this->MultiCell(
            $this->firstColumnWidth,
            floor($this->fontSizeProductDescription / 2),
            $this->changeCharset($description),
            0,
            'L',
            1
        );
        // Calculate Height
        $newY = $this->GetY();
        $cHeight = ($newY - $resetY + 2);
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
    protected function printCommonField(
        string $fieldValue,
        float $cHeight,
        float $width_other,
    ): void {
        $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
        if (!empty($fieldValue)) {
            $this->Cell(
                $width_other,
                $cHeight,
                $this->changeCharset($fieldValue),
                0,
                0,
                'C',
                1
            );
        } else {
            $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
        }
    }

    /**
     * @return float[]
     */
    private function resizeToFit(string $image): array
    {
        $result = getimagesize($image);
        if (false === $result) {
            return [0.0, 0.0];
        }
        [$width, $height] = $result;
        $newWidth = $this->maxImageDimensions[0] / $width;
        $newHeight = $this->maxImageDimensions[1] / $height;
        $scale = min($newWidth, $newHeight);

        return [
            round($this->utilsService->pixelsToMM($scale * $width)),
            round($this->utilsService->pixelsToMM($scale * $height)),
        ];
    }
}
