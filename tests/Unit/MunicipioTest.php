<?php

namespace Tests\Unit;

use App\Support\Municipio;
use Tests\TestCase;

/**
 * Identidad del municipio (App\Support\Municipio). No toca la base de datos: el contenido de
 * `parametros` se fija con Municipio::fijar().
 */
class MunicipioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['municipio.slug' => null]);
        // El .env de la máquina no debe alterar estas pruebas.
        config(['municipio.sobrescribir' => array_fill_keys(array_keys(config('municipio.sobrescribir')), null)]);
    }

    protected function tearDown(): void
    {
        Municipio::fijar([]);
        parent::tearDown();
    }

    private function conParametros(array $parametros)
    {
        Municipio::fijar($parametros);
    }

    public function testSlugSalDelArchivoDelLogo()
    {
        $casos = [
            'logo-guateque.png' => 'guateque',
            'logo-paipa.png' => 'paipa',
            'logo-sutatenza.png' => 'sutatenza',
            'logo-lacapilla.png' => 'lacapilla',
            'logo-santarosa.png' => 'santarosa',
            'logo-villadeleyva.png' => 'villadeleyva',
            'logo-sesquile.png' => 'sesquile',
            'logo-sesquile-2.png' => 'sesquile',
            'logo-Santa-Rosa.PNG' => 'santarosa',
        ];
        foreach ($casos as $archivo => $esperado) {
            $this->conParametros(['logo' => $archivo, 'alcaldia' => 'ALCALDÍA DE OTRO LUGAR']);
            $this->assertSame($esperado, Municipio::slug(), $archivo);
        }
    }

    public function testSlugSaleDelNombreDeLaAlcaldiaSiElLogoNoLoDice()
    {
        $casos = [
            'ALCALDÍA DE VILLA DE LEYVA' => 'villadeleyva',
            'ALCALDÍA MUNICIPAL DE LA CAPILLA' => 'lacapilla',
            'Alcaldía de Santa Rosa de Viterbo' => 'santarosa',
            'ALCALDIA DE SESQUILE' => 'sesquile',
            'ALCALDÍA DE GUATEQUE' => 'guateque',
        ];
        foreach ($casos as $alcaldia => $esperado) {
            $this->conParametros(['logo' => 'logo-escudo.png', 'alcaldia' => $alcaldia]);
            $this->assertSame($esperado, Municipio::slug(), $alcaldia);
        }
    }

    public function testSlugPorOmisionEsPaipaYElEnvManda()
    {
        $this->conParametros([]);
        $this->assertSame('paipa', Municipio::slug());

        config(['municipio.slug' => 'Villa de Leyva']);
        $this->conParametros(['logo' => 'logo-guateque.png']);
        $this->assertSame('villadeleyva', Municipio::slug(), 'MUNICIPIO del .env manda sobre la base de datos');
    }

    public function testNombreEnMayusculasYMinusculasDeTitulo()
    {
        $this->conParametros(['alcaldia' => 'ALCALDÍA DE GUATEQUE', 'logo' => 'logo-guateque.png']);
        $this->assertSame('Alcaldía de Guateque', Municipio::nombre());
        $this->assertSame('Guateque', Municipio::nombreCorto());

        $this->conParametros(['alcaldia' => 'ALCALDÍA DE VILLA DE LEYVA']);
        $this->assertSame('Alcaldía de Villa de Leyva', Municipio::nombre());
        $this->assertSame('Villa de Leyva', Municipio::nombreCorto());

        // las tildes que falten en la base se corrigen con el nombre del perfil
        $this->conParametros(['alcaldia' => 'ALCALDIA DE SESQUILE']);
        $this->assertSame('Alcaldía de Sesquilé', Municipio::nombre());
        $this->assertSame('Sesquilé', Municipio::nombreCorto());

        // un texto que ya viene en mayúsculas y minúsculas no se toca
        $this->conParametros(['alcaldia' => 'Alcaldía Municipal de La Capilla']);
        $this->assertSame('Alcaldía Municipal de La Capilla', Municipio::nombre());
    }

    public function testSinBaseDeDatosSeUsanLosValoresPorOmision()
    {
        $this->conParametros([]);
        $this->assertSame('Alcaldía de Paipa', Municipio::nombre());
        $this->assertSame('Paipa', Municipio::nombreCorto());
        $this->assertSame('Boyacá', Municipio::departamento());
        $this->assertSame('#1fa49d', Municipio::perfil()['color']);
        $this->assertNull(Municipio::param('no-existe'));
        $this->assertSame('x', Municipio::param('no-existe', 'x'));
    }

    public function testDepartamentoYUbicacion()
    {
        $this->conParametros(['logo' => 'logo-sesquile.png', 'ubicacion' => 'SESQUILE - CUNDINAMARCA']);
        $this->assertSame('Cundinamarca', Municipio::departamento());
        $this->assertSame('Sesquilé - Cundinamarca', Municipio::ubicacion());

        $this->conParametros(['logo' => 'logo-guateque.png']);
        $this->assertSame('Guateque - Boyacá', Municipio::ubicacion());
    }

    public function testPaipaTraeSuContactoYLosDemasNoInventanNada()
    {
        $this->conParametros(['logo' => 'logo-paipa.png', 'alcaldia' => 'ALCALDÍA DE PAIPA', 'direccion' => 'Otra dirección']);
        $tipos = array_column(Municipio::contacto(), 'tipo');
        $this->assertSame(['telefono', 'correo', 'direccion'], $tipos);
        $this->assertSame('Secretaría de Hacienda · Paipa', Municipio::ceja());
        $this->assertSame('Carrera 22 # 25-14, primer piso', Municipio::direccion(), 'el perfil de Paipa manda sobre parametros');

        $this->conParametros(['logo' => 'logo-guateque.png', 'alcaldia' => 'ALCALDÍA DE GUATEQUE', 'direccion' => 'Carrera 6 # 9-18', 'nit' => '800.013.683-9']);
        $contacto = Municipio::contacto();
        $this->assertCount(1, $contacto, 'solo la dirección: no hay teléfono ni correo confirmados');
        $this->assertSame('Carrera 6 # 9-18, Guateque, Boyacá', $contacto[0]['texto']);
        $this->assertSame('Guateque · Boyacá', Municipio::ceja());
        $this->assertSame('800.013.683-9', Municipio::nit());
        $this->assertArrayNotHasKey('secretaria', Municipio::perfil());
        $this->assertSame([], Municipio::perfil()['enlaces']);
    }

    public function testMunicipioNuevoNoHeredaNadaDePaipa()
    {
        $this->conParametros(['logo' => 'logo-chiquinquira.png', 'alcaldia' => 'ALCALDÍA DE CHIQUINQUIRÁ', 'ubicacion' => 'CHIQUINQUIRÁ - BOYACÁ']);
        $this->assertSame('chiquinquira', Municipio::slug());
        $this->assertSame('Alcaldía de Chiquinquirá', Municipio::nombre());
        $this->assertSame('Chiquinquirá', Municipio::nombreCorto());
        $this->assertSame('Boyacá', Municipio::departamento());
        $this->assertSame([], Municipio::contacto());
        $this->assertNull(Municipio::logoCompletoUrl());
        $this->assertNull(Municipio::fondoUrl());
    }

    public function testElEnvSobrescribeElPerfil()
    {
        config([
            'municipio.sobrescribir.telefono' => '601 555 0000',
            'municipio.sobrescribir.color' => '#2255aa',
            'municipio.sobrescribir.color_oscuro' => 'no-es-un-color',
        ]);
        $this->conParametros(['logo' => 'logo-guateque.png', 'alcaldia' => 'ALCALDÍA DE GUATEQUE']);
        $this->assertSame('601 555 0000', Municipio::perfil()['telefono']);
        $this->assertSame('#2255aa', Municipio::perfil()['color']);
        $this->assertSame('#17756f', Municipio::perfil()['color_oscuro'], 'un color inválido vuelve al predeterminado');
    }

    public function testTitulo()
    {
        $this->assertSame('Villa de Leyva - Boyacá', Municipio::titulo('VILLA DE LEYVA - BOYACÁ'));
        $this->assertSame('Alcaldía de La Capilla', Municipio::titulo('ALCALDÍA DE LA CAPILLA'));
        $this->assertSame('Ya Viene Bien', Municipio::titulo('Ya Viene Bien'));
        $this->assertSame('', Municipio::titulo('   '));
    }
}
