<?php
namespace gokabam_api;
/**
 * @author Will Woodlief
 * @license MIT Licence
 * @link https://gist.github.com/willwoodlief/caeae241fb36bffe470ed77a18958097 for updates and code fixes outside this project
 *
 * This is a general database class I have been using though the years, and has its origin from all over the internet
 *   as well as my improvements.
 *
 * I often use it as a drop in class
 *
 */

require_once realpath(dirname(__FILE__)) . "/JsonHelper.php";
/**
 * Class SQLException
 *   This is an exception thrown by the @see MYDB
 *   if case things go wrong
 */
class SQLException extends \Exception
{
}

/**
 * Class SQLDataException
 *   This is a type of SQLException that is thrown when there is something wrong with the data, so not a database error
 */
class SQLDataException extends SQLException
{
}



/**
 * Class EnumValues
 *  The is a helper class which is makes it easier to use column type enumeration in the database
 *  gets values of a column if its an enum
 *    great if you are using enums in a database
 */
class EnumValues
{


    /**
     * @var array of strings , each a name of valid enum
     */
    protected $values;

    /**
     * @return array of valid enums for this column
     */
    function getValues()
    {
        return $this->values;
    }

    /**
     * tests if something is a valid enum for the column
     * @param $type
     * @return bool
     */
    function belongs($type)
    {
        $ok = false;
        foreach ($this->values as $e) {
            if ($type == $e) {
                $ok = true;
                break;
            }
        }
        return $ok;
    }

    /**
     * EnumValues constructor.
     * @param $table string
     * @param $column string
     * @param $mysqli object
     * @throws SQLException if the column does not exist
     */
    public function __construct($table, $column, $mysqli)
    {

        $mydb = new MYDB($mysqli);
        $sql = "SHOW COLUMNS FROM $table LIKE '$column'";

        $result = $mydb->execute($sql);
        if ($result) { // If the query's successful

            $enum = \mysqli_fetch_object($result);
            preg_match_all("/'([\w ]*)'/", $enum->Type, $values);
            $this->values = $values[1];


        } else {

            MYDB::throwSQLErrorWithHtml("unable to get enum from column", $mydb->mysqli);

        }

    }
}

/**
 * Class MYDB
 *   Database wrapper class which makes it easier to do prepared statements and commits and rollbacks
 *    throws exceptions if there are any sql errors
 */
class MYDB
{


    /**
     * @const integer MYDB::LAST_ID is used in @see MYDB::execSQL() for insert statements only
     */
    const LAST_ID = 1;


    /**
     * @const integer MYDB::ROWS_AFFECTED is used in @see MYDB::execSQL() for update statements only
     */
    const ROWS_AFFECTED = 2;

    /**
     * @const integer MYDB::RESULT_SET is used in @see MYDB::execSQL() for select statements only
     */
    const RESULT_SET = 3;


    /**
     * @var object|\mysqli the mysqli object, the database connection
     */
    public  $mysqli = null;

    /**
     * @var bool $destroyMe is a flag which tells the destructor if this connection needs to be taken down
     */
    private $destroyMe = false;

    /**
     * @var object|\mysqli_result is used in the static connection helper methods
     */
    protected $result = null;

    //  $transactionCount is  in $mysqli;
    //for storing and reusing prepared statements as requested, they are put into
    //statementCache in the $mysqli

    /**
     * @var int $aardvark is used in the constructor to help make sure there are not connection leaks
     */
    private static $aardvark = 0;//to debug to see if more than one connection

    /**
     * @var int $AnExistCount is a remembering of how many open connections there are when the object is created
     */
    private $AnExistCount = -1010;

    /**
     *  Tests if a value is part of an enum set of the column
     * @param $test
     * @param $table
     * @param $column
     * @return bool
     * @throws
     */
    public function checkEnumLegal($test, $table, $column)
    {
        $ee = new EnumValues($table, $column, $this->mysqli);
        return $ee->belongs($test);

    }

    /**
     * Gets the hash of cached statements
     * @see MYDB::execSQL()
     * @return array <p>
     *    key value pair of "name to remember prepared statement": mysqli_stmt
     * </p>
     */
    public function getCachedStatements()
    {
        return $this->mysqli->statementCache;
    }

    /**
     * The object can either open a new connection or be a wrapper for an existing connection
     *
     * MYDB constructor.
     * @param $mysqli object|null <p>
     *   pass in null to make a new database connection
     *   pass in an existing mysqli object to wrap it around this class
     * </p>
     * @param array|null $db_setup <p>
     *  if creating new connection ($mysqli is null above) then this must be filled out
     *   @see MYDB::getMySqliDatabase() for details
     *  but if passing in mysqli object above, then this will be ignored
     *  default is null
     *
     * </p>
     * @param bool $bIgnoreAardvark <p>
     *    if set to false, then an exception will be thrown if more than one database connection is kept open at one time
     *    if set to true then this behavior is turned off
     *    default is false, which means the default will be throwing exceptions if more than one db connection open
     * </p>
     * @throws SQLException  if connection cannot be made, or $bIgnoreAardvark is false and a second connection made
     */
    public function __construct($mysqli, array $db_setup = null, $bIgnoreAardvark = false)
    {

        if (is_null($mysqli)) {

            $this->destroyMe = true;
            $this->mysqli = self::getMySqliDatabase($db_setup);
            MYDB::$aardvark++;
            $this->AnExistCount = MYDB::$aardvark;

            if (!$bIgnoreAardvark) { //only if deliberate should the program not exit if more than one connection open
                if ($this->AnExistCount > 1) {
                    try {

                        $what = $this->AnExistCount;
                        throw new SQLException("++>exists greater than one [$what]", $this->AnExistCount);

                    } catch (SQLException $e) {
                        //getEID
                        if (method_exists($e, 'getEID')) {
                            $eid = $e->getEID();
                        } else {
                            $eid = '';
                        }
                        print "<hr>too many database connectoins open eid[$eid]<hr>";
                        exit;
                    }
                }
            }
        } else {
            if (!($mysqli instanceof \mysqli)) {
                $out = print_r($mysqli, true);
                throw new SQLException("the mysqli passed in is not who you think it is", $out);
            }
            $this->AnExistCount = MYDB::$aardvark;
            $this->destroyMe = false;
            $this->mysqli = $mysqli;
        }
        if (!isset($this->mysqli->statementCache)) {
            $this->mysqli->statementCache = array();
        }

        if (!isset($this->mysqli->transactionCount)) {
            $this->mysqli->transactionCount = 0;
        }


    }

    /**
     *  Destructor, notice that it will not close out the mysqli if this object is being used as a smart pointer
     */
    public function __destruct()
    {
        if ($this->destroyMe) {
            //close out the cached statements

            foreach ($this->mysqli->statementCache as $s) {
                /** @var $s \mysqli_stmt */
                $s->close();
            }
            mysqli_close($this->mysqli);
            $this->mysqli = null;
        }

    }

    /**
     * Gets the underlying mysqli object
     * @return \mysqli|null
     */
    public function getDBHandle()
    {
        return $this->mysqli;
    }

    /**
     * begins an sql transaction for the mysqli object
     * once this is called @see MYDB::commit() needs to be called to apply anything between this and that to the database
     * This is applied to the mysqli object in this class and not the class itself
     * Can be called multiple times, but the @see MYDB::commit() must be called same amount of times before the data is commited
     * @return void
     */
    public function beginTransaction()
    {


        $this->mysqli->transactionCount++;

        //jad( "doing begin ".$this->mysqli->transactionCount);
        if ($this->mysqli->transactionCount > 1) {
            return;
        }
        $this->mysqli->autocommit(FALSE);

    }

    /**
     * Finishes an sql transaction for the mysqli object
     *   But will only do the work when this is called same number of times as @see MYDB::beginTransaction() was before
     * @throws SQLException if beginTransaction was not called ahead of time
     * @return void
     */
    public function commit()
    {

        $this->mysqli->transactionCount--;
        //jad( "doing commit ".$this->mysqli->transactionCount);
        if ($this->mysqli->transactionCount < 0) {
            throw new SQLException('Commit in mydb is misaligned', $this->mysqli->transactionCount);
        }
        if ($this->mysqli->transactionCount > 0) {
            return;
        }
        $this->mysqli->commit();
        $this->mysqli->autocommit(TRUE);


    }

    /**
     * Rolls back a transaction, will set the transaction count, relied on by @see MYDB::commit() to 0
     * @return void
     */
    public function rollback()
    {
        //jad( "doing rollback");
        $this->mysqli->rollback();
        $this->mysqli->autocommit(TRUE);
        $this->mysqli->transactionCount = 0;
    }

    /**
     * Object wrapper for doing a mysqli_query. If one needs to not use @see MYDB::execSQL()
     * Can be used with @see MYDB::fetch()
     * and @see MYDB::fetchThrowIfNull()
     * @see MYDB::staticExecute()
     * @param $sql
     * @return bool|\mysqli_result|null
     * @throws SQLException
     */
    public function execute($sql)
    {
        $this->result = self::staticExecute($sql, $this->mysqli);
        return $this->result;
    }

    /**
     * Can get the data after using @see MYDB::execute()
     * use in a loop, it will return boolean false (null) if end of data rows
     * @return array|null  the array in key:value pairs for column_name:data
     */
    public function fetch()
    {
        return mysqli_fetch_array($this->result);
    }

    /**
     * Can get the data after using @see MYDB::execute()
     * call @see MYDB::getRowCount() to see how many rows are called
     *   use in a for(;;) statement
     *   its designed to throw an exception is called too many times
     * @return array|null
     * @throws SQLDataException if called after data is finished
     */
    public function fetchThrowIfNull()
    {
        $res = mysqli_fetch_array($this->result);
        if (!$res) {
            throw new SQLDataException("no_data");
        }
        return $res;
    }

    /**
     * Gets the created row Primary key right after an insert, can be used after both
     * @see MYDB::execute() and
     * @see MYDB::execSQL()
     * @return integer <p>
     *   will be 0 if no last insert value, or if the table does not have AUTO_INCREMENT on a PK
     */
    public function getLastIndex()
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Gets the number of rows in a results set
     * Can only be used after @see MYDB::execute()
     * @return int|false
     */
    public function getRowCount()
    {
        return mysqli_num_rows($this->result);
    }

    /**
     * Prepares a statement without binding variables
     * Helper wrapper for mysqli_prepare
     * @param $sql
     * @return bool|\mysqli_stmt
     * @throws SQLException  if there is any sql error
     */
    public function prepare($sql)
    {
        $st = mysqli_prepare($this->mysqli, $sql);
        if (!$st) {
            self::throwSQLErrorWithHtml("Could not prepare statement", $this->mysqli);
        }
        return $st;
    }

    /**
     * strips tags and escapes string for insertion into database
     * @see MYDB::ICleanString()
     * @param $string
     * @return string
     */
    public function cleanString($string)
    {
        try {
            $string = self::sanitizeString($string, false, $this->mysqli);
        } catch (SQLException $e) {
            print $e; //will never get here, but needed so don't have to declare exception in phpdoc
        }

        return $string;
    }




    /**
     * Multipurpose statement to write prepared statements to the database
     *
     * @example
     *  execSQL("SELECT * FROM table WHERE id = ?", array('i', $id), MYDB::ROWS_AFFECTED);
     *  execSQL("SELECT * FROM table");
     *  execSQL("INSERT INTO table(id, name) VALUES (?,?)", array('ss', $id, $name), MYDB::LAST_ID);
     *  execSQL("UPDATE table(id, name) SET A = ? , B = ? ", array('is', $id, $name), MYDB::ROWS_AFFECTED, 'Remember this statement for cool method');
     *  execSQL("SELECT * FROM table where cats = :cats AND dogs = :dogs", ['cats'=>'brown','dogs'=>['value'=>4.5, 'flag'=>'d']], MYDB::ROWS_AFFECTED);
     *  execSQL("SELECT * FROM table where cats = :cats AND dogs = :dogs NOT IN (:mud)", [
     *                                                                      'dogs'=>['value'=>4.5, 'flag'=>'d'],
     *                                                                      'cats'=> $brown,
     *                                                                      'mud'=> get_mud_ratio()/2 * $f]);
     *
     * @param $sql string|object <p>
     *   if string, then the sql statement must have at least one ? in it. if a statement does not need a ?
     *   then add a "AND ?" to the where, for example, and then place a variable with 1 in the params
     *
     *   Can be object of type mysqli_stmt, if need to pass in an already compiled statement
     * </p>
     * @param $params null|array <p>
     *
     *   The rest of the array depends on if using ? notation or named  notation in the sql string and if $sql is an object or a string
     *
     * @example for ? notation  "SELECT apple from tree_table where color = ?"
     *          each param is ?
     *
     * @example for named notation  "SELECT apple from tree_table where color = :color_name"
     *      see the : before the param name, all param names need to begin with :
     *
     *  If $sql is an object must use ? notation
     *
     *   for ? notation
     *   a single dimension array needs to be passed
     *    the first element of the array will be a string, these will be the letters discussed in
     *   @link http://php.net/manual/en/mysqli-stmt.bind-param.php
     *    i	    corresponding variable has type integer
     *    d	    corresponding variable has type double
     *    s	    corresponding variable has type string
     *    b	    corresponding variable is a blob and will be sent in packets
     *
     *   these letters need to in order of the params in the sql statement
     *     the rest of the elements of the array are the values matching the order and number of ? and the letter flags
     *     It is an exception to have a mismatch in the count of ?, letter, and value
     *     The values can be literals, variables, or expressions
     *     @example  ['iid',4,$b,time()]
     *
     *   for named param notation (like :puppies )
     *     the array is a hash of param information, and the param values can be either a single value or an array
     *     @example   ['what'=>3, 'is'=> ['value'=>'nothing','flag=>'s']]
     *     if the value is not an array, then the flag is assumed to be an s
     *       otherwise if the value is an array , then the keys of value and flag must be there, else an exception will rise
     *
     *     The order of the params do not matter
     *      Each value can be passed as a literal or a variable or expression
     *     @example [:first=>1,'cats'=>$b,'betty_right'=>['flag'=>'d','value'='on tv'] 'another'=>3.1415]
     *
     *   Can pass in null if don't need to bind params
     *   When passing in values, booleans will be converted to 0 or 1, and arrays will be converted to json, and objects will be cast to string value
     *
     * Default is null
     *
     * </p>
     * @param $close integer <p>
     *   these are the following named constants
     *   MYDB::LAST_ID        returns the primary key of an insert operation
     *   MYDB::ROWS_AFFECTED  returns the number of rows affected during an update or delete
     *   MYDB::RESULT_SET     used to get the results back from a select
     *
     *   Please note that the proper close value must be put with the type of sql statement
     *    it will not be an error if the wrong close type is put in, but the return results may be unexpected
     *
     *   anything not MYDB::LAST_ID or MYDB::ROWS_AFFECTED will be MYDB::RESULT_SET
     *   default is  MYDB::RESULT_SET
     * </p>
     * @param null|string $lookupKey <p>
     *  a string to remember the compiled sql statement,
     *  will speed up things a lot if called with same statement multiple times
     *  it can save the step of compiling the statement for later identical calls if the the same sql is used again
     *  when lookup key is set, it will ignore the $sql string if there is already a prepared statement under that key
     *  but when $lookupKey is set to null, will not save the statement for later
     *    the $lookupKey is set to the mysqli object, and not to the MYDB object. Which is important to remember if
     *     creating this class from an existing mysqli object
     *
     *   If $lookupKey is null this function will close the statement if no key if sql is a string
     * </p>
     * @return mixed. <p>
     *   The return is based on what the close param is
     *     MYDB::LAST_ID        returns the primary key of an insert operation
     *     MYDB::ROWS_AFFECTED  returns the number of rows affected during an update or delete
     *     MYDB::RESULT_SET     a regular array of hashes where each hash is a key value pair of column_name:value
     * </p>
     * @throws SQLException if anything goes wrong, including sql errors, bad params, etc
     */
    public function execSQL($sql, $params=null, $close=MYDB::RESULT_SET, $lookupKey = null)
    {

        if (! (is_array($params) || is_null($params) ) ) {
            throw new SQLException("Params need to be an array or null");
        }
        $parameters = [];
        $row = [];
        $mysqli = $this->mysqli;
        $bIsStatement = false;
        //pass in prepared statement or sql
        $bStatementClose = true;
        if (is_object($sql) && get_class($sql) == 'mysqli_stmt') {
            $bIsStatement = true;
            $bStatementClose = false;
        } elseif ($lookupKey) {
            //see if we previously put a statement in this key
            $previousStatement = null;
            if (isset($this->mysqli->statementCache[$lookupKey])) {
                $previousStatement = $this->mysqli->statementCache[$lookupKey];
                $sql = $previousStatement;
                $bIsStatement = true;
                $bStatementClose = false;
            }


        }


        //check to see if params are named
        if (!empty($params) && !(isset($params[0])) && !$bIsStatement) {
            // make sure the string length of $params[0] = length of array $params[1]
            if (strlen($params[0]) != sizeof($params[1])) {
                throw new SQLException("Number of named params is not equal to the number of types");
            }
            $p = $params;
            $codes = '';
            $new_params = [];

            // make array of nil values the size of the sql string, call it c
            $c = array_fill(0,strlen($sql),null);

            // for each key value of p
            //   find the index in the string the key starts at, call it s
            //     c[s] = [key,value,flag]

            foreach($p as $k=>$v) {
                $where = strpos($sql,$k);
                if ($where === false) {
                    throw new SQLException("Could not find named param of [$k] in $sql");
                }

                //if $k is an array then must have flag, value fields, if not array then assume flag is s
                if (is_array($v)) {
                    if (!isset($v['flag'])) {
                        throw new SQLException("Named Param notation: $k subarray must have flag field set");
                    }

                    if (!isset($v['value'])) {
                        throw new SQLException("Named Param notation: $k subarray must have value field set");
                    }

                    $flag = $v['flag'];
                    $value = $v['value'];
                } else {
                    $flag = 's';
                    $value = $v;
                }
                $c[$where] = ['key'=>$k,'value'=>$value,'flag'=>$flag];
            }

            // foreach c value , in order, that is not null
            // replace the value in the string with a ?
            // push value onto new_params

            foreach ($c as $v) {
                if (is_null($v)) {continue;}
                $named_of_param = $v['key'];
                $value_of_param = $v['value'];
                $flag_of_param = $v['flag'];
                $codes .= $flag_of_param;
                $sql = str_replace(':'.$named_of_param,' ? ',$sql);
                array_push($new_params,$value_of_param);
            }

            array_unshift($new_params,$codes);
            $params = $new_params;

        }

        $stmt = $bIsStatement ? $sql :

            $mysqli->prepare($sql);
        if (!$stmt) {
            self::throwSQLErrorWithHtml("Could not prepare statement", $this->mysqli,$sql);
        }

        //if lookup key, then put this statement in the key
        if ($lookupKey) {
            $this->mysqli->statementCache[$lookupKey] = $stmt;
            $bStatementClose = false;
        }


        try {
            //make sure all the params are converted from php data types: arrays and booleans
            for($i = 1; $i < count($params); $i++) {
                $params[$i] = JsonHelper::toStringAgnostic($params[$i]);
            }
        } catch (\Exception $e) {
            throw new SQLException($e->getMessage() . " : Index was $i");
        }



        //only call bind if $params is not empty
        if (!empty($params)) {
            if (!call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params))) {

                self::throwSQLStatement("Could not bind param ", $stmt,$sql);
            }
        }


        $res = $stmt->execute();

        if (!$res) {
            self::throwSQLStatement("could not execute statement ", $stmt,$sql);
        }

        if ($close === MYDB::ROWS_AFFECTED) {
            $retVal = $stmt->affected_rows;
            //$retVal = $stmt->num_rows();
            if ($bStatementClose) {
                $stmt->close();
            }

            return $retVal;

        } elseif ($close === MYDB::LAST_ID) {
            $retVal = $mysqli->insert_id;
            if ($bStatementClose) $stmt->close();
            return $retVal;
        } else {
            $results = false;
            $meta = $stmt->result_metadata();

            if (!$meta) {
                return array();
            }

            while ($field = $meta->fetch_field()) {
                $parameters[] = &$row[$field->name];
            }


            if (!call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters))) {
                self::throwSQLStatement("Could not bind result  ", $stmt);
            }
            while ($stmt->fetch()) {
                $x = array();
                foreach ($row as $key => $val) {
                    $x[$key] = $val;
                }
                $results[] = $x;
            }

            $result = $results;
        }

        if ($bStatementClose) $stmt->close();


        return $result;
    }

    private function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * Ease of use function to update a single row in a table
     * @param $table string name of the table
     * @param $id  mixed primary key value of the table
     * @param $fields array column_name: value hash
     * @param string $pk_name, if the primary key is not called id, then put the name of the primary key here
     * @return integer the number of rows updated, will be 0 or 1
     * @throws SQLException when the underlying table or field does not exist
     */
    public function update($table, $id, array  $fields, $pk_name = 'id')
    {

        $set = '';
        $x = 1;
        $flags = '';
        $values = [];

        foreach ($fields as $name => $value) {

            $set .= "{$name} = ?";
            if ($x < count($fields)) {
                $set .= ', ';
            }
            $x++;
            $flags .= 's';
            array_push($values, $value);
        }

        $flags .= 's';
        array_push($values, $id);

        array_unshift($values, $flags);
        $sql = "UPDATE {$table} SET {$set} WHERE $pk_name = ?";

        return $this->execSQL($sql, $values, MYDB::ROWS_AFFECTED);
    }

    /**
     * ease of use function to insert array
     * @param $table string the name of the table inserting into
     * @param $fields array, or array of arrays, <p>
     *      if single array, then its an array of  key value pairs of each column to use in the insert, column_name=>value
     *      if array of arrays, then its an array whose values are each an array described above
     * @return array|integer <p>
     *    if the fields are
     * @throws SQLException
     */
    public function insert($table, $fields)
    {
        if (!is_array($fields) || empty($fields)) { throw new SQLException("Insert Function in database class needs frields to be a populated array");}

        if (isset($fields[0]) && is_array($fields[0])) {
            $ret = [];
            foreach ($fields as $f) {
                $ret[]= $this->insert($table,$f);
            }
            return $ret;
        }
        $sql_columns = [];
        $sql_values = [];

        $flags = '';
        $values = [];

        foreach ($fields as $name => $value) {

            $sql_columns[] = $name;
            $sql_values[] = '?';
            $values[] = $value;
            $flags .= 's';

        }
        $columns_string = implode(',',$sql_columns);
        $values_string = implode(',',$sql_values);

        array_unshift($values, $flags);
        $sql = "INSERT INTO $table($columns_string) VALUES ($values_string) ;";

        return $this->execSQL($sql, $values, MYDB::LAST_ID);
    }

    /**
     * Internal function to connect to database
     * @param $configs array <p>
     *   has the following values which must be supplied
     *     username : database username
     *     password : for the username
     *     host:      the url, which can be localhost or an ip, or url
     *     database_name: name of the database
     *  optional values:
     *    character_set: if not supplied the character set will be utf8
     * </p>
     * @return object mysqli
     * @throws SQLException if connection fails
     */
    protected static function getMySqliDatabase($configs)
    {

        $MySQLUsername = $configs['username'];
        $MySQLPassword = $configs['password'];
        $sqladdress = $configs['host'];
        $dbname = $configs['database_name'];
        $charset = 'utf8';
        if (isset($configs['character_set']) && $configs['character_set']) {
            $charset = $configs['character_set'];
        }

        $mysqli = mysqli_connect($sqladdress, $MySQLUsername, $MySQLPassword, $dbname);
        if (!$mysqli) {
            throw new SQLException(sprintf("Connect failed:<br> %s\n", mysqli_connect_error()));
        }

        //set for unicode

        $mysqli->query("SET CHARACTER SET $charset");
        $mysqli->set_charset($charset);
        return $mysqli;

    }


    /**
     * Helper function to throw errors with messages from the mysqli object
     * @param object|\mysqli $mysqli  cannot be null
     * @param $sql string additional information, usually here the sql string that caused the issue
     * @throws SQLException every time
     */
    public static function throwSQLError($mysqli, $sql)
    {
        $help = sprintf("SQL Error\n %s\nSQL is:\n %s", mysqli_error($mysqli), $sql);
        throw new SQLException($help);
    }

    /**
     * helper method to throw errors with messages from the mysqli object, has html line breaks
     * @param $msg1 string additional information, usually here the sql string that caused the issue
     * @param object|\mysqli $mysqli  object cannot be null
     * @param string|null default null information to put on next line
     * @throws SQLException every time
     */
    public static function throwSQLErrorWithHtml($msg1, $mysqli, $msg2 = null)
    {
        if ($msg2) {
            throw new SQLException(sprintf("%s:<br> %s<br>    error:<br>%s<br>", $msg1, $msg2, mysqli_error($mysqli)));
        } else {
            throw new SQLException(sprintf("%s:<br>error:<br>%s<br>", $msg1, mysqli_error($mysqli)));
        }
    }

//

    /**
     * Helper function to throw errors with messages from the mysqli_stmt object , has html line breaks
     * @param string $msg1  additional information, usually here the sql string that caused the issue
     * @param object|\mysqli_stmt $stmt  mysqli_stmt
     * @param string|null $msg2 additional information on the next line (optional)
     * @throws SQLException every time
     */
    public static function throwSQLStatement($msg1, $stmt, $msg2 = null)
    {
        if ($msg2) {
            throw new SQLException(sprintf("error:<br>\n%s<br>\n%s<br>\n%s<br>", $msg1,  mysqli_stmt_error($stmt),$msg2));
        } else {
            throw new SQLException(sprintf("%s:<br>error:<br>%s<br>", $msg1, mysqli_stmt_error($stmt)));
        }

    }

    /**
     * helper function to execute a mysqli_stmt
     * @link http://php.net/manual/en/mysqli-stmt.execute.php
     * @param object|\mysqli_stmt $state statement object
     * @return void
     * @throws SQLException if something goes wrong
     */
    public static function executeStatement($state)
    {
        if ((!isset($state)) || (empty($state))) {
            throw new SQLException("statement was null or empty");
        }
        $res = mysqli_stmt_execute($state);
        if (!$res) {
            self::throwSQLStatement("could not execute statement", $state);
        }
    }

    /**
     * Debugging function to print out the character set information of a connection with html line breaks
     * this method will print things directly out
     * @param $mysqli
     * @throws SQLException
     */
    public static function printDatabaseLanguageVariables($mysqli)
    {
        $sql = "show variables like 'character_set%'";

        $res = self::staticExecute($sql, $mysqli);


        // GOING THROUGH THE DATA
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $name = $row['Variable_name'];
                $value = $row['Value'];
                print "[$name]=>[$value]<br>";
            }
        } else {
            echo 'NO RESULTS';
        }
    }


    /**
     * Helper method to run mysqli_query and throw exception if wrong
     * @param string $query the sql string to execute
     * @param object|\mysqli database object
     * @return mixed the result of the query
     * @throws SQLException if anything goes wrong
     */
    public static function staticExecute($query,  $mysqli)
    {
        if ((!isset($query)) || (empty($query))) {
            throw new SQLException("sql was null or empty");
        }

        $res = mysqli_query($mysqli, $query);
        if ($res != true) {
            throw new SQLException(sprintf("Could not run execute:<br> %s<br>    sql:<br>%s<br>", mysqli_error($mysqli), $query));
        }
        return $res;
    }


    /**
     * returns true if the object can be interpreted as a whole number
     * will return false if can be interpreted as a fraction or only a non number
     * @param mixed $var
     * @return bool
     */
    public static function is_whole_number($var)
    {
        return (is_numeric($var) && (intval($var) == floatval($var)));
    }

    /**
     * Helper function that makes sure that the input can only be a whole number and nothing else
     * @param mixed $number
     * @return bool true if only a whole number, false if anything else
     */
    public static function isNumberClean($number)
    {
        if (!self::is_whole_number($number)) {
            return false;
        }
        $oldNumber = $number;
        $number = preg_replace('/[^0-9-]/', '', $number);
        if ($oldNumber != $number) {
            return false;
        }
        return true;
    }


    /**
     * Helper function that will throw an exception if the number is not indisputably a whole integer
     * @param mixed $number
     * @return string value of the input
     * @throws SQLDataException if not a whole integer only
     */
    public static function cleanNumber($number)
    {

        $what = self::isNumberClean($number);
        if (!$what) {
            throw new SQLDataException("not a whole number: $number" );
        }

        return strval($number);
    }



    /**
     * Helper function, if the input evaluates to an empty string after being trimmed,
     * then returns null else returns the stringified input
     * @param mixed $s
     * @return null|string
     */
    public static function stringOrNull($s) {
        $s = trim((string)$s);
        if (empty($s)) {
            return null;
        } else {
            return strval($s);
        }
    }

    /**
     * Helper method to sanitize sql string
     * will make sure that magic quotes is not an issue
     * can escape a string for mysql input
     * and will strip out html
     * @param string $s
     * @param boolean $b_strip_tags default false, if true then tags will be stripped out
     * @param object|null optional mysqli object
     * @return string the modified input
     * @throws SQLException if invalid mysqli param and its not null
     */
    //for prepared statements
    public static function sanitizeString($s, $b_strip_tags = false, $mysqli = null)
    {
        $s = strval($s);
        if (empty($s)) {
            $s = '';
        }
        if (get_magic_quotes_gpc())//magic quotes on
        {
            $s = stripslashes($s);
        }

        if ($b_strip_tags) {
            $s = strip_tags($s);
        }


        if ($mysqli) {
            $mem = mysqli_real_escape_string($mysqli, $s);
            if (is_null($mem) && !is_null($s)) {
                self:self::throwSQLError($mysqli,"Seems like no valid mysqli connection with sanitize string");
            }
        }
        return $s;

    }



    /**
     * Converts a timestamp to a string that has the UTC time
     * @param $ts
     * @throws SQLDataException
     * @return string
     */
    public static function timestampToUTCString($ts) {
       // 'YYYY-MM-DD HH:MM:SS'
        $tsc = self::cleanNumber($ts);
        $what =  gmdate('Y-m-d G:i:s',$tsc);
        if (!$what) {
            throw  new SQLDataException("$ts cannot be converted to a date time");
        }
        return $what;
    }

    /**
     * Convenience function to convert array of strings to a single comma delimited string with each thing escaped and quoted
     * @param $arr array
     * @return string comma delimited and escaped string
     */
    public  function arrayToQuotedString($arr) {
        $cloned = [];
        foreach ($arr as $a) {
            $cloned[] = "'".$this->cleanString($a)."'";
        }
        return implode(',',$cloned);
    }


}

