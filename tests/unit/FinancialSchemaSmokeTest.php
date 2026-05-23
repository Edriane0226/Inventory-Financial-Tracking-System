<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialSchemaSmokeTest extends CIUnitTestCase
{
    public function testFinancialSchemaArtifactsExist(): void
    {
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-050000_AddCreatedAtToReceiptsTable.php');
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-050100_CreateExpenseCategoriesTable.php');
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-050200_CreateExpensesTable.php');
        $this->assertTrue(class_exists(\App\Models\ExpenseCategory::class));
        $this->assertTrue(class_exists(\App\Models\Expense::class));
    }
}

