<?php

class Model
{
    public $connection;

    public function __construct()
    {
        $config = require(__DIR__ . '/../config/db_config.php');

        $this->connection = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );

        if ($this->connection->connect_error) {
            die("Database Connection Error: " . $this->connection->connect_error);
        }
    }

    // =================================================================
    // ✅ FIX 1: insertData - Use real_escape_string on ALL values
    // =================================================================
    public function insertData ($table, $insertArray)
    {
        // 1. Escape all values
        $escapedValues = array_map(function($value) {
            // Check if the value is not null and is a string before escaping
            if (is_string($value)) {
                return $this->connection->real_escape_string($value);
            }
            // For numbers or non-strings, return as is (they won't break quotes)
            return $value; 
        }, array_values($insertArray));
        
        $key = implode (",", array_keys($insertArray));
        
        // 2. Build the value string with escaped data
        $valueString = "'" . implode ("','", $escapedValues) . "'";

        $query = "INSERT INTO $table ($key) VALUES ($valueString) ";
        
        $res = $this->connection->query($query);
        return $res;
    }
    // =================================================================


    public function selectData ($table)
    {
        $query = "SELECT * FROM $table";
        $res = $this -> connection -> query ($query);
        while ($row = $res -> fetch_object())
        {
            $rw[] = $row;
        }
        return $rw ?? [];
    }
    
    // =================================================================
    // ✅ FIX 2: selectOne - Use real_escape_string on all WHERE values
    // =================================================================
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
        return $rw ?? [];
    }
    // =================================================================

    // =================================================================
    // ✅ FIX 3: updateData - Use real_escape_string on SET and WHERE values
    // =================================================================
    public function updateData ($table, $setArray, $where)
    {
        $query = "UPDATE $table SET";
        $setClauses = [];
        
        // Build SET clauses with escaped values
        foreach($setArray as $key => $value)
        {
            // Escape the value
            $escapedValue = $this->connection->real_escape_string($value);
            $setClauses[] = " " .$key. " = '" .$escapedValue. "'";
        }
        
        $query .= implode(', ', $setClauses);
        
        $query.= " WHERE 1=1 ";
        
        // Build WHERE clauses with escaped values
        foreach($where as $key => $value)
        {
            // Escape the value
            $escapedValue = $this->connection->real_escape_string($value);
            $query.= " AND " .$key. " = '" .$escapedValue. "' ";
        }
        
        $res = $this -> connection -> query ($query);
        return $res;
    }
    // =================================================================

    // =================================================================
    // ✅ FIX 4: deleteData - Use real_escape_string on WHERE values
    // =================================================================
    public function deleteData ($table, $where)
    {
        $query = "DELETE FROM $table WHERE 1=1";
        foreach($where as $key => $value)
        {
            // Escape the value
            $escapedValue = $this->connection->real_escape_string($value);
            $query.= " AND $key = '$escapedValue'";
        }
        $res = $this -> connection -> query ($query);
        return $res;
    }
    // =================================================================

    // =================================================================
    // ✅ FIX 5: selectDataWithCondition - Use real_escape_string for = comparison
    // =================================================================
    public function selectDataWithCondition ($table, $where)
    {
        $query = "SELECT * FROM $table WHERE 1=1";

        foreach($where as $key => $value)
        {
            // Check for conditions that use operators like 'LIKE'
            if (strpos(strtoupper(trim($key)), 'LIKE') !== false) {
                // For LIKE, we still need to escape the user-supplied value.
                // NOTE: The value passed by the controller for LIKE already contains the % or is a date.
                $escapedValue = $this->connection->real_escape_string($value);
                // We assume the caller handles the surrounding quotes for LIKE correctly.
                $query .= " AND {$key} '{$escapedValue}'"; 
            } else {
                // For standard equality (=), escape the value and quote it
                $escapedValue = $this->connection->real_escape_string($value);
                $query .= " AND {$key} = '{$escapedValue}'";
            }
        }
        
        $res = $this->connection->query($query);
        
        if (!$res) {
            echo "SQL Error in selectDataWithCondition: " . $this->connection->error;
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