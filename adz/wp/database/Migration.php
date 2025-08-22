<?php

namespace AdzWP;

/**
 * Database Migration Base Class
 * 
 * Provides a foundation for creating database migrations
 */
abstract class Migration
{
    protected $connection;
    protected $prefix;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
        $this->prefix = $this->connection->getPrefix();
    }

    /**
     * Run the migration
     */
    abstract public function up();

    /**
     * Reverse the migration
     */
    abstract public function down();

    /**
     * Create a new table
     */
    protected function createTable($table, callable $callback)
    {
        $schema = new Schema($table, $this->connection);
        $callback($schema);
        $schema->create();
    }

    /**
     * Modify an existing table
     */
    protected function table($table, callable $callback)
    {
        $schema = new Schema($table, $this->connection);
        $callback($schema);
        $schema->alter();
    }

    /**
     * Drop a table
     */
    protected function dropTable($table)
    {
        $tableName = $this->connection->getTable($table);
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        return $this->connection->query($sql);
    }

    /**
     * Check if table exists
     */
    protected function hasTable($table)
    {
        return $this->connection->tableExists($table);
    }

    /**
     * Execute raw SQL
     */
    protected function sql($query)
    {
        return $this->connection->query($query);
    }

    /**
     * Get the migration name from the class name
     */
    public function getName()
    {
        $className = get_class($this);
        return basename(str_replace('\\', '/', $className));
    }
}