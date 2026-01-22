<?php

namespace Herdwatch\PdfInvoice\Data;

abstract readonly class AbstractInvoiceItem
{
    public function __construct(
        private string $name,
        private string $description,
        private string $quantity,
        private string $total,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getTotal(): string
    {
        return $this->total;
    }
}
