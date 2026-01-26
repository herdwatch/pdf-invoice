<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class TotalItem
{
    public function __construct(
        private string $name,
        private string $value,
        private bool $colored,
        private bool $negativeRed,
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

    public function isNegativeRed(): bool
    {
        return $this->negativeRed;
    }
}
