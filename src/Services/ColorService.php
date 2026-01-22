<?php

namespace Herdwatch\PdfInvoice\Services;

use Herdwatch\PdfInvoice\Data\Color;
use Herdwatch\PdfInvoice\ExtendedFPDF;

readonly class ColorService
{
    public function __construct(
        private ExtendedFPDF $extendedFPDF,
    ) {
    }

    public function setTextColorData(Color $color): void
    {
        $this->extendedFPDF->SetTextColor($color->getR(), $color->getG(), $color->getB());
    }

    public function setFillColorData(Color $color): void
    {
        $this->extendedFPDF->SetFillColor($color->getR(), $color->getG(), $color->getB());
    }

    public function setDrawColorData(Color $color): void
    {
        $this->extendedFPDF->SetDrawColor($color->getR(), $color->getG(), $color->getB());
    }
}
