<?php

namespace Herdwatch\PdfInvoice\Data;

readonly class TextLinkItem
{
    public function __construct(
        private string $type,
        private string $text,
        private string $encoding,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }
}
