<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialSchemaArtifactsTest extends CIUnitTestCase
{
    public function testBillsAndProductExpensesArtifactsExist(): void
    {
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-060000_CreateBillsTable.php');
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-060100_CreateProductExpensesTable.php');
        $this->assertTrue(class_exists(\App\Models\Bill::class));
        $this->assertTrue(class_exists(\App\Models\ProductExpense::class));
    }
}
