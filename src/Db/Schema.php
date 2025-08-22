<?php

namespace AdzWP\Db;

/**
 * Database Schema Builder
 * 
 * Provides a fluent interface for creating and modifying database tables
 */
class Schema
{
    protected $connection;
    protected $table;
    protected $columns = [];
    protected $indexes = [];
    protected $foreignKeys = [];
    protected $dropColumns = [];
    protected $isCreating = false;

    public function __construct($table, Connection $connection)
    {
        $this->table = $connection->getTable($table);
        $this->connection = $connection;
    }

    /**
     * Add an auto-incrementing ID column
     */
    public function id($name = 'id')
    {
        return $this->bigInteger($name)->unsigned()->autoIncrement()->primary();
    }

    /**
     * Add a string column
     */
    public function string($name, $length = 255)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'VARCHAR',
            'length' => $length,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a text column
     */
    public function text($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'TEXT',
            'length' => null,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a long text column
     */
    public function longText($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'LONGTEXT',
            'length' => null,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add an integer column
     */
    public function integer($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'INT',
            'length' => 11,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a big integer column
     */
    public function bigInteger($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'BIGINT',
            'length' => 20,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a decimal column
     */
    public function decimal($name, $precision = 8, $scale = 2)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DECIMAL',
            'length' => "{$precision},{$scale}",
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a boolean column
     */
    public function boolean($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'TINYINT',
            'length' => 1,
            'nullable' => false,
            'default' => 0,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a datetime column
     */
    public function datetime($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DATETIME',
            'length' => null,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add a timestamp column
     */
    public function timestamp($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'TIMESTAMP',
            'length' => null,
            'nullable' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add created_at and updated_at timestamp columns
     */
    public function timestamps()
    {
        $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
        
        return $this;
    }

    /**
     * Add a JSON column
     */
    public function json($name)
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'JSON',
            'length' => null,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Add an enum column
     */
    public function enum($name, array $values)
    {
        $valueList = "'" . implode("','", $values) . "'";
        
        $this->columns[] = [
            'name' => $name,
            'type' => 'ENUM',
            'length' => "({$valueList})",
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
            'index' => false
        ];
        
        return $this;
    }

    /**
     * Make the last added column nullable
     */
    public function nullable()
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['nullable'] = true;
        }
        
        return $this;
    }

    /**
     * Set the default value for the last added column
     */
    public function default($value)
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['default'] = $value;
        }
        
        return $this;
    }

    /**
     * Make the last added column unsigned
     */
    public function unsigned()
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['unsigned'] = true;
        }
        
        return $this;
    }

    /**
     * Make the last added column auto-incrementing
     */
    public function autoIncrement()
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['autoIncrement'] = true;
        }
        
        return $this;
    }

    /**
     * Make the last added column a primary key
     */
    public function primary()
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['primary'] = true;
        }
        
        return $this;
    }

    /**
     * Make the last added column unique
     */
    public function unique()
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['unique'] = true;
        }
        
        return $this;
    }

    /**
     * Add an index to the last added column
     */
    public function index()
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['index'] = true;
        }
        
        return $this;
    }

    /**
     * Set ON UPDATE for the last added column
     */
    public function onUpdate($value)
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex]['onUpdate'] = $value;
        }
        
        return $this;
    }

    /**
     * Add a foreign key constraint
     */
    public function foreign($column)
    {
        return new ForeignKeyDefinition($column, $this);
    }

    /**
     * Add a foreign key internally
     */
    public function addForeignKey($column, $references, $on, $onDelete = null, $onUpdate = null)
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => $references,
            'on' => $on,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate
        ];
        
        return $this;
    }

    /**
     * Drop a column (for alter operations)
     */
    public function dropColumn($column)
    {
        $this->dropColumns[] = $column;
        return $this;
    }

    /**
     * Create the table
     */
    public function create()
    {
        $this->isCreating = true;
        $sql = $this->buildCreateSql();
        return $this->connection->query($sql);
    }

    /**
     * Alter the table
     */
    public function alter()
    {
        $sql = $this->buildAlterSql();
        return $this->connection->query($sql);
    }

    /**
     * Build CREATE TABLE SQL
     */
    protected function buildCreateSql()
    {
        $sql = "CREATE TABLE `{$this->table}` (\n";
        
        $columnDefinitions = [];
        $primaryKeys = [];
        $indexes = [];
        $uniques = [];
        
        foreach ($this->columns as $column) {
            $definition = $this->buildColumnDefinition($column);
            $columnDefinitions[] = $definition;
            
            if ($column['primary']) {
                $primaryKeys[] = $column['name'];
            }
            
            if ($column['unique']) {
                $uniques[] = $column['name'];
            }
            
            if ($column['index']) {
                $indexes[] = $column['name'];
            }
        }
        
        $sql .= '  ' . implode(",\n  ", $columnDefinitions);
        
        // Add primary key
        if (!empty($primaryKeys)) {
            $sql .= ",\n  PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }
        
        // Add unique constraints
        foreach ($uniques as $unique) {
            $sql .= ",\n  UNIQUE KEY `{$unique}` (`{$unique}`)";
        }
        
        // Add indexes
        foreach ($indexes as $index) {
            $sql .= ",\n  KEY `{$index}` (`{$index}`)";
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $sql .= ",\n  " . $this->buildForeignKeyDefinition($fk);
        }
        
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $sql;
    }

    /**
     * Build ALTER TABLE SQL
     */
    protected function buildAlterSql()
    {
        $alterations = [];
        
        // Add new columns
        foreach ($this->columns as $column) {
            $definition = $this->buildColumnDefinition($column);
            $alterations[] = "ADD COLUMN {$definition}";
        }
        
        // Drop columns
        foreach ($this->dropColumns as $column) {
            $alterations[] = "DROP COLUMN `{$column}`";
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $alterations[] = "ADD " . $this->buildForeignKeyDefinition($fk);
        }
        
        if (empty($alterations)) {
            return '';
        }
        
        return "ALTER TABLE `{$this->table}` " . implode(', ', $alterations);
    }

    /**
     * Build column definition
     */
    protected function buildColumnDefinition($column)
    {
        $definition = "`{$column['name']}` {$column['type']}";
        
        if ($column['length'] !== null) {
            $definition .= "({$column['length']})";
        }
        
        if ($column['unsigned']) {
            $definition .= ' UNSIGNED';
        }
        
        if (!$column['nullable']) {
            $definition .= ' NOT NULL';
        } else {
            $definition .= ' NULL';
        }
        
        if ($column['default'] !== null) {
            if (in_array($column['default'], ['CURRENT_TIMESTAMP', 'NULL'])) {
                $definition .= " DEFAULT {$column['default']}";
            } else {
                $definition .= " DEFAULT '{$column['default']}'";
            }
        }
        
        if (isset($column['onUpdate'])) {
            $definition .= " ON UPDATE {$column['onUpdate']}";
        }
        
        if ($column['autoIncrement']) {
            $definition .= ' AUTO_INCREMENT';
        }
        
        return $definition;
    }

    /**
     * Build foreign key definition
     */
    protected function buildForeignKeyDefinition($fk)
    {
        $definition = "FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['on']}` (`{$fk['references']}`)";
        
        if ($fk['onDelete']) {
            $definition .= " ON DELETE {$fk['onDelete']}";
        }
        
        if ($fk['onUpdate']) {
            $definition .= " ON UPDATE {$fk['onUpdate']}";
        }
        
        return $definition;
    }
}

/**
 * Foreign Key Definition Helper
 */
class ForeignKeyDefinition
{
    protected $column;
    protected $schema;

    public function __construct($column, Schema $schema)
    {
        $this->column = $column;
        $this->schema = $schema;
    }

    public function references($column)
    {
        $this->references = $column;
        return $this;
    }

    public function on($table)
    {
        $this->on = $table;
        return $this;
    }

    public function onDelete($action)
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate($action)
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function __destruct()
    {
        if (isset($this->references) && isset($this->on)) {
            $this->schema->addForeignKey(
                $this->column,
                $this->references,
                $this->on,
                $this->onDelete ?? null,
                $this->onUpdate ?? null
            );
        }
    }
}