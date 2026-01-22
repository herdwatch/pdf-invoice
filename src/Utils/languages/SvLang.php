<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class SvLang implements LangInterface
{
    public const string LANG_NAME = 'sv';
    public const array LANG = [
        'number' => 'Faktura nr',
        'date' => 'Faktura Datum',
        'time' => 'Tid',
        'due' => 'Sista betalnings dag',
        'payment' => 'Betalningsdatum',
        'to' => 'Till',
        'from' => 'Från',
        'product' => 'Produkt',
        'qty' => 'Antal',
        'price' => 'Pris',
        'discount' => 'Rabatt',
        'vat' => 'moms',
        'total' => 'Totalt',
        'page' => 'Sida',
        'page_of' => 'av',
    ];
}
