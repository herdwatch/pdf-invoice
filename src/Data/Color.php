<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class Color
{
    public function __construct(
        private int $r,
        private int $g,
        private int $b,
    ) {
    }

    public function getR(): int
    {
        return $this->r;
    }

    public function getG(): int
    {
        return $this->g;
    }

    public function getB(): int
    {
        return $this->b;
    }
}
