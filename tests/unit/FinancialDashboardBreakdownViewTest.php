<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialDashboardBreakdownViewTest extends CIUnitTestCase
{
    public function testDashboardViewContainsBreakdownTogglesForAllPeriods(): void
    {
        $html = view('financial/dashboard', [
            'metrics' => [
                'daily' => ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0],
                'weekly' => ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0],
                'monthly' => ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0],
            ],
        ]);

        $this->assertStringContainsString('data-period="daily"', $html);
        $this->assertStringContainsString('data-period="weekly"', $html);
        $this->assertStringContainsString('data-period="monthly"', $html);
        $this->assertStringContainsString('financial-breakdown-panel-daily', $html);
        $this->assertStringContainsString('financial-breakdown-panel-weekly', $html);
        $this->assertStringContainsString('financial-breakdown-panel-monthly', $html);
        $this->assertStringContainsString('/financial/breakdown?period=', $html);
    }
}
