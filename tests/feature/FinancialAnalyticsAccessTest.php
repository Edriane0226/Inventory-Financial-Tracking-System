<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialAnalyticsAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('financial')->assertRedirectTo('/login');
    }

    public function testEmployeeIsRedirectedToDashboard(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Employee',
            'user_id' => 1,
        ])->get('financial')->assertRedirectTo('/dashboard');
    }
}

