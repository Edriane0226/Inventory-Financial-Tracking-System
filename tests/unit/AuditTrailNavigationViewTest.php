<?php

use CodeIgniter\Test\CIUnitTestCase;

final class AuditTrailNavigationViewTest extends CIUnitTestCase
{
    public function testManagementMenuContainsOwnerOnlyAuditTrail(): void
    {
        $menu = file_get_contents(APPPATH . 'Views/Reusables/menu.php');

        $this->assertNotFalse($menu);
        $this->assertStringContainsString("'Management'", (string) $menu);
        $this->assertStringContainsString("'Audit Trail'", (string) $menu);
        $this->assertStringContainsString("'management/audit-trail'", (string) $menu);
    }
}
