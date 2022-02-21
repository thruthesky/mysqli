<?php
/**
 * @file mysqli.php
 */
/**
 * MySQLi
 *
 * 기본적인 사용법
 *
 * 먼저, require_once './mysqli/mysqli.php'; 와 같이 로드하면, db() 함수를 사용 할 수 있다.
 *   이 db() 함수는 MySQLiDatabase 객체를 1개만 생성하여 메모리에 보관하고 재 사용 할 수 있도록 해 준다.
 *
 *  - 로그 기록을 보려면,
 *      - db()->log('log_message_function_name') 와 같이 해서, 로그를 기록 할 콜백 함수를 지정한다.
 *  - db()->connect() 함수로 DB 접속을 한다.
 *  - 그 후, db()->insert(), db()->update(), db()->select(), db()->delete() 함수를 사용하면 된다.
 *  - insert() 함수는 에러인 경우, false 를 리턴하며, 성공이면 number of affected rows 를 리턴하는데 보통 1 을 리턴한다.
 *  - update() 와 delete() 함수는 에러인 경우 false 를 리턴한다. 성공이면 true 를 리턴한다.
 *
 * 쿼리를 할 때, 에러가 발생하는 경우, 에러 메시지를 내고 exit 를 한다.
 *  - SQL 문장에 에러가 있는 경우 외에도,
 *  - DB 접속 정보가 틀리거나
 *  - 중복된 키를 입력하려는 경우에도 exit 를 한다.
 *
 */

class MySQLiDatabase {

    private mysqli $mysqli;
    /**
     * 에러가 있는 경우, 로그를 기록할 함수.
     *
     * @var mixed|null
     *
     * @example
     *  db()->log = 'dog';
     */
    public mixed $log = null;

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
        $this->mysqli = new mysqli($hostname, $username, $password, $database);
        $this->check_connection_error();
    }

    function check_connection_error() {
        /* check connection */
        if ($this->mysqli->connect_errno) {
            printf("Connect failed: %s\n", $this->mysqli->connect_error);
            exit();
        }
    }
    function check_query_error(string $q = '') {
        /* check last error */
        if ($this->mysqli->errno) {
            $msg = "Query failed: " . $this->mysqli->error . "\n";
            if ( $q ) {
                $msg .= "SQL: $q";
            }
            if ( $this->log ) {
                call_user_func($this->log, $msg);
            }
            die($msg);
        }
    }

    /**
     * select 쿼리를 하고, 결과를 2차원 연관 배열로 가져온다.
     * 결과 값이 없으면 빈 배열을 리턴한다.
     * @param string $sql
     * @return array
     * @example
     *  print_r(select("SELECT * FROM posts WHERE id != 'abc' AND timestamp > 5"));
     */
    function select(string $sql): array {
        $rows = [];
        $res = $this->mysqli->query($sql);
        $this->check_query_error($sql);
        if ($result = $res) {
            while($row = $result->fetch_assoc()){
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * 여러 행을 리턴하는 것으로 단순히, select() 의 alias 이다.
     *
     * @param string $sql
     * @return array
     */
    function rows(string $sql): array {
        return $this->select($sql);
    }

    /**
     * 하나의 행(row) 를 리턴한다.
     * 값이 없으면 빈 배열을 리턴한다.
     * @param string $sql
     * @return array
     */
    function row(string $sql): array {
        $rows = $this->rows($sql);
        if ( count($rows) ) {
            return $rows[0];
        } else {
            return [];
        }
    }


    /**
     * @param string $table - Mysql table name.
     * @param array $dataArray - Array of data. Must be associative array with column name as key and data as value.
     * @param bool $returnID - Return affected rows or insert id. By default, it is false and return affected rows.
     *      But if you want get the last insert id then pass this as true.
     *      Remember if there is no 'auto increment', then last insert id will be zero(0).
     * @return mixed
     *      - It will return -1 when $returnID was given false and there is an error.
     *          - 참고, 공홈에는 mysqli::query 에서 insert 를 하는 경우, 에러가 있는 경우, false 를 리턴한다고 하는데, 실제로는 -1 이 리턴된다.
     *              따라서, 결과를 확인 할 때에는 리턴된 값이 0 보다 큰지를 확인해야 한다.
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
        $getColumnsKeys = array_keys($dataArray);
        $implodeColumnKeys = implode(",",$getColumnsKeys);

        $getValues = array_values($dataArray);
        $getValues_esc = [];
        foreach( $getValues as $val ) {
            $getValues_esc[] = $this->mysqli->real_escape_string($val);
        }
        $implodeValues = "'".implode("','",$getValues_esc)."'";

        $qry = "INSERT INTO $table (".$implodeColumnKeys.") values (".$implodeValues.")";
        $this->mysqli->query($qry);
        $this->check_query_error($qry);

        if($returnID == true)
        {
            return $this->mysqli->insert_id;
        }
        else
        {
            return $this->mysqli->affected_rows;
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
    function update(string $table, array $data, string $where): bool
    {
        $setColumn= array();

        foreach ($data as $key => $value)
        {
            $setColumn[] = "$key='".$this->mysqli->real_escape_string($value)."'";
        }

        $sql = "UPDATE {$table} SET ".implode(', ', $setColumn)." WHERE $where";
        $re = $this->mysqli->query($sql);
        $this->check_query_error($sql);

        return $re;
    }

    /**
     * @param string $table
     * @param string $where
     * @return bool
     *  - true on success. ! warning - it will return true even if it didn't delete anything.
     *  - false on failure.
     */
    function delete(string $table, string $where): bool {
        $q = "DELETE FROM $table WHERE $where";
        $re = $this->mysqli->query($q);
        $this->check_query_error($q);
        return $re;
    }

}

$globalDb = null;
function db(): MySQLiDatabase {
    global $globalDb;
    if ( ! $globalDb ) {
        $globalDb = new MySQLiDatabase();
    }
    return $globalDb;
}