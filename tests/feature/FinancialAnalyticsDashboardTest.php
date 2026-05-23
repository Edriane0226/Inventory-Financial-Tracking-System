<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialAnalyticsDashboardTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testDashboardShowsRevenueExpenseNetIncomeAndMargin(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial');

        $result->assertStatus(200);
        $result->assertSee('Financial Analytics');
        $result->assertSee('Show source rows');
    }

    public function testMonthlyCsvExportReturnsCsv(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial/export-csv?year=2026&month=4');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}

