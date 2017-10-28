<?php
/**
 * User: techbeast
 * Date: 24/12/15
 * Time: 10:39 PM
 * Description: Oriented class for DB operations
 */

/*-- defining static objects to be used --*/
define("OBJECT","OBJECT");
define("ARRAY_A","ARRAY_A");
define("ARRAY_N","ARRAY_N");

class DB {

    var $trace = false;      // same as $debug_all
    var $debugAll = false;  // same as $trace
    var $showErrors = true;
    var $numOfQueries = 0;
    var $lastQuery;
    var $colInfo;
    var $debugCalled;
    var $vardumpCalled;
    /*var $functionCalled;
    var $result;*/

    /*-- constructor to connect to the DB as and when called --*/
    public function __construct($dbHost, $dbUser, $dbPassword, $dbName) {
        //create connection
        $this->dbh = mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName);
        if(!$this->dbh){
            $this->print_error("<strong>Error establishing a database connection!</strong>
                                <ol>
                                    <li>Are you sure that the database server is running?</li>
                                    <li>Are you sure that you have typed the correct hostname?</li>
                                    <li>Are you sure you have the correct user/password?</li>
                                </ol>");
        }
    }

    /*function __destruct() {
        //close connection
        mysqli_close($this->dbh);
    }*/


    /*-- to select / switch database --*/
    function select_db($databaseName) {
        if (!@mysqli_select_db($databaseName,$this->dbh)) {
            $this->print_error("<strong>Error selecting database <span style='text-decoration: underline'>$databaseName</span> ..!</strong>
                                <ol>
                                    <li>Are you sure it exists?</li>
                                    <li>Are you sure there is a valid database connection?</li>
                                </ol>");
        }
    }


    /*-- format a string correctly for safe insert under all PHP conditions --*/
    function escape($string) {
        return mysqli_escape_string(stripslashes($string));
    }


    /*-- function to toggle error display modes i.e. error reporting --*/
    function error_reporting($mode = 1){
        $this->showErrors = ($mode) ? true : false;
    }


    /*-- clear cached query results --*/
    function flush() {
        $this->lastResult = null;
        $this->colInfo = null;
        $this->lastQuery = null;
    }


    /*-- query() for insert, update, delete --*/
    function query($query) {

        //trim out extra spaces
        $query = trim($query);

        //initialise return
        $returnValue = 0;

        //flush cached values..
        $this->flush();

        //log how the function was called
        $this->functionCalled = "\$db->query(\"$query\")";

        //keep track of the last query for debug..
        $this->lastQuery = $query;

        //execute query
        $this->result = mysqli_query($this->dbh, $query);
        $this->numOfQueries++;

        //print if any error
        if (mysqli_error($this->dbh)) {
            $this->print_error();
            return false;
        }

        //check the pattern of query and return the result accordingly
        if (preg_match("/^(insert|delete|update|replace)\s+/i",$query)) {

            $this->rowsAffected = mysqli_affected_rows($this->dbh);

            //store the last insert id
            if (preg_match("/^(insert|replace)\s+/i",$query)) {
                return $this->insertId = mysqli_insert_id($this->dbh);
            }

            //return the no. of rows affected
            $returnValue = $this->rowsAffected;
        }
        //else select query
        else {
            //counter for column info
            $i=0;
            while ($i < @mysqli_num_fields($this->result)) {
                $this->colInfo[$i] = @mysqli_fetch_field($this->result);
                $i++;
            }

            //counter to store the no. of rows
            $rowCounter=0;
            while ($row = @mysqli_fetch_object($this->result)) {
                //store the results in main array
                $this->lastResult[$rowCounter] = $row;
                $rowCounter++;
            }

            @mysqli_free_result($this->result);

            //log total no. of rows returned
            $this->totalRows = $rowCounter;

            // Return number of rows selected
            $returnValue = $this->totalRows;
        }

        //debug all queries
        $this->trace || $this->debugAll ? $this->debug() : null ;

        return $returnValue;
    }


    /*-- function to get single value returned from the result --*/
    function get_var($query = null, $x=0, $y=0) {

        //log the function called
        $this->functionCalled = "\$db->getVar(\"$query\",$x,$y)";

        // If there is a query then perform it if not then use cached results..
        if ( $query )
        {
            $this->query($query);
        }

        // Extract var out of cached results based x,y vals
        if ( $this->lastResult[$y] )
        {
            $values = array_values(get_object_vars($this->lastResult[$y]));
        }

        // If there is a value return it else return null
        return (isset($values[$x]) && $values[$x]!=='')?$values[$x]:null;
    }


    /*-- function to get first row of result set --*/
    function get_row($query = null, $output = OBJECT, $y=0) {

        //log the function called
        $this->functionCalled = "\$db->getRow(\"$query\",$output,$y)";

        //check query and execute
        if ($query) {
            $this->query($query);
        }

        //if output is an object then return object using the row offset..
        if ($output == OBJECT) {
            return $this->lastResult[$y] ? $this->lastResult[$y] : null;
        }
        //if output is an associative array then return row as such..
        elseif ( $output == ARRAY_A) {
            return $this->lastResult[$y]?get_object_vars($this->lastResult[$y]):null;
        }
        //if output is an numerical array then return row as such..
        elseif ( $output == ARRAY_N ) {
            return $this->lastResult[$y]?array_values(get_object_vars($this->lastResult[$y])):null;
        }
        //if invalid output type was specified..
        else {
            $this->print_error(" \$db->getRow(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
        }

    }


    /*-- function to get 1 column from the cached result set based in X index --*/
    function get_col($query=null, $x=0) {
        if ($query) {
            $this->query($query);
        }

        $newArray = array();

        //extract the column values
        for ($i=0; $i < count($this->lastResult); $i++) {
            $newArray[$i] = $this->get_var(null, $x, $i);
        }

        return $newArray;
    }


    /*-- return the result from the query passed --*/
    function get_results($query=null, $output = OBJECT) {

        //log the function called
        $this->functionCalled = "\$db->get_results(\"$query\", $output)";

        //if there is a query then perform it if not then use cached results..
        if ($query) {
            $this->query($query);
        }

        $newArray= array();

        //send back array of objects (each row as an object)
        if ($output == OBJECT) {
            return $this->lastResult;
        }
        elseif ($output == ARRAY_A || $output == ARRAY_N ) {
            if ( $this->lastResult ) {

                $counter = 0;
                foreach($this->lastResult as $row) {

                    $newArray[$counter] = get_object_vars($row);

                    if ( $output == ARRAY_N ) {
                        $newArray[$counter] = array_values($newArray[$counter]);
                    }

                    $counter++;
                }

                return $newArray;
            }
            else {
                return null;
            }
        }
    }


    /*-- function to get column meta data info pertaining to the last query --*/
    function get_col_info($infoType = "name", $colOffset = -1) {
        $newArray= array();

        if($this->colInfo) {
            if ($colOffset == -1) {
                $counter=0;
                foreach($this->colInfo as $col )
                {
                    $newArray[$counter] = $col->{$infoType};
                    $counter++;
                }
                return $newArray;
            }
            else {
                return $this->colInfo[$colOffset]->{$infoType};
            }
        }
    }


    /*-- dumps the contents of any input variable to screen in formatted --*/
    function var_dump($mixed = '') {
        echo "<p>
                <table>
                    <tr>
                        <td bgcolor='#ffffff'>
                            <blockquote style='color: #000090'>
                                <pre>";

        if (!$this->vardumpCalled) {
            echo "<span style='color: #800080'><strong>Variable Dump..</strong></span><br><br>";
        }

        $varType = gettype($mixed);
        print_r(($mixed?$mixed:"<span style='color: red'>No Value / False</span>"));
        echo "<br><br><strong>Type:</strong> " .ucfirst($varType) . "<br>";
        echo "<strong>Last Query</strong> [$this->numOfQueries]<b>:</b> ".($this->lastQuery ? $this->lastQuery : "NULL")."<br>";
        echo "<strong>Last Function Call:</strong> " . ($this->functionCalled ? $this->functionCalled : "None")."<br>";
        echo "<strong>Last Rows Returned:</strong> ".count($this->lastResult)."<br>";
        echo  "                </pre>
                            </blockquote>
                        </td>
                   </tr>
              </table>";

        $this->vardumpCalled = true;
    }



    /*-- displays the last query string that was sent to the database & a table listing results (if there were any) (abstracted into a separate file to save server overhead) --*/
    function debug() {

        echo "<blockquote>";

        //only show SQL credits once..
        if (!$this->debugCalled) {
            echo "<span style='color: #800080'><strong>Debug..</strong></span><br>";
        }

        echo "<span style='color: #000099'><strong>Query</strong> [$this->numOfQueries] <strong>--</strong> ";
        echo "[<span style='color: #000000'><strong>$this->lastQuery</strong></span>]<br>";

        echo "<span style='color: #000099'><strong>Query Result..</strong></span>";
        echo "<blockquote>";

        if ($this->colInfo) {
            // Results top rows
            echo "<table cellpadding='5' cellspacing='0' style='background: #c0c0c0'>
                    <tr style='background: #eeeeee'>
                        <td valign='bottom' style='color: #555599; font-weight: bold'>(row)</td>";

            for ($i=0; $i < count($this->colInfo); $i++) {
                echo "<td align='left' valign='top' style='color: #555599'>
                        <!--{$this->colInfo[$i]->type} {$this->colInfo[$i]->max_length}<br>-->
                        <strong>{$this->colInfo[$i]->name}</strong>
                      </td>";
            }

            echo "</tr>";


            //print main result
            if ($this->lastResult) {

                $i=0;
                foreach ($this->get_results(null, ARRAY_N) as $row) {
                    $i++;
                    echo "<tr style='background: #ffffff'>
                            <td style='background: #eeeeee; color: #555599'>$i</td>";

                    foreach ($row as $item) {
                        echo "<td>$item</td>";
                    }

                    echo "</tr>";
                }

            }
            //if last result
            else {
                echo "<tr style='background: #ffffff'><td colspan='".(count($this->colInfo) + 1)."'>No Results</td></tr>";
            }

            echo "</table>";

        }//EOC if colInfo
        else {
            echo "No Results";
        }

        echo "</blockquote></blockquote>";

        $this->debugCalled = true;
    }


    /*-- print sql errors --*/
    function print_error($errorString = "") {

        //create global error variable where all errors are dumped
        global $SQL_ERROR;

        // If no special error string then use mysqli default..
        if ( !$errorString ) {
            $errorString = mysqli_error($this->dbh);
            $errorNo = mysqli_errno($this->dbh);
        }

        //log this error to the global array..
        $SQL_ERROR[] = array("query" => $this->lastQuery,
            "error_str"  => $errorString,
            "error_no"   => $errorNo);

        //check if error output turned on
        if ($this->showErrors){
            //display error
            print "<blockquote style='color: #ff0000'>";
            print "<strong>SQL / DB Error -- </strong> ";
            print "[<span style='color: #000077'>$errorString</>]";
            print "</blockquote>";
        }
        else {
            return false;
        }
    }
}