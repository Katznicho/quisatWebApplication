<?php

namespace App\Support;

use App\Models\PocketPayCardNumber;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Printable QR codes for Pocket Pay cards. The QR holds only the raw card number, so a
 * scan gives the attendant app exactly what an attendant would type.
 */
class PocketPayCardQr
{
    protected const SIZE = 300;

    protected const LABEL_HEIGHT = 44;

    /**
     * SVG with the QR code and the formatted number printed underneath.
     */
    public static function svg(string $number): string
    {
        $qr = (new Writer(new ImageRenderer(new RendererStyle(self::SIZE, 4), new SvgImageBackEnd)))->writeString($number);
        $qr = preg_replace('/^<\?xml[^>]*>\s*/', '', $qr);

        $size = self::SIZE;
        $height = $size + self::LABEL_HEIGHT;
        $label = PocketPayCardNumber::format($number);
        // Fixed width and start so every renderer (browsers, print RIPs) centres the label the same way.
        $labelWidth = min($size - 20, (int) round(mb_strlen($label) * 14.4));
        $labelX = intdiv($size - $labelWidth, 2);
        $label = e($label);

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$height}" viewBox="0 0 {$size} {$height}">
<rect width="100%" height="100%" fill="#ffffff"/>
{$qr}
<text x="{$labelX}" y="{$size}" dy="26" textLength="{$labelWidth}" lengthAdjust="spacingAndGlyphs" font-family="Courier New, monospace" font-size="24" font-weight="bold" fill="#000000">{$label}</text>
</svg>
SVG;
    }

    public static function download(PocketPayCardNumber $cardNumber): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print (self::svg($cardNumber->number)),
            "card-{$cardNumber->number}.svg",
            ['Content-Type' => 'image/svg+xml'],
        );
    }

    /**
     * ZIP of one SVG per card number.
     *
     * @param  Collection<int, PocketPayCardNumber>  $cardNumbers
     */
    public static function downloadZip(Collection $cardNumbers, string $name = 'card-qr-codes'): StreamedResponse
    {
        return response()->streamDownload(function () use ($cardNumbers) {
            $path = tempnam(sys_get_temp_dir(), 'qr');
            $zip = new \ZipArchive;
            $zip->open($path, \ZipArchive::OVERWRITE);

            foreach ($cardNumbers as $cardNumber) {
                $zip->addFromString("card-{$cardNumber->number}.svg", self::svg($cardNumber->number));
            }

            $zip->close();
            readfile($path);
            @unlink($path);
        }, $name.'.zip', ['Content-Type' => 'application/zip']);
    }
}
