<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class EtLang implements LangInterface
{
    public const string LANG_NAME = 'et';
    public const array LANG = [
        'number' => 'Number',
        'date' => 'Kuupäev',
        'time' => 'Kellaaeg',
        'due' => 'Tähtaeg',
        'payment' => 'Maksekuupäev',
        'to' => 'Ostja',
        'from' => 'Müüja',
        'product' => 'Toode',
        'qty' => 'Kogus',
        'price' => 'Hind',
        'discount' => 'Allah.',
        'vat' => 'KM',
        'total' => 'Kokku',
        'page' => 'Lk',
        'page_of' => '/',
    ];
}
