<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class ItLang implements LangInterface
{
    public const string LANG_NAME = 'it';
    public const array LANG = [
        'number' => 'Riferimento',
        'date' => 'Data di fatturazione',
        'due' => 'Scadenza',
        'payment' => 'Data di pagamento',
        'to' => 'Fatturazione per',
        'from' => 'Nostre informazioni',
        'product' => 'Prodotto',
        'qty' => 'Quantità',
        'price' => 'Prezzo',
        'discount' => 'Sconto',
        'vat' => 'Imposta',
        'total' => 'Totale',
        'page' => 'Pagina',
        'page_of' => 'di',
    ];
}
