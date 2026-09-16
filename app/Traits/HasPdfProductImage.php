<?php

namespace App\Traits;

trait HasPdfProductImage
{
    /** dompdf-ისთვის დაქეშილი base64 სურათი (resized, AVIF/WEBP → PNG) */
    private function productImageBase64(?\App\Models\Product $product): ?string
    {
        if (!$product || !$product->image) return null;

        $cacheKey = 'pdf_img_b64_' . $product->id . '_' . substr(md5($product->image), 0, 8);
        $cached   = \Cache::get($cacheKey);
        if ($cached !== null) return $cached ?: null;

        try {
            if (str_starts_with($product->image, '/')) {
                $path = public_path(ltrim($product->image, '/'));
                if (!file_exists($path)) { \Cache::put($cacheKey, '', 86400); return null; }
                $contents = file_get_contents($path);
            } else {
                $url = $product->image_url;
                if (!$url) { \Cache::put($cacheKey, '', 86400); return null; }
                $contents = @file_get_contents($url, false, stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    'http' => ['timeout' => 5],
                ]));
            }
            if (!$contents) return null;

            // dompdf-ი AVIF/WEBP-ს ვერ ახდენს render-ს — GD-ით PNG-ად ვაქცევთ, max 200px
            $img = @imagecreatefromstring($contents);
            if ($img !== false) {
                $origW = imagesx($img);
                $origH = imagesy($img);
                $max   = 200;
                if ($origW > $max || $origH > $max) {
                    $scale   = min($max / $origW, $max / $origH);
                    $newW    = (int)($origW * $scale);
                    $newH    = (int)($origH * $scale);
                    $resized = imagecreatetruecolor($newW, $newH);
                    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                    imagedestroy($img);
                    $img = $resized;
                }
                ob_start();
                imagepng($img, null, 6);
                $png = ob_get_clean();
                imagedestroy($img);
                $result = 'data:image/png;base64,' . base64_encode($png);
                \Cache::put($cacheKey, $result, 86400);
                return $result;
            }

            // GD ვერ ახდენს — original format-ით ვრჩებით (jpeg/png/gif)
            $ext  = strtolower(pathinfo($product->image, PATHINFO_EXTENSION));
            $mime = match($ext) {
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                default => 'image/jpeg',
            };
            $result = 'data:' . $mime . ';base64,' . base64_encode($contents);
            \Cache::put($cacheKey, $result, 86400);
            return $result;
        } catch (\Throwable) {
            return null;
        }
    }
}
