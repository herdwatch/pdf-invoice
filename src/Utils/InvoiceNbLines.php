<?php

namespace Herdwatch\PdfInvoice\Utils;

readonly class InvoiceNbLines
{
    /**
     * @param int[] $currentFont
     */
    public function __construct(
        private array $currentFont,
        private float $cMargin,
        private float $fontSize,
    ) {
    }

    public function nbLines(float $w, string $text): int
    {
        $cw = $this->currentFont;
        $wMax = ($w - 2 * $this->cMargin) * 1000 / $this->fontSize;
        $text = rtrim(str_replace("\r", '', $text), "\n");

        $length = strlen($text);
        $lines = 1;

        $lineWidth = 0;
        $lastSpace = -1;
        $lineStart = 0;

        for ($i = 0; $i < $length; ++$i) {
            $char = $text[$i];

            if ("\n" === $char) {
                $this->resetLine($i + 1, $lineWidth, $lastSpace, $lineStart, $lines);
                continue;
            }

            if (' ' === $char) {
                $lastSpace = $i;
            }

            $lineWidth += $cw[$char] ?? 0;

            if ($lineWidth <= $wMax) {
                continue;
            }

            $this->wrapLine($i, $lastSpace, $lineWidth, $lineStart, $lines);
        }

        return $lines;
    }

    private function resetLine(
        int $nextIndex,
        int &$lineWidth,
        int &$lastSpace,
        int &$lineStart,
        int &$lines,
    ): void {
        $lineWidth = 0;
        $lastSpace = -1;
        $lineStart = $nextIndex;
        ++$lines;
    }

    private function wrapLine(
        int &$index,
        int $lastSpace,
        int &$lineWidth,
        int &$lineStart,
        int &$lines,
    ): void {
        if (-1 === $lastSpace) {
            $index = max($index, $lineStart);
        } else {
            $index = $lastSpace;
        }

        $lineWidth = 0;
        $lineStart = $index + 1;
        ++$lines;
    }
}
