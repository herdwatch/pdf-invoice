<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class LtLang implements LangInterface
{
    public const string LANG_NAME = 'lt';
    public const array LANG = [
        'number' => 'Sąskaitos numeris',
        'date' => 'Data',
        'time' => 'Laikas',
        'due' => 'Sumokėti iki',
        'payment' => 'Mokėjimo diena',
        'to' => 'Pirkėjas',
        'from' => 'Pardavėjas',
        'product' => 'Prekės',
        'qty' => 'Kiekis',
        'price' => 'Kaina',
        'discount' => 'Nuolaida',
        'vat' => 'PVM',
        'total' => 'Suma',
        'page' => 'Puslapis',
        'page_of' => 'iš',
    ];
}
