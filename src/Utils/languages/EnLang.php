<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class EnLang implements LangInterface
{
    public const string LANG_NAME = 'en';
    public const array LANG = [
        'number' => 'Reference',
        'date' => 'Billing date',
        'time' => 'Billing time',
        'due' => 'Due date',
        'payment' => 'Payment date',
        'to' => 'Billing to',
        'from' => 'Billing from',
        'product' => 'Product',
        'qty' => 'Qty',
        'price' => 'Price',
        'discount' => 'Discount',
        'vat' => 'Vat',
        'total' => 'Total',
        'page' => 'Page',
        'page_of' => 'of',
    ];
}
