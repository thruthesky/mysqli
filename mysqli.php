<?php
$mysqli = null;

/**
 * MySQLi connect to database.
 *
 * Must call this function before using any of MySQLi database SQL query functions.
 *
 * @param string $hostname
 * @param string $username
 * @param string $password
 * @param string $database
 * @return void
 *
 * @example
 * connect("mariadb", "root", "Wc~Cx7", "wonderfulkorea");
 */
function connect(string $hostname, string $username, string $password, string $database) {
    global $mysqli;
    $mysqli = new mysqli($hostname, $username, $password, $database);

}

/**
 * select 쿼리를 하고, 결과를 2차원 연관 배열로 가져온다.
 * 결과 값이 없으면 빈 배열을 리턴한다.
 * @param $sql
 * @return array
 * @example
 *  print_r(select("SELECT * FROM posts WHERE id != 'abc' AND timestamp > 5"));
 */
function select($sql) {
    global $mysqli;
    $rows = [];
    if ($result = $mysqli->query($sql)) {
        while($row = $result->fetch_assoc()){
            $rows[] = $row;
        }
    }
    return $rows;
}


/**
 * @param string $table - Mysql table name.
 * @param array $dataArray - Array of data. Must be associative array with column name as key and data as value.
 * @param bool $returnID - Return affected rows or insert id. By default, it is false and return affected rows.
 *      But if you want get the last insert id then pass this as true.
 *      Remember if there is no 'auto increment', then last insert id will be zero(0).
 * @return mixed
 *      - It will return -1 when $returnID was given false and there is an error.
 *
 * $mysqli is a database connection object.
 *
 * @example
 *  $db = new mysqli("localhost","root","","demo");
 *  if($db->connect_error) {
        die("Database Connection Error, Error No.: ".$db->connect_errno." | ".$db->connect_error);
    }
 *  $array = array(
 *      "name"=>"Ahsan Zameer",
 *      "email"=>"ahsan@wdb24.com",
 *      "subject"=>"This is subject",
 *      "message"=>"This is my dummy message"
 *  );
 * echo insert("customers",$array,true);
 *
 * @example
 *  $number_of_affected_rows = insert('posts', ['id' => 'id----6', 'title' => 'title 6', 'content' => 'content 6"\'"', 'timestamp' => 6]);
 *
 */
function insert(string $table, array $dataArray, bool $returnID = false): mixed
{
	global $mysqli;

	$getColumnsKeys = array_keys($dataArray);
	$implodeColumnKeys = implode(",",$getColumnsKeys);

	$getValues = array_values($dataArray);
    $getValues_esc = [];
    foreach( $getValues as $val ) {
        $getValues_esc[] = $mysqli->real_escape_string($val);
    }
	$implodeValues = "'".implode("','",$getValues_esc)."'";

	$qry = "INSERT INTO $table (".$implodeColumnKeys.") values (".$implodeValues.")";
	$mysqli->query($qry);

	if($returnID == true)
    {
        return $mysqli->insert_id;
    }
    else
    {
        return $mysqli->affected_rows;
    }
}

/**
 * @param string $table
 * @param array $data
 * @param string $where
 * @return bool
 *  성공이면 true, 실패이면 false
 *
 *
 *
 * @example
 *  update('posts', ['title' => 'title 2 updated', 'content' => 'content 2 updated'], "timestamp=2");
 */
function update(string $table, array $data, string $where)
{
    global $mysqli;
    $setColumn= array();

    foreach ($data as $key => $value)
    {
        $setColumn[] = "$key='".$mysqli->real_escape_string($value)."'";
    }

    $sql = "UPDATE {$table} SET ".implode(', ', $setColumn)." WHERE $where";
    return $mysqli->query($sql);
}
