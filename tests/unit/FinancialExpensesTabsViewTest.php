<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialExpensesTabsViewTest extends CIUnitTestCase
{
    public function testExpensesViewContainsBillsAndProductExpenseTabs(): void
    {
        $html = view('financial/expenses', [
            'bills' => [],
            'productExpenses' => [],
            'filters' => [],
            'billFilters' => [],
        ]);

        $this->assertStringContainsString('Bills', $html);
        $this->assertStringContainsString('Product Expenses', $html);
    }
}
