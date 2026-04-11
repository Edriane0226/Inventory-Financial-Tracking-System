<?php

namespace App\Services;

use App\Models\ProductBarcode;

final class BarcodeService
{
    private const MAX_ALLOCATE_ATTEMPTS = 10;

    public function normalizeProductName(string $productName): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim(strtolower($productName)));

        return (string) $normalized;
    }

    public function computeCheckDigit(string $body12): string
    {
        $sum = 0;

        foreach (str_split($body12) as $index => $char) {
            $digit = (int) $char;
            $sum += (($index + 1) % 2 === 0) ? ($digit * 3) : $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    public function buildEan13(string $body12): string
    {
        return $body12 . $this->computeCheckDigit($body12);
    }

    public function resolveForProduct(string $productName, int $categoryId, int $unitTypeId): string
    {
        $model = new ProductBarcode();
        $normalized = $this->normalizeProductName($productName);

        $existing = $model->where('normalized_product_name', $normalized)
            ->where('category_id', $categoryId)
            ->where('unit_type_id', $unitTypeId)
            ->first();

        if ($existing) {
            return (string) $existing['barcode'];
        }

        $prefix = getenv('BARCODE_INTERNAL_PREFIX') ?: '2000000';

        for ($attempt = 0; $attempt < self::MAX_ALLOCATE_ATTEMPTS; $attempt++) {
            $body12 = $prefix . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $barcode = $this->buildEan13($body12);

            $inserted = $model->insert([
                'normalized_product_name' => $normalized,
                'category_id' => $categoryId,
                'unit_type_id' => $unitTypeId,
                'barcode' => $barcode,
            ]);

            if ($inserted !== false) {
                return $barcode;
            }

            $existing = $model->where('normalized_product_name', $normalized)
                ->where('category_id', $categoryId)
                ->where('unit_type_id', $unitTypeId)
                ->first();

            if ($existing) {
                return (string) $existing['barcode'];
            }
        }

        throw new \RuntimeException('Could not allocate a unique EAN-13 barcode.');
    }
}

