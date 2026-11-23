<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportActiveClients implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    use Exportable;

    public function __construct(private array $rows) {}

    public function array(): array
    {
        $seq = 0;
        $out = [];

        foreach ($this->rows as $r) {
            $seq++;
            $out[] = [
                $seq,
                $r['name'],
                $r['tel'],
                $r['alternative_tel'],
                $r['city_name'],
                $r['address'],
                $r['email'],
                $r['comment'],
                $r['order_count'],
                $r['order_numbers'],
            ];
        }

        return $out;
    }

    public function headings(): array
    {
        return ['#', 'სახელი', 'ტელეფონი', 'დამატ. ტელ.', 'ქალაქი', 'მისამართი', 'ემაილი', 'კომენტარი', 'ორდ. რაოდ.', 'ორდერების ნომრები'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 24,
            'C' => 14,
            'D' => 14,
            'E' => 14,
            'F' => 32,
            'G' => 22,
            'H' => 28,
            'I' => 10,
            'J' => 36,
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE85D26']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();

                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getStyle('A1:' . $lastCol . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getStyle('A1:' . $lastCol . $lastRow)->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE0E0E0');
                $sheet->freezePane('A2');
            },
        ];
    }
}
