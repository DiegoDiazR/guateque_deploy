<?php

namespace App\Exports;

use App\Models\ViewPredialCartera;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportCartera implements FromView, WithTitle, WithEvents
    // implements FromCollection, WithHeadings, WithCustomStartCell, ShouldAutoSize,
    // WithColumnFormatting, WithMapping, WithStyles, WithDrawings //, WithDefaultStyles, FromView
{
    // use Exportable;

    // protected $pathImage;
    // protected $nit;
    // protected $alcaldia;

    // public function __construct(string $pathImage, string $nit, string $alcaldia) {
    //     $this->pathImage = $pathImage;
    //     $this->nit = $nit;
    //     $this->alcaldia = $alcaldia;
    // }

    // /**
    // * @return \Illuminate\Support\Collection
    // */
    // public function headings():array {
    //     return[
    //         'Año',
    //         'Factura',
    //         'Codigo predio',
    //         'Propietario',
    //         'Predial presente año',
    //         'Interes predial',
    //         'Car',
    //         'Interes Car',
    //         'Total vigencia'
    //     ];
    // }

    // /**
    // * @return \Illuminate\Support\Collection
    // */
    // public function collection() {
    //     return ViewPredialCartera::select(
    //         'ultimo_anio',
    //         'factura_pago',
    //         'codigo_predio',
    //         'nombre_propietario',
    //         'predial',
    //         'interespredial',
    //         'car',
    //         'interescar',
    //         'totalvigencia',
    //     )
    //     ->where('ultimo_anio', '<=', Carbon::now()->year)
    //     ->orderBy('codigo_predio', 'asc')
    //     ->orderBy('ultimo_anio', 'asc')
    //     ->get();
    // }

    // public function startCell(): string {
    //     return 'A7';
    // }

    // public function map($row): array {
    //     return [
    //         $row->ultimo_anio,
    //         $row->factura_pago,
    //         $row->codigo_predio,
    //         $row->nombre_propietario,
    //         $row->predial,
    //         $row->interespredial,
    //         $row->car,
    //         $row->interescar,
    //         $row->totalvigencia,
    //     ];
    // }

    // public function columnFormats(): array {
    //     return [
    //         'E' => NumberFormat::FORMAT_CURRENCY_USD,
    //         'F' => NumberFormat::FORMAT_CURRENCY_USD,
    //         'G' => NumberFormat::FORMAT_CURRENCY_USD,
    //         'H' => NumberFormat::FORMAT_CURRENCY_USD,
    //         'I' => NumberFormat::FORMAT_CURRENCY_USD,
    //     ];
    // }

    // // public function defaultStyles(Style $defaultStyle)
    // // {
    // //     // Configure the default styles
    // //     // return $defaultStyle->getFill()->setFillType(Fill::FILL_SOLID);

    // //     // Or return the styles array
    // //     return [
    // //         'fill' => [
    // //             'fillType'   => Fill::FILL_SOLID,
    // //             'startColor' => ['argb' => Color::COLOR_BLUE],
    // //         ],
    // //     ];
    // // }

    // public function drawings() {
    //     $drawing = new Drawing();
    //     $drawing->setName('Logo');
    //     $drawing->setDescription('Logo');
    //     $drawing->setPath(public_path('/theme/plugins/images/' . $this->pathImage));
    //     $drawing->setHeight(76);
    //     $drawing->setCoordinates('B2');

    //     return $drawing;
    // }

    // public function styles(Worksheet $sheet) {
    //     return [
    //         // Style the first row as bold text.
    //         7 => [
    //             'font' => ['bold' => true],
    //             'borders' => [
    //                 'allBorders' => [
    //                     'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
    //                     'color' => ['rgb' => '000000'],
    //                 ],
    //             ],
    //             'fill' => [
    //                 'fillType'   => Fill::FILL_SOLID,
    //                 'startColor' => ['argb' => 'ededed'],
    //             ],
    //         ],
    //         // Styling a specific cell by coordinate.
    //         // 'B2' => ['font' => ['italic' => true]],

    //         // Styling an entire column.
    //         // 'C'  => ['font' => ['size' => 16]],
    //     ];
    // }

    /** Cantidad de filas de datos (se llena en view()); se usa en registerEvents(). */
    protected $totalRegistros = 0;

    /**
     * Formato de las filas de datos. Antes cada celda llevaba style/width/data-format en el HTML y
     * PhpSpreadsheet tardaba ~4 ms por celda en interpretarlos; ahora se aplica una sola vez por
     * columna y el resultado en Excel es el mismo.
     * Filas del HTML: 1-3 encabezado con logo, 4-5 titulos, 6 encabezados de columna, datos desde la 7.
     */
    public function registerEvents(): array {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if ($this->totalRegistros < 1) {
                    return;
                }
                $sheet = $event->sheet->getDelegate();
                $primera = 7;
                $ultima = 6 + $this->totalRegistros;

                foreach (['A' => 8, 'B' => 13, 'C' => 27, 'D' => 60, 'E' => 20, 'F' => 17, 'G' => 17, 'H' => 17, 'I' => 17] as $col => $ancho) {
                    $sheet->getColumnDimension($col)->setWidth($ancho);
                }

                $sheet->getStyle("A{$primera}:C{$ultima}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $valores = $sheet->getStyle("E{$primera}:I{$ultima}");
                $valores->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $valores->getNumberFormat()->setFormatCode('$#,##0_-');
            },
        ];
    }

    public function title(): string {
        return 'reporte';
    }

    public function view(): View {
        $parametro_logo = DB::table('parametros')
                              ->select('parametros.valor')
                              ->where('parametros.nombre', 'logo')
                              ->first();

        $parametro_nit = DB::table('parametros')
                              ->select('parametros.valor')
                              ->where('parametros.nombre', 'nit')
                              ->first();

        $parametro_alcaldia = DB::table('parametros')
                              ->select('parametros.valor')
                              ->where('parametros.nombre', 'alcaldia')
                              ->first();

        $parametro_direccion = DB::table('parametros')
                              ->select('parametros.valor')
                              ->where('parametros.nombre', 'direccion')
                              ->first();

        $parametro_ubicacion = DB::table('parametros')
                              ->select('parametros.valor')
                              ->where('parametros.nombre', 'ubicacion')
                              ->first();

        $vista = view('exports.reporteCarteraEXCEL', [
            'registros' => ViewPredialCartera::select(
                        'ultimo_anio',
                        'factura_pago',
                        'codigo_predio',
                        'nombre_propietario',
                        'predial',
                        'interespredial',
                        'car',
                        'interescar',
                        'totalvigencia',
                    )
                    ->where('ultimo_anio', '<=', Carbon::now()->year)
                    ->orderBy('codigo_predio', 'asc')
                    ->orderBy('ultimo_anio', 'asc')
                    ->get(),
            'anio' => Carbon::now()->year,
            'logo' => $parametro_logo->valor,
            'nit' => $parametro_nit->valor,
            'alcaldia' => $parametro_alcaldia->valor,
            'direccion' => $parametro_direccion->valor,
            'ubicacion' => $parametro_ubicacion->valor,
            'fecha' => Carbon::now()->format('d/m/Y, h:i:s A')
        ]);

        $this->totalRegistros = count($vista->getData()['registros']);

        return $vista;
    }
}
