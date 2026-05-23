<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailFinancialAndUserLoggingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
        $this->seedOwnerUser();
    }

    public function testCreateBillWritesAuditTrail(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('financial/bills/create', [
            'bill_name' => 'Water',
            'amount' => '800.00',
            'bill_date' => '2026-04-11',
            'due_date' => '2026-04-20',
            'status' => 'unpaid',
            'notes' => 'May bill',
        ])->assertRedirectTo('/financial/expenses');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('financial', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
        $this->assertSame('bill', (string) ($row['entity_type'] ?? ''));
    }

    public function testRegisterUserWritesAuditTrail(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'role_id' => 1,
        ])->assertRedirectTo('/register');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('user_management', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
        $this->assertSame('user', (string) ($row['entity_type'] ?? ''));
    }

    private function ensureTables(): void
    {
        $db = db_connect();
        $forge = Database::forge();

        if (!$db->tableExists('roles')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'role_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('roles', true);
        }

        if (!$db->tableExists('users')) {
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

        if (!$db->tableExists('bills')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'bill_name' => ['type' => 'VARCHAR', 'constraint' => 150],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'bill_date' => ['type' => 'DATE'],
                'due_date' => ['type' => 'DATE'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'unpaid'],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('bills', true);
        }

        if (!$db->tableExists('audit_trails')) {
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

    private function resetTables(): void
    {
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('bills')->truncate();
        $db->table('users')->truncate();
        $db->table('roles')->truncate();
    }

    private function seedOwnerUser(): void
    {
        $db = db_connect();
        $db->table('roles')->insert(['id' => 1, 'role_name' => 'Owner']);
        $db->table('users')->insert([
            'id' => 1,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'email' => 'owner@example.com',
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
            'role_id' => 1,
        ]);
    }
}
