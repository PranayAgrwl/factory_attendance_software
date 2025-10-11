<?php

class Model
{
    public $connection;
    public $db; // Alias for $this->connection, used by the Controller for transactions

    public function __construct()
    {
        // Path adjusted assuming db_config is one level up from the Model directory
        $config = require(__DIR__ . '/../config/db_config.php');

        $this->connection = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );
        
        // Set the alias for transaction support
        $this->db = $this->connection; 

        if ($this->connection->connect_error) {
            // In a production environment, this should log the error, not expose it
            die("Database Connection Error: " . $this->connection->connect_error);
        }
    }
    
    // =================================================================
    // DATABASE TRANSACTION METHODS (Used by Controller for salary save)
    // =================================================================
    public function beginTransaction()
    {
        // Check if connection supports transactions (standard for InnoDB/MySQL)
        if (method_exists($this->connection, 'begin_transaction')) {
            return $this->connection->begin_transaction();
        }
        return false;
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollBack()
    {
        return $this->connection->rollback();
    }
    // =================================================================
    
    // =================================================================
    // CUSTOM SELECT (Used by Controller to get unique months with data)
    // =================================================================
    public function selectDataCustom($query)
    {
        $res = $this->connection->query($query);

        if (!$res) {
            // Log or display the error
            error_log("SQL Error in selectDataCustom: " . $this->connection->error . "\nQuery: " . $query);
            return []; 
        }

        $results = [];
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_object()) {
                $results[] = $row;
            }
        }
        return $results;
    }
    // =================================================================
    
    // =================================================================
    // CRUD METHODS
    // =================================================================

    /**
     * Inserts data into a table.
     * @param string $table The table name.
     * @param array $insertArray Associative array of column => value.
     * @return bool Result of the query execution.
     */
    public function insertData ($table, $insertArray)
    {
        // 1. Escape all values
        $escapedValues = array_map(function($value) {
            if (is_string($value)) {
                return $this->connection->real_escape_string($value);
            }
            return $value; 
        }, array_values($insertArray));
        
        $key = implode (",", array_keys($insertArray));
        
        // 2. Build the value string, ensuring strings are quoted
        $valueString = "'" . implode ("','", $escapedValues) . "'";

        $query = "INSERT INTO $table ($key) VALUES ($valueString) ";
        
        $res = $this->connection->query($query);
        return $res;
    }


    /**
     * Selects all data from a table, with optional ordering.
     * @param string $table The table name.
     * @param array $options Array containing options like ['order_by' => 'column DESC'].
     * @return array Array of result objects or empty array.
     */
    public function selectData ($table, $options = [])
    {
        $query = "SELECT * FROM $table";
        
        if (isset($options['order_by'])) {
            $query .= " ORDER BY " . $options['order_by'];
        }
        
        $res = $this -> connection -> query ($query);
        $rw = [];
        if ($res && $res -> num_rows > 0) {
            while ($row = $res -> fetch_object())
            {
                $rw[] = $row;
            }
        }
        return $rw;
    }
    
    
    /**
     * Selects a single record based on WHERE conditions.
     * @param string $table The table name.
     * @param array $where Associative array of column => value conditions.
     * @return object|null Result object or null if not found.
     */
    public function selectOne ($table, $where)
    {
        $query = "SELECT * FROM $table WHERE 1=1";
        foreach($where as $key => $value)
        {
            // Escape the value before using it in the WHERE clause
            $escapedValue = $this->connection->real_escape_string($value);
            $query.=" AND ".$key."='".$escapedValue."'";
        }
        $res = $this -> connection -> query ($query);
        $rw = $res -> fetch_object();
        return $rw ?? null; 
    }

    
    /**
     * Updates records in a table.
     * @param string $table The table name.
     * @param array $setArray Associative array of column => new_value.
     * @param array $where Associative array of column => condition_value.
     * @return bool Result of the query execution.
     */
    public function updateData ($table, $setArray, $where)
    {
        $query = "UPDATE $table SET";
        $setClauses = [];
        
        // Build SET clauses with escaped values
        foreach($setArray as $key => $value)
        {
            $escapedValue = $this->connection->real_escape_string($value);
            $setClauses[] = " " .$key. " = '" .$escapedValue. "'";
        }
        
        $query .= implode(', ', $setClauses);
        
        $query.= " WHERE 1=1 ";
        
        // Build WHERE clauses with escaped values
        foreach($where as $key => $value)
        {
            $escapedValue = $this->connection->real_escape_string($value);
            $query.= " AND " .$key. " = '" .$escapedValue. "' ";
        }
        
        $res = $this -> connection -> query ($query);
        return $res;
    }

    
    /**
     * Deletes records from a table.
     * @param string $table The table name.
     * @param array $where Associative array of column => condition_value.
     * @return bool Result of the query execution.
     */
    public function deleteData ($table, $where)
    {
        $query = "DELETE FROM $table WHERE 1=1";
        foreach($where as $key => $value)
        {
            $escapedValue = $this->connection->real_escape_string($value);
            $query.= " AND $key = '$escapedValue'";
        }
        $res = $this -> connection -> query ($query);
        return $res;
    }

    
    /**
     * Selects multiple records based on WHERE conditions.
     * NOTE: This assumes standard equality (=) unless the key contains a keyword like 'LIKE'.
     * @param string $table The table name.
     * @param array $where Associative array of column => value conditions.
     * @return array Array of result objects or empty array.
     */
    public function selectDataWithCondition ($table, $where)
    {
        $query = "SELECT * FROM $table WHERE 1=1";

        foreach($where as $key => $value)
        {
            // Simple check to see if an operator is likely being used
            if (preg_match('/(LIKE|!=|>|<|>=|<=)/i', trim($key))) {
                // For LIKE or other operators, just escape the value. 
                // The caller must ensure the quotes and operator are correctly formatted in $key/$value.
                $escapedValue = $this->connection->real_escape_string($value);
                $query .= " AND {$key} '{$escapedValue}'"; 
            } else {
                // For standard equality (=), escape the value and quote it
                $escapedValue = $this->connection->real_escape_string($value);
                $query .= " AND {$key} = '{$escapedValue}'";
            }
        }
        
        $res = $this->connection->query($query);
        
        if (!$res) {
            error_log("SQL Error in selectDataWithCondition: " . $this->connection->error);
            return []; 
        }

        $results = [];
        while ($row = $res->fetch_object())
        {
            $results[] = $row;
        }
        
        return $results;
    }
}

?>
