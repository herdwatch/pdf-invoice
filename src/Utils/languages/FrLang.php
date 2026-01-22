<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class FrLang implements LangInterface
{
    public const string LANG_NAME = 'fr';
    public const array LANG = [
        'number' => 'Référence',
        'date' => 'Date de facturation',
        'time' => 'Heure de facturation',
        'due' => 'Date d\'échéance',
        'payment' => 'Date de paiement',
        'to' => 'Facturé à',
        'from' => 'Facturé par',
        'product' => 'Produit',
        'qty' => 'Quantité',
        'price' => 'Prix unit. HT',
        'discount' => 'Réduction',
        'vat' => 'TVA',
        'total' => 'Total',
        'page' => 'Page',
        'page_of' => 'sur',
    ];
}
