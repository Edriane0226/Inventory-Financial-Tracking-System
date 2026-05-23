<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialTotalsSourceRulesTest extends CIUnitTestCase
{
    public function testExpenseFormulaDocumentsPaidBillsPlusProductExpenses(): void
    {
        $controllerFile = file_get_contents(APPPATH . 'Controllers/FinancialAnalyticsController.php');
        $this->assertNotFalse($controllerFile);
        $this->assertStringContainsString('sumPaidBills', (string) $controllerFile);
        $this->assertStringContainsString('sumProductExpenses', (string) $controllerFile);
    }
}
