<?php

namespace Herdwatch\PdfInvoice;

class PDFInvoiceException extends \Exception
{
    public function __construct(string $message = 'PDF-Invoice exception occur', int $code = 1)
    {
        parent::__construct($message, $code);
    }
}
