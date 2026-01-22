<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class InvoiceItem extends AbstractInvoiceItem
{
    public function __construct(
        string $name,
        string $description,
        string $quantity,
        private string $price,
        private string $discount,
        private string $vat,
        string $total,
    ) {
        parent::__construct($name, $description, $quantity, $total);
    }

    public function getVat(): string
    {
        return $this->vat;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }
}
