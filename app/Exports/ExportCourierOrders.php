<?php

namespace App\Exports;

use App\Models\Product_Order;
use App\Models\StatusChangeLog;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Carbon;

class ExportCourierOrders implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    use Exportable;

    private int $total = 0;

    public function collection()
    {
        $today = Carbon::today();

        $logIds = StatusChangeLog::where('status_id_to', 4)
            ->whereDate('changed_at', $today)
            ->pluck('changed_at', 'order_id');

        // მხოლოდ primary ან ungrouped ორდერები (ჯგუფი = 1 ორდერი)
        $orders = Product_Order::withoutGlobalScope('active')
            ->with(['customer'])
            ->whereIn('id', $logIds->keys())
            ->where('status_id', 4)
            ->where(function ($q) {
                $q->whereNull('merged_id')->orWhere('is_primary', 1);
            })
            ->get();

        $this->total = $orders->count();

        return $orders->map(function ($order) use ($logIds) {
            $orderNo = $order->order_number ?? ('S' . $order->id);
            $tel     = $order->order_alt_tel ?: ($order->customer->tel ?? '');
            $address = $order->order_address ?: ($order->customer->address ?? '');

            return [
                $orderNo,
                $order->customer->name ?? '',
                $tel,
                $address,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ორდერის ნომერი',
            'სახელი გვარი',
            'ტელეფონი',
            'მისამართი',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow() + 1;
                $sheet->setCellValue('A' . $lastRow, 'სულ: ' . $this->total . ' ორდერი');
                $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
            },
        ];
    }
}
