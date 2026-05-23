<?php

use CodeIgniter\Test\CIUnitTestCase;

final class StockInViewBarcodeTest extends CIUnitTestCase
{
    public function testViewDoesNotContainClientBarcodeGenerator(): void
    {
        $html = view('Stock_in/index', [
            'stockIns' => [],
            'capitals' => [],
            'categories' => [],
            'productBatches' => [],
            'unitTypes' => [],
            'salesPrices' => [],
        ]);

        $this->assertStringNotContainsString('const generateBarcode = () => {', $html);
        $this->assertStringContainsString('id="barcode"', $html);
        $this->assertStringContainsString('Generated on save using server-side EAN-13', $html);
    }
}

