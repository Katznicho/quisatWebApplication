<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use SoapClient;
use stdClass;
use Throwable;

class CustomerSoapClient
{
    public function client(): SoapClient
    {
        $url = config('app.webService');
        $username = config('app.soapUsername');
        $password = config('app.soapPassword');

        if (! $url || ! $username || ! $password) {
            throw new \RuntimeException('SOAP service is not configured.');
        }

        return new SoapClient($url, [
            'login' => $username,
            'password' => $password,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'exceptions' => true,
        ]);
    }

    public function call(string $method, array $params = []): mixed
    {
        $payload = new stdClass();

        foreach ($params as $key => $value) {
            $payload->{$key} = $value;
        }

        Log::info("SOAP {$method} request", [
            'params' => $params,
        ]);

        $result = $this->client()->{$method}($payload);
        $returnValue = $result->return_value ?? null;

        Log::info("SOAP {$method} response", [
            'type' => gettype($returnValue),
            'preview' => is_string($returnValue) ? substr($returnValue, 0, 200) : $returnValue,
        ]);

        return $returnValue;
    }

    public function json(string $method, array $params = []): array
    {
        try {
            $raw = $this->call($method, $params);

            if ($raw === null || $raw === '' || $raw === false) {
                return [];
            }

            if (is_array($raw)) {
                return $this->wrapList($raw);
            }

            $decoded = json_decode((string) $raw, true);

            if (! is_array($decoded)) {
                return [];
            }

            return $this->wrapList($decoded);
        } catch (Throwable $e) {
            Log::error("SOAP {$method} failed", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function accountGraph(string $customerNo, string $accountNo): array
    {
        $data = $this->json('GetAccountGraphData', [
            'customerNo' => $customerNo,
            'accountNo' => $accountNo,
        ]);

        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        $value = self::pickFloat($data, [
            'currentUsage', 'CurrentUsage', 'current_usage',
            'value', 'Value', 'used', 'Used', 'consumed', 'Consumed',
            'currentValue', 'CurrentValue', 'amount', 'Amount',
            'Quantity_Consumed', 'usage', 'Usage', 'balanceUsed',
        ]);
        $max = self::pickFloat($data, [
            'utlizationLimit', 'utilizationLimit', 'UtilizationLimit',
            'utilisationLimit', 'UtlizationLimit',
            'max', 'Max', 'limit', 'Limit', 'ceiling', 'Ceiling',
            'maxValue', 'MaxValue', 'Usage_Ceiling', 'target', 'Target',
            'limitAmount', 'LimitAmount',
        ]);
        $remaining = self::pickFloat($data, [
            'usageBalance', 'UsageBalance', 'usage_balance',
            'remaining', 'Remaining', 'balance', 'Balance',
        ]);
        $percent = self::pickFloat($data, [
            'percent', 'Percent', 'percentage', 'Percentage', 'usagePercent',
        ]);

        if ($max > 0 && $percent <= 0) {
            $percent = ($value / $max) * 100;
        }

        if ($percent > 0 && $max <= 0) {
            $max = 100;
            $value = $percent;
        }

        if ($remaining <= 0 && $max > 0) {
            $remaining = max(0, $max - $value);
        }

        $percent = max(0, min(100, round($percent, 1)));

        return [
            'value' => $value,
            'max' => $max,
            'remaining' => $remaining,
            'percent' => $percent,
            'label' => self::pick($data, ['label', 'Label', 'title', 'Title', 'description', 'Description']) ?: 'Account usage',
            'available' => $max > 0 || $value > 0 || $percent > 0,
        ];
    }

    public static function pick(array $item, array $keys, mixed $default = ''): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                return $item[$key];
            }
        }

        $normalized = [];
        foreach ($item as $key => $value) {
            $normalized[strtolower(str_replace(['_', '-', ' '], '', (string) $key))] = $value;
        }

        foreach ($keys as $key) {
            $lookup = strtolower(str_replace(['_', '-', ' '], '', (string) $key));
            if (array_key_exists($lookup, $normalized) && $normalized[$lookup] !== null && $normalized[$lookup] !== '') {
                return $normalized[$lookup];
            }
        }

        return $default;
    }

    public static function pdfFromReturn(mixed $returnValue): ?string
    {
        if ($returnValue === null || $returnValue === false || $returnValue === '') {
            return null;
        }

        if (is_array($returnValue)) {
            $returnValue = $returnValue['pdf']
                ?? $returnValue['content']
                ?? $returnValue['document']
                ?? $returnValue['file']
                ?? $returnValue['base64']
                ?? json_encode($returnValue);
        }

        $raw = trim((string) $returnValue);

        if ($raw === '' || $raw === '{}' || strtolower($raw) === 'null' || strtolower($raw) === 'false') {
            return null;
        }

        if (str_starts_with($raw, '%PDF')) {
            return $raw;
        }

        if (preg_match('/^data:application\/pdf;base64,/i', $raw)) {
            $raw = preg_replace('/^data:application\/pdf;base64,/i', '', $raw) ?? $raw;
        }

        $decoded = base64_decode($raw, true);
        if ($decoded !== false && str_starts_with($decoded, '%PDF')) {
            return $decoded;
        }

        $maybeJson = json_decode($raw, true);
        if (is_array($maybeJson)) {
            $base64 = $maybeJson['pdf'] ?? $maybeJson['content'] ?? $maybeJson['document'] ?? $maybeJson['file'] ?? $maybeJson['base64'] ?? null;
            if (is_string($base64) && $base64 !== '') {
                if (str_starts_with($base64, '%PDF')) {
                    return $base64;
                }

                $fromJson = base64_decode($base64, true);
                if ($fromJson !== false && str_starts_with($fromJson, '%PDF')) {
                    return $fromJson;
                }
            }
        }

        return null;
    }

    public static function pickFloat(array $item, array $keys): float
    {
        $value = self::pick($item, $keys, 0);

        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function wrapList(array $data): array
    {
        if ($data === []) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        return [$data];
    }
}
