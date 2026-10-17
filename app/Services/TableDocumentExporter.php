<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use ZipArchive;

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
            'excel' => $this->excel("{$base}-{$stamp}.xlsx", $headings, $rows),
            'word' => $this->word("{$base}-{$stamp}.docx", $safeTitle, $headings, $rows),
            default => $this->pdf("{$base}-{$stamp}.pdf", $safeTitle, $headings, $rows),
        };
    }

    private function excel(string $filename, array $headings, array $rows)
    {
        $path = $this->tempPath('xlsx');
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headings));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $this->file($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function word(string $filename, string $title, array $headings, array $rows)
    {
        $path = $this->tempPath('docx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('word/document.xml', $this->wordDocumentXml($title, $headings, $rows));
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelsXml());
        $zip->close();

        return $this->file($path, $filename, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    private function pdf(string $filename, string $title, array $headings, array $rows)
    {
        $binary = Pdf::loadView('exports.table-document', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now()->format('d M Y H:i'),
        ])->setPaper('a4', 'landscape')->output();

        $path = $this->tempPath('pdf');
        file_put_contents($path, $binary);

        return $this->file($path, $filename, 'application/pdf');
    }

    private function file(string $path, string $filename, string $contentType)
    {
        return response()->download($path, $filename, [
            'Content-Type' => $contentType,
        ])->deleteFileAfterSend(true);
    }

    private function tempPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'quisat-export-');
        $named = $path.'.'.$extension;
        rename($path, $named);

        return $named;
    }

    private function plain(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => $this->plain($item), $value));
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        } elseif (is_object($value)) {
            return '';
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?? '');

        return mb_check_encoding($text, 'UTF-8') ? $text : '';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function documentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
    }

    private function wordDocumentXml(string $title, array $headings, array $rows): string
    {
        $cells = function (array $values, bool $header) {
            $xml = '';
            foreach ($values as $value) {
                $weight = $header ? '<w:b/>' : '';
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="2400" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr>'.$weight.'</w:rPr><w:t xml:space="preserve">'.$this->xml((string) $value).'</w:t></w:r></w:p></w:tc>';
            }

            return $xml;
        };

        $body = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>'.$this->xml($title).'</w:t></w:r></w:p>';
        $body .= '<w:tbl><w:tr>'.$cells($headings, true).'</w:tr>';
        if ($rows === []) {
            $body .= '<w:tr>'.$cells(['No records match this view.'], false).'</w:tr>';
        }
        foreach ($rows as $row) {
            $body .= '<w:tr>'.$cells($row, false).'</w:tr>';
        }
        $body .= '</w:tbl>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$body
            .'<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/></w:sectPr>'
            .'</w:body></w:document>';
    }
}
