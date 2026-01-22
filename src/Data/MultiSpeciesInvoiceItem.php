<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class MultiSpeciesInvoiceItem extends AbstractInvoiceItem
{
    public function __construct(
        string $name,
        string $description,
        private string $extendedDescription,
        private string $notes,
        string $quantity,
        string $total,
        private bool $negative = false,
    ) {
        parent::__construct($name, $description, $quantity, $total);
    }

    public function getExtendedDescription(): string
    {
        return $this->extendedDescription;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function isNegative(): bool
    {
        return $this->negative;
    }
}
