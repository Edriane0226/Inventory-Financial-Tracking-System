<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialNavigationViewTest extends CIUnitTestCase
{
    public function testMenuAndDashboardLinkToFinancialAnalytics(): void
    {
        $menu = file_get_contents(APPPATH . 'Views/Reusables/menu.php');
        $dashboard = file_get_contents(APPPATH . 'Views/auth/dashboard.php');

        $this->assertNotFalse($menu);
        $this->assertNotFalse($dashboard);
        $this->assertStringContainsString("'Financial Dashboard'", (string) $menu);
        $this->assertStringContainsString("'financial/expenses'", (string) $menu);
        $this->assertStringContainsString("'financial/export-csv?year=' . date('Y') . '&month=' . date('n')", (string) $menu);
        $this->assertStringContainsString("base_url('financial')", (string) $dashboard);
    }
}

