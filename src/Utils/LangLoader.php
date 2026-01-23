<?php

namespace Herdwatch\PdfInvoice\Utils;

use Herdwatch\PdfInvoice\Utils\languages\BrLang;
use Herdwatch\PdfInvoice\Utils\languages\DeLang;
use Herdwatch\PdfInvoice\Utils\languages\EnLang;
use Herdwatch\PdfInvoice\Utils\languages\EsLang;
use Herdwatch\PdfInvoice\Utils\languages\EtLang;
use Herdwatch\PdfInvoice\Utils\languages\FrLang;
use Herdwatch\PdfInvoice\Utils\languages\ItLang;
use Herdwatch\PdfInvoice\Utils\languages\LtLang;
use Herdwatch\PdfInvoice\Utils\languages\NlLang;
use Herdwatch\PdfInvoice\Utils\languages\PlLang;
use Herdwatch\PdfInvoice\Utils\languages\RoLang;
use Herdwatch\PdfInvoice\Utils\languages\SvLang;
use Herdwatch\PdfInvoice\Utils\languages\TrLang;

class LangLoader
{
    /**
     * @var string[]
     */
    private static array $languages = [
        EnLang::class,
        BrLang::class,
        DeLang::class,
        EsLang::class,
        EtLang::class,
        FrLang::class,
        ItLang::class,
        LtLang::class,
        NlLang::class,
        PlLang::class,
        RoLang::class,
        SvLang::class,
        TrLang::class,
    ];

    /**
     * @return array<string, string>
     */
    public static function get(string $lang): array
    {
        $class = null;
        foreach (self::$languages as $languageClass) {
            if ($languageClass::LANG_NAME === $lang) {
                $class = $languageClass;
                break;
            }
        }
        if (null === $class) {
            $class = EnLang::class;
        }

        return $class::LANG;
    }
}
