<?php

namespace AdzWP;

/**
 * Database Connection Manager
 * 
 * Handles database connections and provides a simple interface
 * for WordPress database operations
 */
class Connection
{
    protected static $instance = null;
    protected $wpdb;
    protected $prefix;

    protected function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Get WordPress database instance
     */
    public function getWpdb()
    {
        return $this->wpdb;
    }

    /**
     * Get table prefix
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get full table name with prefix
     */
    public function getTable($table)
    {
        return $this->prefix . $table;
    }

    /**
     * Execute a raw SQL query
     */
    public function query($sql)
    {
        return $this->wpdb->query($sql);
    }

    /**
     * Get a single variable from the database
     */
    public function getVar($sql, $col = 0, $row = 0)
    {
        return $this->wpdb->get_var($sql, $col, $row);
    }

    /**
     * Get a single row from the database
     */
    public function getRow($sql, $output = OBJECT, $row = 0)
    {
        return $this->wpdb->get_row($sql, $output, $row);
    }

    /**
     * Get column of data from the database
     */
    public function getCol($sql, $col = 0)
    {
        return $this->wpdb->get_col($sql, $col);
    }

    /**
     * Get multiple rows from the database
     */
    public function getResults($sql, $output = OBJECT)
    {
        return $this->wpdb->get_results($sql, $output);
    }

    /**
     * Insert data into a table
     */
    public function insert($table, $data, $format = null)
    {
        $table = $this->getTable($table);
        return $this->wpdb->insert($table, $data, $format);
    }

    /**
     * Update data in a table
     */
    public function update($table, $data, $where, $format = null, $where_format = null)
    {
        $table = $this->getTable($table);
        return $this->wpdb->update($table, $data, $where, $format, $where_format);
    }

    /**
     * Delete data from a table
     */
    public function delete($table, $where, $where_format = null)
    {
        $table = $this->getTable($table);
        return $this->wpdb->delete($table, $where, $where_format);
    }

    /**
     * Replace data in a table
     */
    public function replace($table, $data, $format = null)
    {
        $table = $this->getTable($table);
        return $this->wpdb->replace($table, $data, $format);
    }

    /**
     * Get the ID of the last inserted row
     */
    public function getInsertId()
    {
        return $this->wpdb->insert_id;
    }

    /**
     * Get the number of rows affected by the last query
     */
    public function getAffectedRows()
    {
        return $this->wpdb->rows_affected;
    }

    /**
     * Get the last database error
     */
    public function getLastError()
    {
        return $this->wpdb->last_error;
    }

    /**
     * Prepare a SQL statement for safe execution
     */
    public function prepare($query, ...$args)
    {
        return $this->wpdb->prepare($query, ...$args);
    }

    /**
     * Show SQL errors
     */
    public function showErrors($show = true)
    {
        $this->wpdb->show_errors($show);
    }

    /**
     * Hide SQL errors
     */
    public function hideErrors()
    {
        $this->wpdb->hide_errors();
    }

    /**
     * Start a database transaction
     */
    public function startTransaction()
    {
        return $this->query('START TRANSACTION');
    }

    /**
     * Commit a database transaction
     */
    public function commit()
    {
        return $this->query('COMMIT');
    }

    /**
     * Rollback a database transaction
     */
    public function rollback()
    {
        return $this->query('ROLLBACK');
    }

    /**
     * Check if a table exists
     */
    public function tableExists($table)
    {
        $table = $this->getTable($table);
        $result = $this->getVar($this->prepare("SHOW TABLES LIKE %s", $table));
        return $result === $table;
    }

    /**
     * Get table structure
     */
    public function describeTable($table)
    {
        $table = $this->getTable($table);
        return $this->getResults("DESCRIBE `$table`");
    }

    /**
     * Execute multiple queries in a transaction
     */
    public function transaction(callable $callback)
    {
        $this->startTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}