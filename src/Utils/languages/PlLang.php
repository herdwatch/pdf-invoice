<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class PlLang implements LangInterface
{
    public const string LANG_NAME = 'pl';
    public const array LANG = [
        'number' => 'Numer',
        'date' => 'Data',
        'time' => 'Godzina',
        'due' => 'Termin płatności',
        'payment' => 'Termin płatności',
        'to' => 'Nabywca',
        'from' => 'Sprzedawca',
        'product' => 'Produkt',
        'qty' => 'Ilość',
        'price' => 'Cena',
        'discount' => 'Rabat',
        'vat' => 'Vat',
        'total' => 'Razem',
        'page' => 'Strona',
        'page_of' => 'z',
    ];
}
