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

	public function insertData ($table, $insertArray)
	{
		$key = implode (",", array_keys($insertArray));
		$value = implode ("','", array_values($insertArray));
		$query = "INSERT INTO $table ($key) VALUES ('$value') ";
		$res = $this -> connection -> query ($query);
		return $res;
	}

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

	public function selectOne ($table, $where)
	{
		$query = "SELECT * FROM $table WHERE 1=1";
		foreach($where as $key => $value)
		{
			$query.=" AND ".$key."='".$value."'";
		}
		$res = $this -> connection -> query ($query);
		$rw = $res -> fetch_object();
		return $rw ?? [];
	}
	
	public function updateData ($table, $setArray, $where)
	{
		$query = "UPDATE $table SET";
		$count = count ($setArray);
		$i=0;
		foreach($setArray as $key => $value)
		{
			if($i < $count -1)
			{
				$query.= " " .$key. " = '".$value."', ";
			}
			else
			{
				$query.= " " .$key. " = '" .$value."' ";
			}
			$i++;
		}
		$query.= " WHERE 1=1 ";
		foreach($where as $key => $value)
		{
			$query.= " AND " .$key. " = '" .$value. "' ";
		}
		// echo "<pre>$query</pre>";
		$res = $this -> connection -> query ($query);
		return $res;
	}

	public function deleteData ($table, $where)
	{
		$query = "DELETE FROM $table WHERE 1=1";
		foreach($where as $key => $value)
		{
			$query.= " AND $key = '$value'";
		}
		$res = $this -> connection -> query ($query);
		return $res;
	}

	public function selectDataWithCondition ($table, $where)
    {
        $query = "SELECT * FROM $table WHERE 1=1";

        // Append each condition from the $where array to the query
        foreach($where as $key => $value)
        {
            // Check if the condition key contains 'LIKE'
            if (strpos(strtoupper(trim($key)), 'LIKE') !== false) {
                // For LIKE, the value should already contain quotes and wildcards
                // e.g., ['entry_date LIKE' => "'2025-10%'"]
                // We assume the caller (Controller) passes the value WITH quotes if needed.
                // NOTE: The Controller passes it as "{$selected_year_month}%" which is correct.
                $query .= " AND {$key} '{$value}'";
            } else {
                // For standard equality (=), quote the value
                $query .= " AND {$key} = '{$value}'";
            }
        }
        
        // Final query will look like:
        // SELECT * FROM daily_attendance_report WHERE 1=1 AND entry_date LIKE '2025-10%'
        
        $res = $this->connection->query($query);
        
        // Check if the query was successful and returned results
        if (!$res) {
            // Handle error (e.g., log it or return an error message)
            echo "SQL Error in selectDataWithCondition: " . $this->connection->error;
            return []; 
        }

        $results = [];
        // Fetch all resulting rows as objects
        while ($row = $res->fetch_object())
        {
            $results[] = $row;
        }
        
        // Return the array of objects, or an empty array if no rows were found
        return $results;
    }
}

?>
