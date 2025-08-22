<?php

namespace AdzWP;

class Database
{
    protected static $instance = null;
    protected $wpdb;
    protected $config;
    protected $tablePrefix;
    protected $charset;
    protected $collate;
    
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->config = Config::getInstance();
        $this->tablePrefix = $wpdb->prefix . $this->config->get('database.prefix', 'adz_');
        $this->charset = $this->config->get('database.charset', 'utf8mb4');
        $this->collate = $this->config->get('database.collate', 'utf8mb4_unicode_ci');
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Create a new query builder instance
     */
    public function table($table)
    {
        return new QueryBuilder($this, $table);
    }
    
    /**
     * Execute raw SQL query
     */
    public function query($sql, $params = [])
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        $result = $this->wpdb->query($sql);
        
        if ($result === false) {
            throw new DatabaseException(
                'Query execution failed: ' . $this->wpdb->last_error,
                $sql
            );
        }
        
        return $result;
    }
    
    /**
     * Get results from a query
     */
    public function get($sql, $params = [], $output = OBJECT)
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        return $this->wpdb->get_results($sql, $output);
    }
    
    /**
     * Get a single row
     */
    public function getRow($sql, $params = [], $output = OBJECT)
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        return $this->wpdb->get_row($sql, $output);
    }
    
    /**
     * Get a single value
     */
    public function getValue($sql, $params = [])
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Insert data into table
     */
    public function insert($table, $data, $format = null)
    {
        $table = $this->getTableName($table);
        
        $result = $this->wpdb->insert($table, $data, $format);
        
        if ($result === false) {
            throw new DatabaseException(
                'Insert failed: ' . $this->wpdb->last_error,
                $this->wpdb->last_query
            );
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update data in table
     */
    public function update($table, $data, $where, $format = null, $whereFormat = null)
    {
        $table = $this->getTableName($table);
        
        $result = $this->wpdb->update($table, $data, $where, $format, $whereFormat);
        
        if ($result === false) {
            throw new DatabaseException(
                'Update failed: ' . $this->wpdb->last_error,
                $this->wpdb->last_query
            );
        }
        
        return $result;
    }
    
    /**
     * Delete data from table
     */
    public function delete($table, $where, $whereFormat = null)
    {
        $table = $this->getTableName($table);
        
        $result = $this->wpdb->delete($table, $where, $whereFormat);
        
        if ($result === false) {
            throw new DatabaseException(
                'Delete failed: ' . $this->wpdb->last_error,
                $this->wpdb->last_query
            );
        }
        
        return $result;
    }
    
    /**
     * Start database transaction
     */
    public function beginTransaction()
    {
        $this->query('START TRANSACTION');
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        $this->query('COMMIT');
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        $this->query('ROLLBACK');
    }
    
    /**
     * Execute callback within transaction
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Create table if not exists
     */
    public function createTable($table, $schema)
    {
        $tableName = $this->getTableName($table);
        
        $sql = "CREATE TABLE IF NOT EXISTS $tableName (";
        $sql .= $this->buildColumnDefinitions($schema['columns']);
        
        if (isset($schema['indexes'])) {
            $sql .= ', ' . $this->buildIndexDefinitions($schema['indexes']);
        }
        
        $sql .= ") {$this->charset} COLLATE {$this->collate}";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        return dbDelta($sql);
    }
    
    /**
     * Drop table if exists
     */
    public function dropTable($table)
    {
        $tableName = $this->getTableName($table);
        return $this->query("DROP TABLE IF EXISTS $tableName");
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table)
    {
        $tableName = $this->getTableName($table);
        $result = $this->getValue("SHOW TABLES LIKE %s", [$tableName]);
        return !empty($result);
    }
    
    /**
     * Get table name with prefix
     */
    public function getTableName($table)
    {
        if (strpos($table, $this->tablePrefix) === 0) {
            return $table;
        }
        
        return $this->tablePrefix . $table;
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId()
    {
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get affected rows count
     */
    public function affectedRows()
    {
        return $this->wpdb->rows_affected;
    }
    
    /**
     * Get last error
     */
    public function lastError()
    {
        return $this->wpdb->last_error;
    }
    
    /**
     * Get last query
     */
    public function lastQuery()
    {
        return $this->wpdb->last_query;
    }
    
    protected function buildColumnDefinitions($columns)
    {
        $definitions = [];
        
        foreach ($columns as $name => $definition) {
            if (is_string($definition)) {
                $definitions[] = "$name $definition";
            } elseif (is_array($definition)) {
                $columnSql = "$name {$definition['type']}";
                
                if (isset($definition['length'])) {
                    $columnSql .= "({$definition['length']})";
                }
                
                if (isset($definition['null']) && !$definition['null']) {
                    $columnSql .= ' NOT NULL';
                }
                
                if (isset($definition['default'])) {
                    $columnSql .= " DEFAULT '{$definition['default']}'";
                }
                
                if (isset($definition['auto_increment']) && $definition['auto_increment']) {
                    $columnSql .= ' AUTO_INCREMENT';
                }
                
                $definitions[] = $columnSql;
            }
        }
        
        return implode(', ', $definitions);
    }
    
    protected function buildIndexDefinitions($indexes)
    {
        $definitions = [];
        
        foreach ($indexes as $name => $definition) {
            if (is_string($definition)) {
                $definitions[] = "KEY $name ($definition)";
            } elseif (is_array($definition)) {
                $type = $definition['type'] ?? 'KEY';
                $columns = is_array($definition['columns']) 
                    ? implode(', ', $definition['columns'])
                    : $definition['columns'];
                
                if ($type === 'PRIMARY') {
                    $definitions[] = "PRIMARY KEY ($columns)";
                } elseif ($type === 'UNIQUE') {
                    $definitions[] = "UNIQUE KEY $name ($columns)";
                } else {
                    $definitions[] = "KEY $name ($columns)";
                }
            }
        }
        
        return implode(', ', $definitions);
    }
}

/**
 * Query Builder for fluent database queries
 */
class QueryBuilder
{
    protected $db;
    protected $table;
    protected $select = ['*'];
    protected $where = [];
    protected $joins = [];
    protected $orderBy = [];
    protected $groupBy = [];
    protected $having = [];
    protected $limit;
    protected $offset;
    
    public function __construct(Database $db, $table)
    {
        $this->db = $db;
        $this->table = $db->getTableName($table);
    }
    
    public function select($columns = ['*'])
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function where($column, $operator = '=', $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    public function orWhere($column, $operator = '=', $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    public function whereIn($column, $values)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => $values
        ];
        
        return $this;
    }
    
    public function whereNotIn($column, $values)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => $values
        ];
        
        return $this;
    }
    
    public function whereBetween($column, $values)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => $values
        ];
        
        return $this;
    }
    
    public function whereNull($column)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null
        ];
        
        return $this;
    }
    
    public function whereNotNull($column)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null
        ];
        
        return $this;
    }
    
    public function join($table, $first, $operator = '=', $second = null)
    {
        return $this->addJoin('INNER JOIN', $table, $first, $operator, $second);
    }
    
    public function leftJoin($table, $first, $operator = '=', $second = null)
    {
        return $this->addJoin('LEFT JOIN', $table, $first, $operator, $second);
    }
    
    public function rightJoin($table, $first, $operator = '=', $second = null)
    {
        return $this->addJoin('RIGHT JOIN', $table, $first, $operator, $second);
    }
    
    protected function addJoin($type, $table, $first, $operator, $second)
    {
        if (func_num_args() === 4) {
            $second = $operator;
            $operator = '=';
        }
        
        $this->joins[] = [
            'type' => $type,
            'table' => $this->db->getTableName($table),
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }
    
    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function having($column, $operator = '=', $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function get()
    {
        $sql = $this->buildSelectQuery();
        return $this->db->get($sql);
    }
    
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }
    
    public function count()
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        
        $sql = $this->buildSelectQuery();
        $result = $this->db->getRow($sql);
        
        $this->select = $originalSelect;
        
        return $result ? intval($result->count) : 0;
    }
    
    public function exists()
    {
        return $this->count() > 0;
    }
    
    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($data)
    {
        if (empty($this->where)) {
            throw new DatabaseException('Cannot update without WHERE clause');
        }
        
        $whereClause = $this->buildWhereClause();
        $sql = "UPDATE {$this->table} SET " . $this->buildUpdateClause($data) . " WHERE $whereClause";
        
        return $this->db->query($sql);
    }
    
    public function delete()
    {
        if (empty($this->where)) {
            throw new DatabaseException('Cannot delete without WHERE clause');
        }
        
        $whereClause = $this->buildWhereClause();
        $sql = "DELETE FROM {$this->table} WHERE $whereClause";
        
        return $this->db->query($sql);
    }
    
    protected function buildSelectQuery()
    {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= " FROM {$this->table}";
        
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }
        
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }
        
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->buildHavingClause();
        }
        
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
            
            if ($this->offset) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return $sql;
    }
    
    protected function buildWhereClause()
    {
        $clauses = [];
        
        foreach ($this->where as $index => $condition) {
            $clause = '';
            
            if ($index > 0) {
                $clause .= $condition['type'] . ' ';
            }
            
            $clause .= $this->buildCondition($condition);
            $clauses[] = $clause;
        }
        
        return implode(' ', $clauses);
    }
    
    protected function buildHavingClause()
    {
        $clauses = [];
        
        foreach ($this->having as $condition) {
            $clauses[] = $this->buildCondition($condition);
        }
        
        return implode(' AND ', $clauses);
    }
    
    protected function buildCondition($condition)
    {
        global $wpdb;
        
        $column = $condition['column'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        switch ($operator) {
            case 'IN':
            case 'NOT IN':
                $placeholders = implode(',', array_fill(0, count($value), '%s'));
                return $wpdb->prepare("$column $operator ($placeholders)", $value);
                
            case 'BETWEEN':
                return $wpdb->prepare("$column $operator %s AND %s", $value[0], $value[1]);
                
            case 'IS NULL':
            case 'IS NOT NULL':
                return "$column $operator";
                
            default:
                return $wpdb->prepare("$column $operator %s", $value);
        }
    }
    
    protected function buildUpdateClause($data)
    {
        global $wpdb;
        
        $clauses = [];
        
        foreach ($data as $column => $value) {
            if ($value === null) {
                $clauses[] = "$column = NULL";
            } else {
                $clauses[] = $wpdb->prepare("$column = %s", $value);
            }
        }
        
        return implode(', ', $clauses);
    }
}