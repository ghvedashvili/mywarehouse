<?php

namespace App\Exports;

use App\Models\Product_Order;
use App\Models\StatusChangeLog;
use App\Models\OrderStatus;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Carbon;

class ExportCourierOrders implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    use Exportable;

    private int $totalCourier  = 0;
    private int $totalReturned = 0;
    private int $totalExchange = 0;
    private Carbon $date;
    private array $rowColors = [];

    public function __construct(?string $date = null)
    {
        $this->date = $date ? Carbon::parse($date) : Carbon::today();
    }

    public function collection()
    {
        $today = $this->date;

        // Load status labels from DB to avoid encoding issues
        $statusNames = OrderStatus::whereIn('id', [4, 5, 6])->pluck('name', 'id');

        // Orders given to courier today (status →4)
        $courierLogIds = StatusChangeLog::where('status_id_to', 4)
            ->whereDate('changed_at', $today)
            ->pluck('changed_at', 'order_id');

        // Orders returned today (status →5)
        $returnLogIds = StatusChangeLog::where('status_id_to', 5)
            ->whereDate('changed_at', $today)
            ->pluck('order_id');

        // Orders exchanged today (status →6)
        $exchangeLogIds = StatusChangeLog::where('status_id_to', 6)
            ->whereDate('changed_at', $today)
            ->pluck('order_id');

        // All relevant order IDs
        $allOrderIds = $courierLogIds->keys()
            ->merge($returnLogIds)
            ->merge($exchangeLogIds)
            ->unique();

        $orders = Product_Order::withoutGlobalScope('active')
            ->with(['customer.city', 'orderCity'])
            ->whereIn('id', $allOrderIds)
            ->where(function ($q) {
                $q->whereNull('merged_id')->orWhere('is_primary', 1);
            })
            ->get()
            ->keyBy('id');

        $rows = collect();

        // 1. Orders given to courier today
        foreach ($courierLogIds->keys() as $orderId) {
            $order = $orders->get($orderId);
            if (!$order) continue;

            $sameDayReturn   = $returnLogIds->contains($orderId);
            $sameDayExchange = $exchangeLogIds->contains($orderId);

            $rows->push([
                'order'       => $order,
                'status_type' => 4,
                'label'       => $statusNames[4] ?? '',
                'same_day'    => $sameDayReturn || $sameDayExchange,
            ]);
        }

        // 2. Orders returned today — include even if given on a previous day
        foreach ($returnLogIds as $orderId) {
            $order = $orders->get($orderId);
            if (!$order) continue;

            $rows->push([
                'order'       => $order,
                'status_type' => 5,
                'label'       => $statusNames[5] ?? '',
                'same_day'    => false,
            ]);
        }

        // 3. Orders exchanged today
        foreach ($exchangeLogIds as $orderId) {
            $order = $orders->get($orderId);
            if (!$order) continue;

            $rows->push([
                'order'       => $order,
                'status_type' => 6,
                'label'       => $statusNames[6] ?? '',
                'same_day'    => false,
            ]);
        }

        $this->totalCourier  = $rows->where('status_type', 4)->count();
        $this->totalReturned = $rows->where('status_type', 5)->count();
        $this->totalExchange = $rows->where('status_type', 6)->count();

        // Build row color map (row 1 = heading, data starts at row 2)
        $rowNum = 2;
        foreach ($rows as $item) {
            if ($item['status_type'] === 4 && $item['same_day']) {
                $this->rowColors[$rowNum] = 'FFE8E8'; // light red: given & returned same day
            } elseif ($item['status_type'] === 5) {
                $this->rowColors[$rowNum] = 'FFC7CE'; // red: returned
            } elseif ($item['status_type'] === 6) {
                $this->rowColors[$rowNum] = 'FFF3CD'; // yellow: exchange
            }
            $rowNum++;
        }

        return $rows->map(function ($item) {
            $order   = $item['order'];
            $orderNo = $order->order_number ?? ('S' . $order->id);
            $tel     = $order->order_alt_tel ?: ($order->customer->tel ?? '');
            $city    = $order->orderCity->name ?? $order->customer->city->name ?? '';
            $address = $order->order_address ?: ($order->customer->address ?? '');

            return [
                $orderNo,
                $order->customer->name ?? '',
                $tel,
                $city,
                $address,
                $item['label'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ორდერის ნომერი',
            'სახელი გვარი',
            'ტელეფონი',
            'ქალაქი',
            'მისამართი',
            'სტატუსი',
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
                $sheet = $event->sheet->getDelegate();

                // Apply row background colors
                foreach ($this->rowColors as $rowNum => $color) {
                    $highestCol = $sheet->getHighestColumn();
                    $sheet->getStyle('A' . $rowNum . ':' . $highestCol . $rowNum)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('FF' . $color);
                }

                // Footer with totals
                $lastRow = $sheet->getHighestRow() + 1;
                $total   = $this->totalCourier + $this->totalReturned + $this->totalExchange;
                $sheet->setCellValue(
                    'A' . $lastRow,
                    'კურიერთან: ' . $this->totalCourier .
                    '    დაბრუნებული: ' . $this->totalReturned .
                    '    გაცვლილი: ' . $this->totalExchange .
                    '    სულ: ' . $total
                );
                $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
            },
        ];
    }
}
