<?php

namespace Herdwatch\PdfInvoice;

use FPDF;
use Herdwatch\PdfInvoice\Data\Color;
use Herdwatch\PdfInvoice\Utils\InvoiceNbLines;
use Herdwatch\PdfInvoice\Utils\LangLoader;
use Herdwatch\PdfInvoice\Utils\TimezoneService;
use Herdwatch\PdfInvoice\Utils\UtilsService;

class ExtendedFPDF extends \FPDF
{
    public const string ICONV_CHARSET_INPUT = 'UTF-8';
    public const string ICONV_CHARSET_OUTPUT_A = 'ISO-8859-1//TRANSLIT//IGNORE';
    public const string ICONV_CHARSET_OUTPUT_B = 'windows-1252//TRANSLIT//IGNORE';
    public const string ALIGNMENT_RIGHT = 'right';
    public const string ALIGNMENT_LEFT = 'left';
    public const string SIZE_A4 = 'A4';
    public const string SIZE_LETTER = 'letter';
    public const string SIZE_LEGAL = 'legal';
    public const string TOTAL_ALIGNMENT_VERTICAL = 'vertical';
    public const string TOTAL_ALIGNMENT_HORIZONTAL = 'horizontal';

    /**
     * @var array<string, float|int>
     */
    public array $document = [];

    /**
     * @var int[]
     */
    public array $color = [];

    public int $angle = 0;
    public string $font = 'helvetica';                 /* Font Name : See inc/fpdf/font for all supported fonts */
    public float $columnOpacity = 0.06;               /* Items table background color opacity. Range (0.00 - 1) */
    public float $columnSpacing = 0.3;                /* Spacing between Item Tables */

    /**
     * @var array<int, string|bool>
     */
    public array $referenceFormat = ['.', ',', 'left', false, false];    /* Currency formater */

    /**
     * @var array<string, int>
     */
    public array $margins = [
        'l' => 15,
        't' => 15,
        'r' => 15,
        'b' => 15,
    ];

    /* l: Left Side , t: Top Side , r: Right Side */
    public float $fontSizeProductDescription = 7.0;                /* font size of product description */

    /**
     * @var array<string, string>
     */
    public array $lang = [];

    protected TimezoneService $timezoneService;
    protected UtilsService $utilsService;

    final public function __construct(
        string $size = self::SIZE_A4,
        protected string $currency = '$',
        protected string $language = 'en',
    ) {
        $this->timezoneService = new TimezoneService();
        $this->utilsService = new UtilsService();
        $this->setLanguage($language);
        $this->setDocumentSize($size);
        $this->setColor('#222222');

        $this->recalculateColumns();

        parent::__construct('P', 'mm', [$this->document['w'], $this->document['h']]);

        $this->AliasNbPages();
        $this->SetMargins($this->margins['l'], $this->margins['t'], $this->margins['r']);
    }

    public function setColor(string $rgbColor): void
    {
        $this->color = $this->utilsService->hex2rgb($rgbColor);
    }

    public function setTimeZone(string $zone = ''): void
    {
        $this->timezoneService->setTimeZone($zone);
    }

    public function setNumberFormat(
        string $decimals = '.',
        string $thousands_sep = ',',
        string $alignment = 'left',
        bool $space = true,
        bool $negativeParenthesis = false,
    ): void {
        $this->referenceFormat = [
            $decimals,
            $thousands_sep,
            $alignment,
            $space,
            $negativeParenthesis,
        ];
    }

    public function price(float $price, ?string $currency = null, ?string $alignment = null): string
    {
        if (null === $currency) {
            $currency = $this->currency;
        }
        if (null === $alignment) {
            $alignment = isset($this->referenceFormat[2]) ? strtolower((string) $this->referenceFormat[2]) : 'left';
        }
        [$decimalPoint, $thousandSeparator] = $this->referenceFormat;
        $spaceBetweenCurrencyAndAmount = !isset($this->referenceFormat[3]) || (bool) $this->referenceFormat[3];
        $space = $spaceBetweenCurrencyAndAmount ? ' ' : '';
        $negativeParenthesis = isset($this->referenceFormat[4]) && (bool) $this->referenceFormat[4];

        if (is_bool($decimalPoint)) {
            $decimalPoint = '.';
        }

        if (is_bool($thousandSeparator)) {
            $thousandSeparator = '';
        }
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

    public function Rotate(int $angle, int $x = -1, int $y = -1): void
    {
        if (-1 === $x) {
            $x = $this->x;
        }
        if (-1 === $y) {
            $y = $this->y;
        }
        if (0 !== $this->angle) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if (0 !== $angle) {
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
        if (0 !== $this->angle) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    public function setFontSizeProductDescription(int $data): void
    {
        $this->fontSizeProductDescription = $data;
    }

    public function setTextColorData(Color $color): void
    {
        $this->SetTextColor($color->getR(), $color->getG(), $color->getB());
    }

    public function setFillColorData(Color $color): void
    {
        $this->SetFillColor($color->getR(), $color->getG(), $color->getB());
    }

    protected function setLanguage(string $language): void
    {
        $this->lang = LangLoader::get($language);
    }

    protected function recalculateColumns(): void
    {
        // Can be replaced in child classes
    }

    protected function setDocumentSize(string $dSize): void
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

    protected function changeLanguageTerm(string $term, string $new): void
    {
        $this->lang[$term] = $new;
    }

    /**
     * @param int[] $bgColor
     */
    protected function fixedHeightCell(
        float $w,
        float $h,
        string $text,
        array $bgColor = [255, 255, 255],
        int $border = 1,
        string $align = 'L',
    ): void {
        $x = (float) $this->GetX();
        $y = (float) $this->GetY();

        $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);

        $style = $border ? 'FD' : 'F';
        $this->Rect($x, $y, $w, $h, $style);

        $fontSize = $this->FontSizePt;
        $minFontSize = 7.0;

        $nbLines = new InvoiceNbLines(
            $this->CurrentFont['cw'],
            $this->cMargin,
            $this->FontSize
        );
        do {
            $this->SetFontSize($fontSize);
            $lineHeight = $this->FontSize * 1.5;
            $lines = $nbLines->nbLines($w, $text);
            $textHeight = $lines * $lineHeight;

            if ($textHeight <= $h) {
                break;
            }

            $fontSize -= 0.5;
        } while ($fontSize >= $minFontSize);

        $offsetY = ($h - $textHeight) / 2;

        $this->SetXY($x, $y + max(0, $offsetY));
        $this->MultiCell($w, $lineHeight, $text, 0, $align);

        $this->SetXY($x + $w, $y);
    }

    /**
     * @throws PDFInvoiceException
     */
    protected function changeCharset(string $tmpFrom, bool $toUpper = false, string $toEncoding = self::ICONV_CHARSET_OUTPUT_B): string
    {
        $result = iconv(self::ICONV_CHARSET_INPUT, $toEncoding, $tmpFrom);
        if (!is_string($result)) {
            throw new PDFInvoiceException("Failed to convert string by iconv {$tmpFrom}");
        }
        if ($toUpper) {
            $result = mb_strtoupper($result, self::ICONV_CHARSET_INPUT);
        }

        return $result;
    }
}
