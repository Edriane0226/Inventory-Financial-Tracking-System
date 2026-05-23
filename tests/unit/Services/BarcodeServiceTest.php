<?php

namespace Tests\Unit\Services;

use App\Services\BarcodeService;
use CodeIgniter\Test\CIUnitTestCase;

final class BarcodeServiceTest extends CIUnitTestCase
{
    public function testNormalizeProductName(): void
    {
        $service = new BarcodeService();
        $this->assertSame('deluxe milk 1l', $service->normalizeProductName('  Deluxe   MILK 1L '));
    }

    public function testComputeCheckDigit(): void
    {
        $service = new BarcodeService();
        $this->assertSame('5', $service->computeCheckDigit('200000012345'));
    }

    public function testBuildEan13FromBody(): void
    {
        $service = new BarcodeService();
        $this->assertSame('2000000123455', $service->buildEan13('200000012345'));
    }
}

