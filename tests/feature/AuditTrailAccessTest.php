<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class AuditTrailAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('management/audit-trail')->assertRedirectTo('/login');
    }

    public function testEmployeeIsRedirectedToDashboard(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Employee',
            'user_id' => 2,
        ])->get('management/audit-trail')->assertRedirectTo('/dashboard');
    }
}
