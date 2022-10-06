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

use DateTimeZone;
use Exception;
use FPDF;

/* fork from konekt/pdf-invoice bundle */

class InvoicePrinter extends FPDF
{
    public const ICONV_CHARSET_INPUT    = 'UTF-8';
    public const ICONV_CHARSET_OUTPUT_A = 'ISO-8859-1//TRANSLIT';
    public const ICONV_CHARSET_OUTPUT_B = 'windows-1252//TRANSLIT';
    public const        ALIGNMENT_RIGHT = 'right';
    public const        ALIGNMENT_LEFT = 'left';
    public const SIZE_A4               = 'A4';
    public const SIZE_LETTER           = 'letter';
    public const SIZE_LEGAL            = 'legal';
    public const TOTAL_ALIGNMENT_VERTICAL = 'vertical';
    public const TOTAL_ALIGNMENT_HORIZONTAL = 'horizontal';

  public int $angle = 0;
    public string $font = 'helvetica';                 /* Font Name : See inc/fpdf/font for all supported fonts */
    public float $columnOpacity = 0.06;               /* Items table background color opacity. Range (0.00 - 1) */
    public float $columnSpacing = 0.3;                /* Spacing between Item Tables */
    public array $referenceFormat = ['.', ',', 'left', false, false];    /* Currency formater */
    public array $margins = [
        'l' => 15,
        't' => 15,
        'r' => 15,
        'b' => 15
    ]; /* l: Left Side , t: Top Side , r: Right Side */
    public int $fontSizeProductDescription = 7;                /* font size of product description */

    public array $lang = [];
    public array $document = [];
    public string $reference = '';
    public string $logo = '';
    public array $color = [];
    public array $badgeColor = [];
    public string $date = '';
    public string $time = '';
    public string $due = '';
    public string $paymentDate = '';
    public array $from = [''];
    public array $to = [''];
    public array $items = [];
    public array $totals = [];
    public string $totalsAlignment = self::TOTAL_ALIGNMENT_VERTICAL;
    public string $badge = '';
    public array $addText = [];
    public string $footerNote = '';
    public array $dimensions = [61.0, 34.0];
    public bool $displayToFrom = true;
    public array $customHeaders = [];

    protected string $title = '';
    protected bool $displayToFromHeaders = true;
    public int $fromToBoldLineNumber = 0;
    protected int $columns = 2;
    protected array $maxImageDimensions = [230, 130];
    protected bool $flipFlop = false;
    protected bool $vatField = false;
    protected bool $priceField = false;
    protected bool $totalField = false;
    protected bool $discountField = false;
    protected int $firstColumnWidth = 92;
    protected string $currency = '';
    protected string $language = '';

    public function __construct(string $size = self::SIZE_A4, string $currency = '$', string $language = 'en')
    {
        $this->currency = $currency;
        $this->setLanguage($language);
        $this->setDocumentSize($size);
        $this->setColor('#222222');

        $this->recalculateColumns();

        parent::__construct('P', 'mm', [$this->document['w'], $this->document['h']]);

        $this->AliasNbPages();
        $this->SetMargins($this->margins['l'], $this->margins['t'], $this->margins['r']);
    }

    private function setLanguage(string $language): void
    {
      $this->language = $language;
      include dirname(__DIR__) . '/inc/languages/' . $language . '.inc';
      $this->lang = $lang;
    }

    private function setDocumentSize(string $dSize): void
    {
        switch ($dSize) {
            case self::SIZE_LETTER:
                $this->document['w'] = 215.9;
                $this->document['h'] = 279.4;
                break;
            case self::SIZE_LEGAL:
                $this->document['w'] = 215.9;
                $this->document['h'] = 355.6;
                break;
            case self::SIZE_A4:
            default:
                $this->document['w'] = 210;
                $this->document['h'] = 297;
                break;
        }
    }

    private function resizeToFit(?string $image): array
    {
        [$width, $height] = getimagesize($image);
        $newWidth = $this->maxImageDimensions[0] / $width;
        $newHeight = $this->maxImageDimensions[1] / $height;
        $scale = min($newWidth, $newHeight);

        return [
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height)),
        ];
    }

    private function pixelsToMM(float $val): float
    {
        $mm_inch = 25.4;
        $dpi = 96;

        return ($val * $mm_inch) / $dpi;
    }

    private function hex2rgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return [$r, $g, $b];
    }

    private function br2nl(string $string): string
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    public function changeLanguageTerm(string $term, string $new): void
    {
        $this->lang[$term] = $new;
    }

    public function isValidTimezoneId(string $zone): bool
    {
        try {
            $d = new DateTimeZone($zone);
        } catch (Exception $e) {
            $d = null;
        }

        return $d !== null;
    }

    public function setTimeZone(string $zone = ''): void
    {
        if (!empty($zone) && $this->isValidTimezoneId($zone) === true) {
            date_default_timezone_set($zone);
        }
    }

    public function setType(string $title): void
    {
        $this->title = $title;
    }

    public function setColor(string $rgbcolor): void
    {
        $this->color = $this->hex2rgb($rgbcolor);
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function setTime(string $time): void
    {
        $this->time = $time;
    }

    public function setDue(string $date): void
    {
        $this->due = $date;
    }

    public function setPaymentDate(string $paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }

    public function setLogo(string $logo = '', int $maxWidth = 0, int $maxHeight = 0): void
    {
        if ($maxWidth && $maxHeight) {
            $this->maxImageDimensions = [$maxWidth, $maxHeight];
        }
        $this->logo = $logo;
        $this->dimensions = $this->resizeToFit($logo);
    }

    public function hideToFrom(): void
    {
        $this->displayToFrom = false;
    }

    public function hideToFromHeaders(): void
    {
        $this->displayToFromHeaders = false;
    }

    public function setFrom(array $data): void
    {
        $this->from = $data;
    }

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


    public function setTotalsAlignment(string $alignment): void
    {
        $this->totalsAlignment = $alignment;
    }

    public function setNumberFormat(
        string $decimals = '.',
        string $thousands_sep = ',',
        string $alignment = 'left',
        bool $space = true,
        bool $negativeParenthesis = false): void
    {
        $this->referenceFormat = [
            $decimals,
            $thousands_sep,
            $alignment,
            $space,
            $negativeParenthesis,
        ];
    }

    public function setFontSizeProductDescription(int $data): void
    {
        $this->fontSizeProductDescription = $data;
    }

    public function flipFlop(): void
    {
        $this->flipFlop = true;
    }

    public function price(float $price, ?string $currency = null, ?string $alignment = null): string
    {
        if ($currency === null) {
            $currency = $this->currency;
        }
        if ($alignment === null) {
            $alignment = isset($this->referenceFormat[2]) ? strtolower($this->referenceFormat[2]) : 'left';
        }
        [$decimalPoint, $thousandSeparator] = $this->referenceFormat;
        $spaceBetweenCurrencyAndAmount = !isset($this->referenceFormat[3]) || (bool)$this->referenceFormat[3];
        $space = $spaceBetweenCurrencyAndAmount ? ' ' : '';
        $negativeParenthesis = isset($this->referenceFormat[4]) && (bool)$this->referenceFormat[4];

        $number = number_format($price, 2, $decimalPoint, $thousandSeparator);
        $negative = $negativeParenthesis && $price < 0;
        if ($negative) {
            $number = substr($number, 1);
        }
        if (self::ALIGNMENT_RIGHT === $alignment) {
            $str = $number . $space . $currency;
        } else {
            $str = $currency . $space . $number;
        }
        if ($negative) {
            $str = '(' . $str . ')';
        }
        return $str;
    }

    public function addCustomHeader(?string $title, ?string $content): void
    {
        $this->customHeaders[] = [
            'title' => $title,
            'content' => $content,
        ];
    }

    public function addItem(string $item, ?string $description, float $quantity, ?float $vat, ?float $price, ?float $discount, ?float $total, ?string $currency = null, ?string $alignment = null): void
    {
        $p['item'] = $item;
        $p['description'] = $this->br2nl($description);
        $p['quantity'] = $quantity;

        if ($vat !== null) {
            $p['vat'] = $vat;
            if (is_numeric($vat)) {
                $p['vat'] = $this->price($vat);
            }
            $this->vatField = true;
            $this->recalculateColumns();
        }
        if ($price !== null) {
            $p['price'] = $price;
            if (is_numeric($price)) {
                $p['price'] = $this->price($price, $currency, $alignment);
            }
            $this->priceField = true;
            $this->recalculateColumns();
        }
        if ($total !== null) {
            $p['total'] = $total;
            if (is_numeric($total)) {
                $p['total'] = $this->price($total);
            }
            $this->totalField = true;
            $this->recalculateColumns();
        }
        if ($discount !== null) {
            $this->firstColumnWidth = 58;
            $p['discount'] = $discount;
            if (is_numeric($discount)) {
                $p['discount'] = $this->price($discount);
            }
            $this->discountField = true;
            $this->recalculateColumns();
        }
        $this->items[] = $p;
    }

    public function addTotal(string $name, $value, bool $colored = false): void
    {
        $t['name'] = $name;
        $t['value'] = $value;
        if (is_numeric($value)) {
            $t['value'] = $this->price($value);
        }
        $t['colored'] = $colored;
        $this->totals[] = $t;
    }

    public function addTitle(string $title): void
    {
        $this->addText[] = ['title', $title];
    }

    public function addParagraph(string $paragraph): void
    {
        $paragraph = $this->br2nl($paragraph);
        $this->addText[] = ['paragraph', $paragraph];
    }

    public function addBadge(string $badge, bool $color = false): void
    {
        $this->badge = $badge;

        if ($color) {
            $this->badgeColor = $this->hex2rgb($color);
        } else {
            $this->badgeColor = $this->color;
        }
    }

    public function setFooterNote(string $note): void
    {
        $this->footerNote = $note;
    }

    public function render(string $name = '', string $destination = ''): string
    {
        $this->AddPage();
        $this->Body();
        $this->AliasNbPages();

        return $this->Output($destination, $name);
    }

    public function Header(): void
    {
        if (isset($this->logo) && !empty($this->logo)) {
            $this->Image(
                $this->logo,
                $this->margins['l'],
                $this->margins['t'],
                $this->dimensions[0],
                $this->dimensions[1]
            );
        }

        //Title
        $this->printTitle();

        $lineHeight = 5;
        //Calculate position of strings
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

        //Number
        $this->printReference($positionX, $lineHeight);
        //Date
        $this->printDate($positionX, $lineHeight);
        //Time
        $this->printTime($positionX, $lineHeight);
        //Payment date
        $this->printPaymentDate($positionX, $lineHeight);
        //Due date
        $this->printDueDate($positionX, $lineHeight);
        //Custom Headers
        $this->printCustomHeaders($positionX, $lineHeight);
        //First page
        $this->printFirstPage($lineHeight);
        //Table header
        $this->printTableHeader();
    }

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
        $bgColor = (1 - $this->columnOpacity) * 255;
        $this->addItems($cellHeight, $bgColor, $widthQuantity, $width_other);
        $badgeX = $this->GetX();
        $badgeY = $this->GetY();

        //Add totals
        $this->addTotals($bgColor, $cellHeight, $widthQuantity, $width_other);
        $this->productsEnded = true;
        $this->Ln();
        $this->Ln(3);

        //Badge
        $this->badge($badgeX, $badgeY);

        //Add information
        $this->addInformation();
    }

    public function Footer(): void
    {
        $bottomMargin = $this->margins['b'];
        $bottomMargin += 5*count(explode(PHP_EOL, $this->footerNote));
        $this->SetY(-$bottomMargin);
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(50, 50, 50);
        $this->MultiCell(0, 5, $this->footerNote, 0, 'L');
        $this->Cell(
            0,
            10,
            iconv('UTF-8', 'ISO-8859-1', $this->lang['page']) . ' ' . $this->PageNo() . ' ' . $this->lang['page_of'] . ' {nb}',
            0,
            0,
            'R'
        );
    }

    public function Rotate(int $angle, int $x = -1, int $y = -1): void
    {
        if ($x === -1) {
            $x = $this->x;
        }
        if ($y === -1) {
            $y = $this->y;
        }
        if ($this->angle !== 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle !== 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c,
                $s,
                -$s,
                $c,
                $cx,
                $cy,
                -$cx,
                -$cy
            ));
        }
    }

    public function _endpage(): void
    {
        if ($this->angle !== 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    private function recalculateColumns(): void
    {
        $this->columns = 2;

        if ($this->vatField) {
            ++$this->columns;
        }

        if ($this->priceField) {
            ++$this->columns;
        }

        if ($this->totalField) {
            ++$this->columns;
        }

        if ($this->discountField) {
            ++$this->columns;
        }
    }

    /**
     * @param     $bgColor
     * @param int $cellHeight
     * @param     $width_other
     */
    protected function addTotals($bgColor, int $cellHeight, $widthQuantity, $width_other): void
    {
        if ($this->totals) {
            if ($this->totalsAlignment === self::TOTAL_ALIGNMENT_HORIZONTAL) {
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
                for ($i=0;$i<$totalsCount;$i++) {
                    $this->Cell(
                        $totalsCount % 2 == 0 ? ($i % 2 == 0 ? $cellWidth + 5 : $cellWidth - 5) : $cellWidth,
                        7,
                        iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $this->totals[$i]['name']),
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
                for ($y=0;$y<$totalsCount;$y++) {
                    $this->Cell(
                        $totalsCount % 2 == 0 ? ($y % 2 == 0 ? $cellWidth + 5 : $cellWidth - 5) : $cellWidth,
                        6,
                        iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $this->totals[$y]['value']),
                        'LRB',
                        0,
                        'C',
                        $this->totals[$y]['colored']
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
                  for ($i = 0; $i < $this->columns - 4; $i++) {
                    $this->Cell($width_other, $cellHeight, '', 0, 0, 'L', 0);
                    $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                  }
                  $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                  if ($total['colored']) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
                  }
                  $this->SetFont($this->font, 'b', 8);
                  $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
                  $this->Cell(
                    $width_other - 1,
                    $cellHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $total['name']),
                    0,
                    0,
                    'L',
                    1
                );
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                $this->SetFont($this->font, 'b', 8);
                $this->SetFillColor($bgColor, $bgColor, $bgColor);
                if ($total['colored']) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
                  }
                  $this->Cell($width_other, $cellHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $total['value']), 0, 0, 'C', 1);
                  $this->Ln();
                  $this->Ln($this->columnSpacing);
                }
            }
        }
    }

    /**
     * @param $badgeX
     * @param $badgeY
     */
    protected function badge($badgeX, $badgeY): void
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
            $this->Write(10, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B,
                mb_strtoupper($tmpBadge, self::ICONV_CHARSET_INPUT)));
            $this->Rotate(0);
            if ($resetY > $this->GetY() + 20) {
                $this->SetXY($resetX, $resetY);
            } else {
                $this->Ln(18);
            }
        }
    }

    protected function addInformation(): void
    {
        foreach ($this->addText as $text) {
            if ($text[0] === 'title') {
                $this->SetFont($this->font, 'b', 9);
                $this->SetTextColor(50, 50, 50);
                $this->Cell(0, 10, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                    mb_strtoupper($text[1], self::ICONV_CHARSET_INPUT)), 0, 0, 'L', 0);
                $this->Ln();
                $this->SetLineWidth(0.3);
                $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Line(
                    $this->margins['l'],
                    $this->GetY(),
                    $this->document['w'] - $this->margins['r'],
                    $this->GetY()
                );
                $this->Ln(4);
            }
            if ($text[0] === 'paragraph') {
                $this->SetTextColor(80, 80, 80);
                $this->SetFont($this->font, '', 8);
                $this->MultiCell(0, 4, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A, $text[1]), 0, 'L',
                    0);
                $this->Ln(4);
            }
        }
    }

    /**
     * @param int $cellHeight
     * @param     $bgColor
     * @param     $width_other
     */
    protected function addItems(int $cellHeight, $bgColor, $widthQuantity, $width_other): void
    {
        if ($this->items) {
            foreach ($this->items as $item) {
                if ((empty($item['item'])) || (empty($item['description']))) {
                    $this->Ln($this->columnSpacing);
                }
                $this->printFirstDescription($item['description']);
                $cHeight = $cellHeight;
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor(50, 50, 50);
                $this->SetFillColor($bgColor, $bgColor, $bgColor);
                $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
                $x = $this->GetX();
                $this->Cell(
                    $this->firstColumnWidth,
                    $cHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A, $item['item']),
                    0,
                    0,
                    'L',
                    1
                );
                $cHeight = $this->printDescription($item['description'], $x, $cHeight);
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 8);
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($widthQuantity, $cHeight, $item['quantity'], 0, 0, 'C', 1);
                $this->printVatField($cHeight, $item, $width_other);
                $this->printPriceField($cHeight, $item, $width_other);
                $this->printDiscountField($cHeight, $item, $width_other);
                $this->printTotalField($cHeight, $item, $width_other);
                $this->Ln();
                $this->Ln($this->columnSpacing);
            }
        }
    }

    /**
     * @param int $positionX
     * @param int $lineHeight
     */
    protected function printReference(int $positionX, int $lineHeight): void
    {
        if (!empty($this->reference)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(
                32,
                $lineHeight,
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                    mb_strtoupper($this->lang['number'], self::ICONV_CHARSET_INPUT) . ':'),
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
     * @param int $positionX
     * @param int $lineHeight
     */
    protected function printDate(int $positionX, int $lineHeight): void
    {
        $this->Cell($positionX, $lineHeight);
        $this->SetFont($this->font, 'B', 9);
        $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
        $this->Cell(32, $lineHeight, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                mb_strtoupper($this->lang['date'], self::ICONV_CHARSET_INPUT)) . ':', 0, 0, 'L');
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, '', 9);
        $this->Cell(0, $lineHeight, $this->date, 0, 1, 'R');
    }

    /**
     * @param int $positionX
     * @param int $lineHeight
     */
    protected function printTime(int $positionX, int $lineHeight): void
    {
        if (!empty($this->time)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(
                32,
                $lineHeight,
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                    mb_strtoupper($this->lang['time'], self::ICONV_CHARSET_INPUT)) . ':',
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
     * @param int $positionX
     * @param int $lineHeight
     */
    protected function printDueDate(int $positionX, int $lineHeight): void
    {
        if (!empty($this->due)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(32, $lineHeight, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                    mb_strtoupper($this->lang['due'], self::ICONV_CHARSET_INPUT)) . ':', 0, 0, 'L');
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 9);
            $this->Cell(0, $lineHeight, $this->due, 0, 1, 'R');
        }
    }

    public function printPaymentDate(int $positionX, int $lineHeight): void
    {
        if (!empty($this->paymentDate)) {
            $this->Cell($positionX, $lineHeight);
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(32, $lineHeight, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                    mb_strtoupper($this->lang['payment'], self::ICONV_CHARSET_INPUT)) . ':', 0, 0,
                'L');
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 9);
            $this->Cell(0, $lineHeight, $this->paymentDate, 0, 1, 'R');
        }
    }

    /**
     * @param int $positionX
     * @param int $lineHeight
     */
    protected function printCustomHeaders(int $positionX, int $lineHeight): void
    {
        if (count($this->customHeaders) > 0) {
            foreach ($this->customHeaders as $customHeader) {
                $this->Cell($positionX, $lineHeight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(32, $lineHeight, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                        mb_strtoupper($customHeader['title'], self::ICONV_CHARSET_INPUT)) . ':', 0, 0, 'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineHeight, $customHeader['content'], 0, 1, 'R');
            }
        }
    }

    protected function printTableHeader(): void
    {
        if (!isset($this->productsEnded)) {
            $width_other = ($this->document['w']
                            - $this->margins['l']
                            - $this->margins['r']
                            - $this->firstColumnWidth
                            - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
            $this->SetTextColor(50, 50, 50);
            $this->Ln(12);
            $this->SetFont($this->font, 'B', 9);
            $this->Cell(1, 10, '', 0, 0, 'L', 0);
            $this->Cell(
                $this->firstColumnWidth,
                10,
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                    mb_strtoupper($this->lang['product'], self::ICONV_CHARSET_INPUT)),
                0,
                0,
                'L',
                0
            );
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other - 15, 10, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                mb_strtoupper($this->lang['qty'], self::ICONV_CHARSET_INPUT)), 0, 0, 'C', 0);
            if ($this->vatField) {
                $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
                $this->Cell(
                    $width_other,
                    10,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                        mb_strtoupper($this->lang['vat'], self::ICONV_CHARSET_INPUT)),
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
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                        mb_strtoupper($this->lang['price'], self::ICONV_CHARSET_INPUT)),
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
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                        mb_strtoupper($this->lang['discount'], self::ICONV_CHARSET_INPUT)),
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
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                        mb_strtoupper($this->lang['total'], self::ICONV_CHARSET_INPUT)),
                    0,
                    0,
                    'C',
                    0
                );
            }
            $this->Ln();
            $this->SetLineWidth(0.3);
            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'], $this->GetY());
            $this->Ln(2);
        } else {
            $this->Ln(12);
        }
    }

    /**
     * @param int $lineHeight
     */
    protected function printFirstPage(int $lineHeight): void
    {
        if ($this->PageNo() === 1) {
            $tmpDimensions = $this->dimensions[1] ?? 0;
            if (($this->margins['t'] + $tmpDimensions) > $this->GetY()) {
                $this->SetY($this->margins['t'] + $tmpDimensions + 5);
            } else {
                $this->SetY($this->GetY() + 10);
            }
            $this->Ln(5);
            $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);

            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetFont($this->font, 'B', 10);
            $width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;

            $this->doFlipFlop();
            $this->doDisplayToFrom($width, $lineHeight);
        }
    }

    protected function printTitle(): void
    {
        $this->SetTextColor(0, 0, 0);
        $this->SetFont($this->font, 'B', 20);
        if (isset($this->title) && !empty($this->title)) {
            $this->Cell(0, 5, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                mb_strtoupper($this->title, self::ICONV_CHARSET_INPUT)), 0, 1, 'R');
        }
        $this->SetFont($this->font, '', 9);
        $this->Ln(5);
    }

    /**
     * @param int   $cHeight
     * @param array $item
     * @param       $width_other
     *
     * @return mixed
     */
    protected function printVatField(int $cHeight, array $item, $width_other): void
    {
        if ($this->vatField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (isset($item['vat'])) {
                $this->Cell($width_other, $cHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $item['vat']), 0, 0, 'C', 1);
            } else {
                $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
            }
        }
    }

    /**
     * @param int   $cHeight
     * @param array $item
     * @param       $width_other
     *
     * @return mixed
     */
    protected function printPriceField(int $cHeight, array $item, $width_other): void
    {
        if ($this->priceField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (isset($item['price'])) {
                $this->Cell($width_other, $cHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $item['price']), 0, 0, 'C',
                    1);
            } else {
                $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
            }
        }
    }

    /**
     * @param int   $cHeight
     * @param array $item
     * @param       $width_other
     *
     * @return mixed
     */
    protected function printDiscountField(int $cHeight, array $item, $width_other): void
    {
        if ($this->discountField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (isset($item['discount'])) {
                $this->Cell($width_other, $cHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $item['discount']), 0, 0,
                    'C', 1);
            } else {
                $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
            }
        }
    }

    /**
     * @param int   $cHeight
     * @param array $item
     * @param       $width_other
     *
     * @return mixed
     */
    protected function printTotalField(int $cHeight, array $item, $width_other): void
    {
        if ($this->totalField) {
            $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
            if (isset($item['total'])) {
                $this->Cell($width_other, $cHeight,
                    iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_B, $item['total']), 0, 0, 'C',
                    1);
            } else {
                $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
            }
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
     * @param     $width
     * @param int $lineHeight
     */
    protected function doDisplayToFrom($width, int $lineHeight): void
    {
        if ($this->displayToFrom) {
            $this->doDisplayToFromHeaders($width, $lineHeight);

            //Information
            $this->SetFont($this->font, '', 8);
            $this->SetTextColor(100, 100, 100);
            $countFrom = $this->from === null ? 0 : count($this->from);
            $countTo = $this->to === null ? 0 : count($this->to);
            $iMax = max($countFrom, $countTo);
            for ($i = 0; $i < $iMax; $i++) {
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
     * @param $description
     */
    protected function printFirstDescription($description): void
    {
        if ($description) {
            //Precalculate height
            $calculateHeight = new self();
            $calculateHeight->AddPage();
            $calculateHeight->SetXY(0, 0);
            $calculateHeight->SetFont($this->font, '', 7);
            $calculateHeight->MultiCell(
                $this->firstColumnWidth,
                3,
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A, $description),
                0,
                'L',
                1
            );
            $pageHeight = $this->document['h'] - $this->GetY() - $this->margins['t'] - $this->margins['t'];
            if ($pageHeight < 35) {
                $this->AddPage();
            }
        }
    }

    /**
     * @param     $description
     * @param     $x
     * @param int $cHeight
     *
     * @return int
     */
    protected function printDescription($description, $x, int $cHeight): int
    {
        if ($description) {
            $resetX = $this->GetX();
            $resetY = $this->GetY();
            $this->SetTextColor(120, 120, 120);
            $this->SetXY($x, $this->GetY() + 8);
            $this->SetFont($this->font, '', $this->fontSizeProductDescription);
            $this->MultiCell(
                $this->firstColumnWidth,
                floor($this->fontSizeProductDescription / 2),
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A, $description),
                0,
                'L',
                1
            );
            //Calculate Height
            $newY = $this->GetY();
            $cHeight = (int)($newY - $resetY + 2);
            //Make our spacer cell the same height
            $this->SetXY($x - 1, $resetY);
            $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
            //Draw empty cell
            $this->SetXY($x, $newY);
            $this->Cell($this->firstColumnWidth, 2, '', 0, 0, 'L', 1);
            $this->SetXY($resetX, $resetY);
        }

        return $cHeight;
}

    /**
     * @param     $width
     * @param int $lineHeight
     */
    protected function doDisplayToFromHeaders($width, int $lineHeight): void
    {
        if ($this->displayToFromHeaders) {
            $this->Cell($width, $lineHeight, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                mb_strtoupper($this->lang['from'], self::ICONV_CHARSET_INPUT)), 0, 0, 'L');
            $this->Cell(0, $lineHeight, iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A,
                mb_strtoupper($this->lang['to'], self::ICONV_CHARSET_INPUT)), 0, 0, 'L');
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

    /**
     * @param bool $isEmptyFrom
     * @param bool $isEmptyTo
     * @param int  $i
     * @param      $width
     * @param int  $lineHeight
     */
    protected function printLineDisplayToFrom(bool $isEmptyFrom, bool $isEmptyTo, int $i, $width, int $lineHeight): void
    {
        if (!$isEmptyFrom || !$isEmptyTo) {
            $tmpFrom = $isEmptyFrom ? '' : $this->from[$i];
            $tmpTo = $isEmptyTo ? '' : $this->to[$i];
            $this->Cell($width,
                $lineHeight,
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A, $tmpFrom),
                0,
                0,
                'L');
            $this->Cell(0,
                $lineHeight,
                iconv(self::ICONV_CHARSET_INPUT, self::ICONV_CHARSET_OUTPUT_A, $tmpTo),
                0,
                0,
                'L');
        }
    }
}
