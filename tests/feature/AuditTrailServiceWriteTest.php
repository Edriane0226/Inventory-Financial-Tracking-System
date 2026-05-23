<?php

use App\Services\AuditTrailService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class AuditTrailServiceWriteTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureUsersTable();
        $this->ensureAuditTable();
        db_connect()->table('audit_trails')->truncate();
    }

    public function testServiceWritesCreateEntryWithAfterJson(): void
    {
        $service = new AuditTrailService();
        $insertId = $service->log([
            'actor_user_id' => 1,
            'actor_role' => 'Owner',
            'module' => 'financial',
            'action' => 'create',
            'entity_type' => 'bill',
            'entity_id' => '10',
            'summary' => 'Created bill Electric Bill',
            'before_data' => null,
            'after_data' => ['id' => 10, 'bill_name' => 'Electric Bill'],
            'request_method' => 'POST',
            'request_path' => 'financial/bills/create',
        ]);

        $this->assertIsInt($insertId);
        $row = db_connect()->table('audit_trails')->where('id', $insertId)->get()->getRowArray();
        $this->assertSame('financial', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
        $this->assertSame('{"id":10,"bill_name":"Electric Bill"}', (string) ($row['after_data'] ?? ''));
    }

    private function ensureUsersTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('users')) {
            return;
        }

        $forge = Database::forge();
        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 30],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 30],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('users', true);
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
