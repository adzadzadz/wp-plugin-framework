<?php

namespace AdzWP\Db;

/**
 * Database Query Builder
 * 
 * Provides a fluent interface for building database queries
 */
class QueryBuilder
{
    protected $connection;
    protected $select = [];
    protected $from;
    protected $joins = [];
    protected $where = [];
    protected $groupBy = [];
    protected $having = [];
    protected $orderBy = [];
    protected $limit;
    protected $offset;

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection ?: Connection::getInstance();
    }

    /**
     * Set the table to select from
     */
    public function table($table)
    {
        $this->from = $this->connection->getTable($table);
        return $this;
    }
    
    /**
     * Alias for table() method
     */
    public function from($table)
    {
        return $this->table($table);
    }

    /**
     * Set the columns to select
     */
    public function select($columns = ['*'])
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $this->select = array_merge($this->select, $columns);
        return $this;
    }

    /**
     * Add a JOIN clause
     */
    public function join($table, $first, $operator, $second, $type = 'INNER')
    {
        $table = $this->connection->getTable($table);
        $this->joins[] = "{$type} JOIN `{$table}` ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     */
    public function leftJoin($table, $first, $operator, $second)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause
     */
    public function rightJoin($table, $first, $operator, $second)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add a WHERE clause
     */
    public function where($column, $operator = '=', $value = null)
    {
        if ($value === null) {
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

    /**
     * Add an OR WHERE clause
     */
    public function orWhere($column, $operator = '=', $value = null)
    {
        if ($value === null) {
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

    /**
     * Add a WHERE IN clause
     */
    public function whereIn($column, array $values)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT IN clause
     */
    public function whereNotIn($column, array $values)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * Add a WHERE NULL clause
     */
    public function whereNull($column)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS',
            'value' => 'NULL'
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause
     */
    public function whereNotNull($column)
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT',
            'value' => 'NULL'
        ];

        return $this;
    }

    /**
     * Add a LIKE clause
     */
    public function whereLike($column, $value)
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * Add a GROUP BY clause
     */
    public function groupBy($columns)
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * Add a HAVING clause
     */
    public function having($column, $operator = '=', $value = null)
    {
        if ($value === null) {
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

    /**
     * Add an ORDER BY clause
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = "`{$column}` {$direction}";
        return $this;
    }

    /**
     * Set the LIMIT clause
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET clause
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Build the SELECT query
     */
    public function toSql()
    {
        $sql = 'SELECT ';
        
        // SELECT
        if (empty($this->select)) {
            $sql .= '*';
        } else {
            $sql .= implode(', ', array_map(function($col) {
                return $col === '*' ? $col : "`{$col}`";
            }, $this->select));
        }
        
        // FROM
        $sql .= " FROM `{$this->from}`";
        
        // JOINs
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        // WHERE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $whereClause = [];
            
            foreach ($this->where as $i => $condition) {
                $clause = '';
                
                if ($i > 0) {
                    $clause .= ' ' . $condition['type'] . ' ';
                }
                
                $clause .= "`{$condition['column']}` {$condition['operator']} ";
                
                if (in_array($condition['operator'], ['IN', 'NOT IN'])) {
                    $placeholders = implode(',', array_fill(0, count($condition['value']), '%s'));
                    $clause .= "({$placeholders})";
                } elseif ($condition['value'] === 'NULL') {
                    $clause = rtrim($clause) . ' NULL';
                } else {
                    $clause .= '%s';
                }
                
                $whereClause[] = $clause;
            }
            
            $sql .= implode('', $whereClause);
        }
        
        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', array_map(function($col) {
                return "`{$col}`";
            }, $this->groupBy));
        }
        
        // HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ';
            $havingClause = [];
            
            foreach ($this->having as $condition) {
                $havingClause[] = "`{$condition['column']}` {$condition['operator']} %s";
            }
            
            $sql .= implode(' AND ', $havingClause);
        }
        
        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        // LIMIT
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        // OFFSET
        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }

    /**
     * Get the prepared SQL with values
     */
    public function getPreparedSql()
    {
        $sql = $this->toSql();
        $values = $this->getValues();
        
        if (!empty($values)) {
            return $this->connection->prepare($sql, ...$values);
        }
        
        return $sql;
    }

    /**
     * Get all values for the query
     */
    protected function getValues()
    {
        $values = [];
        
        // WHERE values
        foreach ($this->where as $condition) {
            if (in_array($condition['operator'], ['IN', 'NOT IN'])) {
                $values = array_merge($values, $condition['value']);
            } elseif ($condition['value'] !== 'NULL') {
                $values[] = $condition['value'];
            }
        }
        
        // HAVING values
        foreach ($this->having as $condition) {
            $values[] = $condition['value'];
        }
        
        return $values;
    }

    /**
     * Execute the query and get results
     */
    public function get()
    {
        $sql = $this->getPreparedSql();
        return $this->connection->getResults($sql);
    }

    /**
     * Get the first result
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Get a single value
     */
    public function value($column)
    {
        $this->select([$column])->limit(1);
        $sql = $this->getPreparedSql();
        return $this->connection->getVar($sql);
    }

    /**
     * Get count of records
     */
    public function count($column = '*')
    {
        $originalSelect = $this->select;
        $this->select = ["COUNT({$column}) as total"];
        $result = $this->first();
        $this->select = $originalSelect;
        
        return $result ? (int) $result->total : 0;
    }

    /**
     * Check if records exist
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Paginate results
     */
    public function paginate($perPage = 15, $page = 1)
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;
        
        $this->limit($perPage)->offset($offset);
        $items = $this->get();
        
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
}