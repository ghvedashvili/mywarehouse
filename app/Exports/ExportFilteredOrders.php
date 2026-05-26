<?php

namespace App\Exports;

use App\Models\Product_Order;
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

class ExportFilteredOrders implements FromArray, WithHeadings, WithStyles, WithEvents, WithColumnWidths
{
    use Exportable;

    private array $rows       = [];
    private array $images     = []; // excelRow => GD resource
    private array $debts      = []; // excelRow => true
    private array $groupRows  = []; // excelRow => true (belongs to a merged group)

    public function __construct(private array $ids) {}

    public function array(): array
    {
        $orders = Product_Order::withoutGlobalScope('active')
            ->with([
                'product'      => fn($q) => $q->withoutGlobalScope('active'),
                'customer.city',
                'orderStatus',
            ])
            ->whereIn('id', $this->ids)
            ->get()
            ->keyBy('id');

        // children of primary orders in selection
        $primaryIds = $orders->where('is_primary', 1)->pluck('id')->toArray();
        $childrenByParent = collect();
        if (!empty($primaryIds)) {
            $children = Product_Order::withoutGlobalScope('active')
                ->with(['product' => fn($q) => $q->withoutGlobalScope('active')])
                ->whereIn('merged_id', $primaryIds)
                ->where('is_primary', 0)
                ->get();
            $childrenByParent = $children->groupBy('merged_id');
        }

        $excelRow = 2; // row 1 = headings
        $seq      = 0;

        foreach ($this->ids as $id) {
            $order = $orders->get($id);
            if (!$order) continue;

            $customer = $order->customer;
            $cityName = $customer?->city?->name ?? '';
            $address  = $customer?->address ?? '';
            $phone    = $order->order_alt_tel ?: ($customer?->tel ?? '');

            $total = (float)$order->price_georgia - (float)($order->discount ?? 0);
            $paid  = (float)($order->paid_tbc  ?? 0) + (float)($order->paid_bog  ?? 0)
                   + (float)($order->paid_lib  ?? 0) + (float)($order->paid_cash ?? 0);
            $debt  = round(max(0, $total - $paid), 2);

            // For grouped (primary) orders — show all members (primary + children)
            $children  = $order->is_primary
                ? $childrenByParent->get($order->id, collect())->values()
                : collect();
            $members   = collect([$order])->merge($children)->values();
            $isGroup   = $members->count() > 1;

            foreach ($members as $idx => $member) {
                $seq++;
                $isFirst      = $idx === 0;
                $productName  = $member->product?->name ?? '—';
                $productSize  = $member->product_size ?? '';
                $orderNum     = $order->order_number ?? ('S' . $order->id);
                $statusName   = $order->orderStatus?->name ?? '';
                $date         = $order->created_at?->format('d.m.Y') ?? '';
                $comment      = $order->comment ?? '';
                $memberOrig   = (float)$member->price_georgia;
                $memberDisc   = (float)($member->discount ?? 0);
                $memberPaid   = (float)($member->paid_tbc ?? 0) + (float)($member->paid_bog ?? 0)
                              + (float)($member->paid_lib ?? 0) + (float)($member->paid_cash ?? 0);
                $memberDebt   = round(max(0, $memberOrig - $memberDisc - $memberPaid), 2);

                // image resource
                $gd = $this->loadImageResource($member->product);
                if ($gd) {
                    $this->images[$excelRow] = $gd;
                }

                if ($memberDebt > 0) {
                    $this->debts[$excelRow] = true;
                }

                // mark all rows of this group for background coloring
                if ($isGroup) {
                    $this->groupRows[$excelRow] = true;
                }

                $memberPriceUsa   = (float)($member->price_usa ?? 0);
                $courierPrice     = (float)($member->courier_price_tbilisi   ?? 0)
                                  + (float)($member->courier_price_region    ?? 0)
                                  + (float)($member->courier_price_village   ?? 0)
                                  + (float)($member->courier_price_international ?? 0);

                $this->rows[] = [
                    $seq,
                    '',                          // B — image placeholder
                    $productName,
                    $productSize,
                    $customer?->name ?? '—',
                    $phone,
                    $cityName,
                    $address,
                    $memberPriceUsa > 0 ? round($memberPriceUsa, 2) : '',   // I — ფასი $
                    round($memberOrig, 2),                                    // J — ფასი ₾
                    $memberDisc > 0 ? round($memberDisc, 2) : '',            // K — ფასდ. ₾
                    $courierPrice > 0 ? round($courierPrice, 2) : '',        // L — საკ. ₾
                    $paid > 0 ? round($paid, 2) : '',                        // M — გადახდ. ₾
                    $memberDebt > 0 ? $memberDebt : '',                      // N — ვალი ₾
                    $statusName,
                    $orderNum,
                    $date,
                    $comment,
                ];

                $excelRow++;
            }
        }

        return $this->rows;
    }

    public function headings(): array
    {
        return ['#', 'სურათი', 'პროდუქტი', 'ზომა', 'კლიენტი', 'ტელ', 'ქალაქი', 'მისამართი',
                'ფასი $', 'ფასი ₾', 'ფასდ. ₾', 'საკ. ₾', 'გადახდ. ₾', 'ვალი ₾', 'სტატუსი', 'ორდ. #', 'თარიღი', 'კომ.'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 12,
            'C' => 26,
            'D' => 8,
            'E' => 20,
            'F' => 14,
            'G' => 14,
            'H' => 28,
            'I' => 9,   // ფასი $
            'J' => 10,  // ფასი ₾
            'K' => 8,   // ფასდ. ₾
            'L' => 9,   // საკ. ₾
            'M' => 10,  // გადახდ. ₾
            'N' => 8,   // ვალი ₾
            'O' => 14,  // სტატუსი
            'P' => 14,  // ორდ. #
            'Q' => 12,  // თარიღი
            'R' => 20,  // კომ.
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
                $sheet    = $event->sheet->getDelegate();
                $lastRow  = $sheet->getHighestRow();
                $lastCol  = $sheet->getHighestColumn();

                // header row height
                $sheet->getRowDimension(1)->setRowHeight(22);

                // vertical align + wrap all cells
                $sheet->getStyle('A1:' . $lastCol . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // thin borders on all data
                $sheet->getStyle('A1:' . $lastCol . $lastRow)->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('FFE0E0E0');

                // embed images + set row heights
                foreach ($this->images as $rowNum => $gd) {
                    $drawing = new MemoryDrawing();
                    $drawing->setImageResource($gd);
                    $drawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
                    $drawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);
                    $drawing->setCoordinates('B' . $rowNum);
                    $drawing->setOffsetX(3);
                    $drawing->setOffsetY(3);
                    $drawing->setWidth(72);
                    $drawing->setHeight(72);
                    $drawing->setWorksheet($sheet);
                    $sheet->getRowDimension($rowNum)->setRowHeight(78);
                }

                // rows without image — default height
                for ($r = 2; $r <= $lastRow; $r++) {
                    if (!isset($this->images[$r])) {
                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }
                }

                // grouped order rows — light orange background
                foreach ($this->groupRows as $rowNum => $_) {
                    $sheet->getStyle('A' . $rowNum . ':' . $lastCol . $rowNum)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF5F0']],
                    ]);
                }

                // debt cells — red background + bold
                foreach ($this->debts as $rowNum => $_) {
                    $sheet->getStyle('N' . $rowNum)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEE2E2']],
                    ]);
                }

                // freeze header row
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
            // Resize to thumbnail immediately to keep memory usage low
            $thumb = imagecreatetruecolor(80, 80);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, 80, 80, imagesx($src), imagesy($src));
            imagedestroy($src);
            return $thumb;
        } catch (\Throwable) {
            return null;
        }
    }
}
