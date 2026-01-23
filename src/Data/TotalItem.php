<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class TotalItem
{
    public function __construct(
        private string $name,
        private string $value,
        private bool $colored,
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
}
