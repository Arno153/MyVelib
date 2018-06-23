<?php

include "./../inc/mysql.inc.php";
echo "DB Backup Begin<br>";
backupDatabaseTables("velib_");
echo "DB Backup End";


/**
 * @function    backupDatabaseTables
 * @author      CodexWorld
 * @link        http://www.codexworld.com
 * @usage       Backup database tables and save in SQL file
 */
function backupDatabaseTables($tables = '*'){
    //connect & select the database
	$db = mysqlConnect();
	
	if ($db->connect_error) {
		die('Erreur de connexion (' . $mysqli->connect_errno . ') '
				. $mysqli->connect_error);
	}
	
	$return = "";
	
    //get all of the tables
    if($tables == '*'){
        $tables = array();
        $result = $db->query("show TABLES");
        while($row = $result->fetch_row()){
            $tables[] = $row[0];
        }
    }else{
        $result = $db->query("show TABLES like '$tables%' ");
		$tables = array();
        while($row = $result->fetch_row()){
            $tables[] = $row[0];
        }		
    }
	
    //loop through the tables
    foreach($tables as $table){
		//echo "table: ".$table."<br>";
        $result = $db->query("SELECT * FROM $table");
        $numColumns = $result->field_count;

        $return .= "DROP TABLE $table;";

        $result2 = $db->query("SHOW CREATE TABLE $table");
        $row2 = $result2->fetch_row(); 

        $return .= "\n\n".$row2[1].";\n\n";

        for($i = 0; $i < $numColumns; $i++){
            while($row = $result->fetch_row()){
                $return .= "INSERT INTO $table VALUES(";

                for($j=0; $j < $numColumns; $j++){
                    $row[$j] = addslashes($row[$j]);
					
                    $row[$j] = preg_replace("/\n/","\\n",$row[$j]);

                    if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
                    if ($j < ($numColumns-1)) { $return.= ','; }
                }
                $return .= ");\n";

            }
        }

        $return .= "\n\n\n";
		
    }

    //save file
    $handle = fopen('./../backup_files/db-backup-'.time().'.sql','w+');
    fwrite($handle,$return);
    fclose($handle);
	
}