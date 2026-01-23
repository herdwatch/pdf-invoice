<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class NlLang implements LangInterface
{
    public const string LANG_NAME = 'nl';
    public const array LANG = [
        'number' => 'Referentie',
        'date' => 'Aangemaakt',
        'time' => 'Tijd',
        'due' => 'Vervaldatum',
        'payment' => 'Betaaldatum',
        'to' => 'Begunstigde',
        'from' => 'Onze gegevens',
        'product' => 'Product',
        'qty' => 'Aantal',
        'price' => 'Prijs',
        'discount' => 'Korting',
        'vat' => 'BTW',
        'total' => 'Totaal',
        'page' => 'Pagina',
        'page_of' => 'van',
    ];
}
