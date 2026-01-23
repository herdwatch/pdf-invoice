<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class TrLang implements LangInterface
{
    public const string LANG_NAME = 'tr';
    public const array LANG = [
        'number' => 'Fatura No',
        'date' => 'Fatura tarihi',
        'time' => 'Fatura zamanı',
        'due' => 'Vadesi',
        'payment' => 'Ödeme tarihi',
        'to' => 'Firma',
        'from' => 'Müşteri',
        'product' => 'Ürün',
        'qty' => 'Adet',
        'price' => 'Fiyat',
        'discount' => 'İndirim',
        'vat' => 'Vergi',
        'total' => 'Toplam',
        'page' => 'Sayfa',
        'page_of' => 'arasında',
    ];
}
