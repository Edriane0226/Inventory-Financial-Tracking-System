<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailFilterAndCsvExportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAuditTable();
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('audit_trails')->insertBatch([
            [
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
            ],
            [
                'actor_user_id' => 2,
                'actor_role' => 'Employee',
                'module' => 'stock_in',
                'action' => 'update',
                'entity_type' => 'stock_in',
                'entity_id' => '5',
                'summary' => 'Stock merged',
                'before_data' => '{"quantity":2}',
                'after_data' => '{"quantity":5}',
                'request_method' => 'POST',
                'request_path' => 'stockin',
                'ip_address' => null,
                'created_at' => '2026-04-11 02:00:00',
            ],
        ]);
    }

    public function testOwnerCanFilterByModule(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('management/audit-trail?module=financial');

        $result->assertStatus(200);
        $result->assertSee('Bill created');
        $result->assertDontSee('Stock merged');
    }

    public function testCsvExportUsesSameFilterSet(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('management/audit-trail/export-csv?module=financial');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $result->assertHeaderContains('Content-Disposition', 'audit-trail-');
    }

    public function testCsvExportWithNoRowsStillReturnsHeaderRow(): void
    {
        db_connect()->table('audit_trails')->truncate();
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('management/audit-trail/export-csv?module=inventory');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
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
