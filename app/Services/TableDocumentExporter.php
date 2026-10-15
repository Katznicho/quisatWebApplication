<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TableDocumentExporter
{
    public function download(string $format, string $title, array $headings, array $rows)
    {
        $safeTitle = trim($title) !== '' ? $title : 'List';
        $stamp = now()->format('Y-m-d');
        $base = Str::slug($safeTitle) ?: 'list';
        $headings = array_map(fn ($heading) => $this->plain($heading), $headings);
        $rows = array_map(function ($row) {
            return array_map(fn ($cell) => $this->plain($cell), array_values($row));
        }, $rows);

        return match ($format) {
            'excel' => $this->excel("{$base}-{$stamp}.xls", $safeTitle, $headings, $rows),
            'word' => $this->word("{$base}-{$stamp}.doc", $safeTitle, $headings, $rows),
            default => $this->pdf("{$base}-{$stamp}.pdf", $safeTitle, $headings, $rows),
        };
    }

    private function excel(string $filename, string $title, array $headings, array $rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Worksheet ss:Name="'.e(Str::limit($title, 31, '')).'"><Table>';
        $xml .= $this->excelRow($headings);
        foreach ($rows as $row) {
            $xml .= $this->excelRow($row);
        }
        $xml .= '</Table></Worksheet></Workbook>';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function word(string $filename, string $title, array $headings, array $rows)
    {
        $html = view('exports.table-document', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now()->format('d M Y H:i'),
            'forWord' => true,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function pdf(string $filename, string $title, array $headings, array $rows)
    {
        $pdf = Pdf::loadView('exports.table-document', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now()->format('d M Y H:i'),
            'forWord' => false,
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function excelRow(array $cells): string
    {
        $row = '<Row>';
        foreach ($cells as $cell) {
            $row .= '<Cell><Data ss:Type="String">'.e((string) $cell).'</Data></Cell>';
        }

        return $row.'</Row>';
    }

    private function plain(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => $this->plain($item), $value));
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?? '');
    }
}
