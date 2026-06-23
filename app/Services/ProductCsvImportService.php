<?php

namespace App\Services;

use App\Models\Product;
use App\Support\StationeryHub;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductCsvImportService
{
    public const TEMPLATE_HEADERS = [
        'item_name',
        'grade',
        'price',
        'stock',
        'image',
        'delivery_days',
        'quality',
        'quantity',
    ];

    /** @var array<string, string> */
    protected array $headerMap = [
        'item_name' => 'name',
        'item name' => 'name',
        'name' => 'name',
        'product_name' => 'name',
        'product name' => 'name',
        'grade' => 'grade',
        'grade_level' => 'grade',
        'grade level' => 'grade',
        'price' => 'price',
        'stock' => 'stock_quantity',
        'quantity' => 'stock_quantity',
        'qty' => 'stock_quantity',
        'image' => 'image',
        'image_url' => 'image',
        'image url' => 'image',
        'photo' => 'image',
        'delivery_days' => 'delivery_days',
        'delivery days' => 'delivery_days',
        'delivery' => 'delivery_days',
        'quality' => 'quality_grade',
        'quality_grade' => 'quality_grade',
        'quality grade' => 'quality_grade',
    ];

    public function templateCsv(string $hub): string
    {
        $lines = [implode(',', self::TEMPLATE_HEADERS)];

        if ($hub === StationeryHub::HUB) {
            $lines[] = 'Blue Exercise Book,P3,2500,120,https://example.com/images/exercise-book.jpg,2,standard,120';
            $lines[] = 'Scientific Calculator,S4,45000,35,https://example.com/images/calculator.jpg,3,premium,35';
        } else {
            $lines[] = 'Kids School Bag,P2,85000,25,https://example.com/images/school-bag.jpg,3,standard,25';
            $lines[] = 'Water Bottle Set,All,15000,80,,2,economy,80';
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @return array{success: int, errors: array<int, string>, skipped: int}
     */
    public function import(string $filePath, int $businessId, string $hub): array
    {
        $rows = $this->readCsv($filePath);
        if ($rows === []) {
            return ['success' => 0, 'errors' => ['The CSV file is empty or has no data rows.'], 'skipped' => 0];
        }

        $headers = array_shift($rows);
        $columnMap = $this->buildColumnMap($headers);

        if (! in_array('name', $columnMap, true)) {
            return ['success' => 0, 'errors' => ['Missing required column: item_name (or name).'], 'skipped' => 0];
        }

        $success = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->rowIsEmpty($row)) {
                $skipped++;

                continue;
            }

            $data = $this->mapRow($row, $columnMap);
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'delivery_days' => 'nullable|integer|min:1|max:30',
                'grade' => 'nullable|string|max:64',
                'quality_grade' => 'nullable|string|max:32',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $quality = $this->normalizeQuality($data['quality_grade'] ?? null);
            if (($data['quality_grade'] ?? '') !== '' && $quality === null) {
                $errors[] = "Row {$rowNumber}: Invalid quality. Use standard, premium, or economy.";

                continue;
            }

            $imagePath = null;
            $imageWarning = null;
            if (! empty($data['image'])) {
                $imagePath = $this->storeImageFromCell($data['image']);
                if ($imagePath === null) {
                    $imageWarning = 'image could not be downloaded';
                }
            }

            try {
                Product::create([
                    'business_id' => $businessId,
                    'hub' => $hub,
                    'name' => $data['name'],
                    'grade' => $data['grade'] ?? null,
                    'price' => $data['price'],
                    'stock_quantity' => (int) $data['stock_quantity'],
                    'delivery_days' => (int) ($data['delivery_days'] ?? 3),
                    'quality_grade' => $quality,
                    'image_path' => $imagePath,
                    'is_available' => true,
                    'status' => 'active',
                    'low_stock_threshold' => 15,
                ]);

                $success++;
                if ($imageWarning) {
                    $errors[] = "Row {$rowNumber}: Product created but {$imageWarning}.";
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNumber}: ".$e->getMessage();
            }
        }

        return compact('success', 'errors', 'skipped');
    }

    /**
     * @return list<array<int, string|null>>
     */
    protected function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($rows === [] && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string|null>  $headers
     * @return array<int, string>
     */
    protected function buildColumnMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string) $header));
            $key = str_replace(['-', '_'], ' ', $key);
            $key = preg_replace('/\s+/', ' ', $key) ?? $key;

            if (isset($this->headerMap[$key])) {
                $map[$index] = $this->headerMap[$key];
            }
        }

        return $map;
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<int, string>  $columnMap
     * @return array<string, mixed>
     */
    protected function mapRow(array $row, array $columnMap): array
    {
        $data = [
            'name' => null,
            'grade' => null,
            'price' => null,
            'stock_quantity' => null,
            'image' => null,
            'delivery_days' => null,
            'quality_grade' => null,
        ];

        foreach ($columnMap as $index => $field) {
            $value = isset($row[$index]) ? trim((string) $row[$index]) : '';
            if ($value === '') {
                continue;
            }

            if ($field === 'stock_quantity' && $data['stock_quantity'] !== null) {
                continue;
            }

            $data[$field] = $value;
        }

        if ($data['stock_quantity'] !== null) {
            $data['stock_quantity'] = (int) preg_replace('/\D+/', '', (string) $data['stock_quantity']);
        }

        if ($data['price'] !== null) {
            $data['price'] = (float) str_replace([',', ' '], '', (string) $data['price']);
        }

        if ($data['delivery_days'] !== null) {
            $data['delivery_days'] = (int) preg_replace('/\D+/', '', (string) $data['delivery_days']);
        }

        return $data;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeQuality(?string $quality): ?string
    {
        if ($quality === null || trim($quality) === '') {
            return null;
        }

        $normalized = strtolower(trim($quality));
        $options = StationeryHub::qualityOptions();
        $aliases = [
            'std' => 'standard',
            'prem' => 'premium',
            'eco' => 'economy',
        ];

        $normalized = $aliases[$normalized] ?? $normalized;

        foreach ($options as $key => $label) {
            if ($normalized === $key || $normalized === strtolower($label)) {
                return $key;
            }
        }

        return null;
    }

    protected function storeImageFromCell(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(20)->get($value);
                if (! $response->successful()) {
                    return null;
                }

                $pathInfo = pathinfo(parse_url($value, PHP_URL_PATH) ?? '');
                $extension = strtolower((string) ($pathInfo['extension'] ?? 'jpg'));
                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $extension = 'jpg';
                }

                $path = 'products/'.Str::uuid().'.'.$extension;
                Storage::disk('public')->put($path, $response->body());

                return $path;
            } catch (\Throwable) {
                return null;
            }
        }

        if (Str::startsWith($value, 'products/')) {
            return $value;
        }

        return null;
    }
}
