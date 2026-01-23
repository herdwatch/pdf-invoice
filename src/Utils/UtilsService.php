<?php

namespace Herdwatch\PdfInvoice\Utils;

use Herdwatch\PdfInvoice\Data\Color;

class UtilsService
{
    public function br2nl(string $string): string
    {
        return (string) preg_replace('/<br(\s+)?\/?>/i', "\n", $string);
    }

    /**
     * @return int[]
     */
    public function hex2rgbArray(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        if (3 === strlen($hex)) {
            $r = (int) hexdec($hex[0] . $hex[0]);
            $g = (int) hexdec($hex[1] . $hex[1]);
            $b = (int) hexdec($hex[2] . $hex[2]);
        } else {
            $r = (int) hexdec(substr($hex, 0, 2));
            $g = (int) hexdec(substr($hex, 2, 2));
            $b = (int) hexdec(substr($hex, 4, 2));
        }

        return [$r, $g, $b];
    }

    public function hex2color(string $hex): Color
    {
        return new Color(...$this->hex2rgbArray($hex));
    }

    public function pixelsToMM(float $val): float
    {
        $mm_inch = 25.4;
        $dpi = 96;

        return ($val * $mm_inch) / $dpi;
    }
}
