<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailPdfExportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAuditTable();
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('audit_trails')->insert([
            'actor_user_id' => 1,
            'actor_role' => 'Owner',
            'module' => 'financial',
            'action' => 'create',
            'entity_type' => 'bill',
            'entity_id' => '1',
            'summary' => 'Bill created',
            'before_data' => null,
            'after_data' => '{"id":1}',
            'request_method' => 'POST',
            'request_path' => 'financial/bills/create',
            'ip_address' => null,
            'created_at' => '2026-04-11 01:00:00',
        ]);
    }

    public function testPdfExportReturnsPdfDownload(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('management/audit-trail/export-pdf?module=financial');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/pdf');
        $result->assertHeaderContains('Content-Disposition', 'audit-trail-');
    }

    private function ensureAuditTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('audit_trails')) {
            return;
        }

        $forge = Database::forge();
        $forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
            'module' => ['type' => 'VARCHAR', 'constraint' => 40],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
            'before_data' => ['type' => 'TEXT', 'null' => true],
            'after_data' => ['type' => 'TEXT', 'null' => true],
            'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('audit_trails', true);
    }
}
