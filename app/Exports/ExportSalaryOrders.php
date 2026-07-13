<?php

namespace App\Exports;

use App\Models\Product_Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportSalaryOrders implements FromArray, WithHeadings, WithStyles, WithEvents, WithColumnWidths
{
    use Exportable;

    private array $rows      = [];
    private array $images    = [];
    private array $debts     = [];
    private int   $totalRow  = 0;

    private float $sumOrig    = 0;
    private float $sumDisc    = 0;
    private float $sumCost    = 0;
    private float $sumCourier = 0;
    private float $sumNet     = 0;
    private float $sumPaid    = 0;
    private float $sumDebt    = 0;

    public function __construct(
        private string  $month,
        private ?int    $userId = null,
    ) {}

    public function array(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $this->month)->endOfMonth();

        $userMap = User::pluck('name', 'id');

        $q = Product_Order::withoutGlobalScope('active')
            ->with([
                'product'      => fn($q) => $q->withoutGlobalScope('active'),
                'customer.city',
                'orderStatus',
            ])
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->whereBetween('fully_paid_at', [$start, $end]);

        if ($this->userId) {
            $q->where('user_id', $this->userId);
        } else {
            $saleOperatorIds = User::where('role', 'sale_operator')->pluck('id');
            $q->whereIn('user_id', $saleOperatorIds);
        }

        $orders = $q->orderBy('fully_paid_at')->get();

        $excelRow = 2;
        $seq      = 0;

        foreach ($orders as $order) {
            $seq++;
            $customer = $order->customer;
            $cityName = $customer?->city?->name ?? '';
            $phone    = $order->order_alt_tel ?: ($customer?->tel ?? '');

            $orig    = (float)$order->price_georgia;
            $disc    = (float)($order->discount ?? 0);
            $cost    = (float)($order->price_usa ?? 0);
            $courier = (float)($order->courier_price_tbilisi    ?? 0)
                     + (float)($order->courier_price_region     ?? 0)
                     + (float)($order->courier_price_village    ?? 0);
            $paid    = (float)($order->paid_tbc  ?? 0) + (float)($order->paid_bog  ?? 0)
                     + (float)($order->paid_lib  ?? 0) + (float)($order->paid_cash ?? 0);
            $net     = $orig - $disc - $cost - $courier;
            $debt    = round(max(0, ($orig - $disc) - $paid), 2);

            $this->sumOrig    += $orig;
            $this->sumDisc    += $disc;
            $this->sumCost    += $cost;
            $this->sumCourier += $courier;
            $this->sumNet     += $net;
            $this->sumPaid    += $paid;
            $this->sumDebt    += $debt;

            $gd = $this->loadImageResource($order->product);
            if ($gd) {
                $this->images[$excelRow] = $gd;
            }
            if ($debt > 0) {
                $this->debts[$excelRow] = true;
            }

            $this->rows[] = [
                $seq,
                '',
                $userMap[$order->user_id] ?? '—',
                $order->product?->name ?? '—',
                $order->product_size ?? '',
                $customer?->name ?? '—',
                $phone,
                $cityName,
                round($orig, 2),
                $disc > 0 ? round($disc, 2) : '',
                $cost > 0 ? round($cost, 2) : '',
                $courier > 0 ? round($courier, 2) : '',
                round($net, 2),
                $paid > 0 ? round($paid, 2) : '',
                $debt > 0 ? $debt : '',
                $order->orderStatus?->name ?? '',
                $order->order_number ?? ('S' . $order->id),
                $order->created_at?->format('d.m.Y') ?? '',
                $order->fully_paid_at?->format('d.m.Y') ?? '',
            ];

            $excelRow++;
        }

        $this->totalRow = $excelRow;

        // totals row
        $this->rows[] = [
            '', '', 'სულ', '', '', '', '', '',
            round($this->sumOrig, 2),
            $this->sumDisc > 0 ? round($this->sumDisc, 2) : '',
            $this->sumCost > 0 ? round($this->sumCost, 2) : '',
            $this->sumCourier > 0 ? round($this->sumCourier, 2) : '',
            round($this->sumNet, 2),
            round($this->sumPaid, 2),
            $this->sumDebt > 0 ? round($this->sumDebt, 2) : '',
            '', '', '', '',
        ];

        return $this->rows;
    }

    public function headings(): array
    {
        return [
            '#', 'სურათი', 'გამყიდველი', 'პროდუქტი', 'ზომა',
            'კლიენტი', 'ტელ', 'ქ-ი',
            'ფასი ₾', 'ფასდ. ₾', 'ფასი $', 'საკ. ₾', 'წმინდა ₾', 'გადახდ. ₾', 'ვალი ₾',
            'სტატუსი', 'ორდ. #', 'შექ. თარ.', 'გადახდ. თარ.',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 12,
            'C' => 18,
            'D' => 26,
            'E' => 8,
            'F' => 20,
            'G' => 14,
            'H' => 14,
            'I' => 10,  // ფასი ₾
            'J' => 8,   // ფასდ. ₾
            'K' => 9,   // ფასი $
            'L' => 9,   // საკ. ₾
            'M' => 10,  // წმინდა ₾
            'N' => 10,  // გადახდ. ₾
            'O' => 8,   // ვალი ₾
            'P' => 14,  // სტატუსი
            'Q' => 14,  // ორდ. #
            'R' => 12,  // შექ. თარ.
            'S' => 14,  // გადახდ. თარ.
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF8E44AD']],
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
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle('A1:' . $lastCol . $lastRow)->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('FFE0E0E0');

                foreach ($this->images as $rowNum => $gd) {
                    $drawing = new MemoryDrawing();
                    $drawing->setImageResource($gd);
                    $drawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
                    $drawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);
                    $drawing->setCoordinates('B' . $rowNum);
                    $drawing->setOffsetX(3)->setOffsetY(3);
                    $drawing->setWidth(72)->setHeight(72);
                    $drawing->setWorksheet($sheet);
                    $sheet->getRowDimension($rowNum)->setRowHeight(78);
                }

                for ($r = 2; $r <= $lastRow; $r++) {
                    if (!isset($this->images[$r])) {
                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }
                }

                foreach ($this->debts as $rowNum => $_) {
                    $sheet->getStyle('O' . $rowNum)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEE2E2']],
                    ]);
                }

                // totals row styling
                if ($this->totalRow > 0) {
                    $sheet->getStyle('A' . $this->totalRow . ':' . $lastCol . $this->totalRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF2C3E50']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F4FF']],
                    ]);
                    $sheet->getRowDimension($this->totalRow)->setRowHeight(22);
                }

                $sheet->freezePane('A2');
            },
        ];
    }

    private function loadImageResource(?\App\Models\Product $product): mixed
    {
        if (!$product || !$product->image) return null;
        try {
            if (str_starts_with($product->image, '/')) {
                $path = public_path(ltrim($product->image, '/'));
                if (!file_exists($path)) return null;
                $contents = file_get_contents($path);
            } else {
                $disk = config('filesystems.default', 'local');
                if (Storage::disk($disk)->exists($product->image)) {
                    $contents = Storage::disk($disk)->get($product->image);
                } else {
                    $url = $product->image_url;
                    if (!$url) return null;
                    $contents = @file_get_contents($url, false, stream_context_create([
                        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
                        'http' => ['timeout' => 5],
                    ]));
                }
            }
            if (!$contents) return null;
            $src = @imagecreatefromstring($contents);
            if ($src === false) return null;
            $thumb = imagecreatetruecolor(80, 80);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, 80, 80, imagesx($src), imagesy($src));
            imagedestroy($src);
            return $thumb;
        } catch (\Throwable) {
            return null;
        }
    }
}
