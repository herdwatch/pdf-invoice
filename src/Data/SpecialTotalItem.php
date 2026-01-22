<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class SpecialTotalItem
{
    public function __construct(
        private string $name,
        private string $value,
        private bool $colored,
        private ?Color $bgColor,
        private ?Color $textColor,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isColored(): bool
    {
        return $this->colored;
    }

    public function getBgColor(): ?Color
    {
        return $this->bgColor;
    }

    public function getTextColor(): ?Color
    {
        return $this->textColor;
    }
}
