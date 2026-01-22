<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class CreditNoteItem extends InvoiceItem
{
    public function __construct(
        string $name,
        string $description,
        string $quantity,
        string $price,
        string $discount,
        string $vat,
        string $total,
        private string $vatPercent,
    ) {
        parent::__construct(
            $name,
            $description,
            $quantity,
            $price,
            $discount,
            $vat,
            $total
        );
    }

    public function getVatPercent(): string
    {
        return $this->vatPercent;
    }
}
