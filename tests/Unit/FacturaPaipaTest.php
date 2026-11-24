<?php

namespace Tests\Unit;

use App\Support\FacturaPaipa;
use PHPUnit\Framework\TestCase;

/**
 * Apoyo de la factura de Paipa (App\Support\FacturaPaipa): selección del cupón vigente, valor en letras
 * y fechas. No toca la base de datos.
 */
class FacturaPaipaTest extends TestCase
{
    public function test_cupon_vigente_deja_el_primero_que_no_ha_vencido()
    {
        list($v, $f, $p) = FacturaPaipa::cuponVigente([100, 90, 80], ['2026-11-30', '2026-12-31', '2027-01-31'], [10, 5, 0], '2026-11-06');
        $this->assertSame([100], $v);
        $this->assertSame(['2026-11-30'], $f);
        $this->assertSame([10], $p);
    }

    public function test_cupon_vigente_salta_el_que_ya_vencio()
    {
        list($v, $f, $p) = FacturaPaipa::cuponVigente([100, 90, 80], ['2026-10-01', '2026-12-31', '2027-01-31'], [10, 5, 0], '2026-11-06');
        $this->assertSame([90], $v);
        $this->assertSame(['2026-12-31'], $f);
        $this->assertSame([5], $p);
    }

    public function test_cupon_vigente_usa_el_dia_de_impresion_si_ya_no_queda_descuento()
    {
        list($v, $f) = FacturaPaipa::cuponVigente([100], ['2026-10-01'], [0], '2026-11-06');
        $this->assertSame([100], $v);
        $this->assertSame(['2026-11-06'], $f);

        // Sin fecha de descuento también es el día de impresión.
        list(, $f) = FacturaPaipa::cuponVigente([100], [''], [0], '2026-11-06');
        $this->assertSame(['2026-11-06'], $f);
    }

    public function test_cupon_vigente_acepta_una_fecha_que_vence_hoy()
    {
        list(, $f) = FacturaPaipa::cuponVigente([100], ['2026-11-06'], [0], '2026-11-06');
        $this->assertSame(['2026-11-06'], $f);
    }

    public function test_valor_en_letras()
    {
        $casos = [
            0 => 'CERO PESOS',
            1 => 'UN PESO',
            21 => 'VEINTIÚN PESOS',
            100 => 'CIEN PESOS',
            1001 => 'MIL UN PESOS',
            21000 => 'VEINTIÚN MIL PESOS',
            101000 => 'CIENTO UN MIL PESOS',
            222300 => 'DOSCIENTOS VEINTIDÓS MIL TRESCIENTOS PESOS',
            1000000 => 'UN MILLÓN DE PESOS',
            2000000 => 'DOS MILLONES DE PESOS',
            1021000 => 'UN MILLÓN VEINTIÚN MIL PESOS',
            1234567 => 'UN MILLÓN DOSCIENTOS TREINTA Y CUATRO MIL QUINIENTOS SESENTA Y SIETE PESOS',
            31000021 => 'TREINTA Y UN MILLONES VEINTIÚN PESOS',
        ];
        foreach ($casos as $valor => $letras) {
            $this->assertSame($letras, FacturaPaipa::totalEnLetras($valor), "valor $valor");
        }
    }

    public function test_fecha_acepta_lo_que_entrega_el_controlador()
    {
        $this->assertSame('14/03/2025', FacturaPaipa::fecha('14/03/2025'));
        $this->assertSame('14/03/2025', FacturaPaipa::fecha('2025-03-14 00:00:00.000'));
        $this->assertSame('', FacturaPaipa::fecha('N/D'));
        $this->assertSame('', FacturaPaipa::fecha(''));
        $this->assertSame('', FacturaPaipa::fecha(null));
    }

    public function test_zona_y_porcentaje()
    {
        $this->assertSame('Zona urbana', FacturaPaipa::zona('Urbano'));
        $this->assertSame('Zona rural', FacturaPaipa::zona('Rural'));
        $this->assertSame('', FacturaPaipa::zona(''));
        $this->assertSame('24,76', FacturaPaipa::porcentaje(24.76));
    }
}
