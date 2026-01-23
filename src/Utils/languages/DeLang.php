<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class DeLang implements LangInterface
{
    public const string LANG_NAME = 'de';
    public const array LANG = [
        'number' => 'Rechnungsnummer',
        'date' => 'Rechnungsdatum',
        'time' => 'Rechnungs-Uhrzeit',
        'due' => 'Fälligkeitsdatum',
        'payment' => 'Zahlungsdatum',
        'to' => 'Rechnungsempfänger',
        'from' => 'Rechnung von',
        'product' => 'Produkt',
        'qty' => 'Menge',
        'price' => 'Preis',
        'discount' => 'Rabatt',
        'vat' => 'MwSt',
        'total' => 'Gesamt',
        'page' => 'Seite',
        'page_of' => 'von',
    ];
}
