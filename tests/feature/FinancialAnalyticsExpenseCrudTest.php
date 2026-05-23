<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class FinancialAnalyticsExpenseCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
        $this->seedRows();
    }

    public function testOwnerCanCreateExpense(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('financial/expenses/create', [
            'category_id' => 1,
            'amount' => '150.75',
            'note' => 'Fuel',
            'expense_date' => '2026-04-11',
        ]);

        $result->assertRedirectTo('/financial/expenses');
    }

    public function testExpenseHistoryFiltersByCategoryAndKeyword(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial/expenses?category_id=1&keyword=fuel');

        $result->assertStatus(200);
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

        if (!$db->tableExists('expense_categories')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 120],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('expense_categories', true);
        }

        if (!$db->tableExists('expenses')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'expense_date' => ['type' => 'DATETIME'],
                'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('expenses', true);
        }
    }

    private function resetTables(): void
    {
        $db = db_connect();
        $db->table('expenses')->truncate();
        $db->table('expense_categories')->truncate();
        $db->table('users')->truncate();
        $db->table('roles')->truncate();
    }

    private function seedRows(): void
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
        $db->table('expense_categories')->insert([
            'id' => 1,
            'name' => 'Fuel',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

