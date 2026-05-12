<?php
namespace App\Modules\Dte\Domain\Enums;
enum DteType: int
{
    case FACTURA_ELECTRTONICA = 33;
    case FACTURA_EXENTA_ELECTRONICA = 34;
    case BOLETA_ELECTRONICA = 39;
    case BOLETA_EXENTA_ELECTRONICA = 41;
    case FACTURA_COMPRA_ELECTRONICA = 46;
    case GUIA_DESPACHO_ELECTRONICA = 52;
    case NOTA_DEBITO_ELECTRRONICA = 56;
    case NOTA_CREDITO_ELECTRONICA = 61;
    public function isBoletaFamily(): bool
    {
        return in_array($this,[
            self::BOLETA_ELECTRONICA,
            self::BOLETA_EXENTA_ELECTRONICA,
        ], true);
    }
    public function isFacturaFamily():bool
    {
        return in_array($this,[
            self::FACTURA_ELECTRTONICA,
            self::FACTURA_EXENTA_ELECTRONICA,
            self::FACTURA_COMPRA_ELECTRONICA,
            self::GUIA_DESPACHO_ELECTRONICA,
            self::NOTA_DEBITO_ELECTRRONICA,
            self::NOTA_CREDITO_ELECTRONICA,
        ],true);
    }
}
