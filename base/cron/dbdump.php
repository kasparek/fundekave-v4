<?php
//unblock system
session_write_close();

$host = 'localhost';
$user = 'awakecom_db';
$pass = 'rohlicek';
$dbname = 'awakecom_1';

$baseDir = 'tmp/dbdump/';
$today = date('Y-m-d');
$dumpdir = $baseDir.$today;
if(!is_dir($dumpdir)) mkdir($dumpdir);
$dumpdir .= '/';

//export structure
$cmd = 'mysqldump --no-data --host='.$host.' --user='.$user.' --password='.$pass.' --add-drop-table '.$dbname.' | gzip > '.$dumpdir.'db.scheme.sql.gz';
shell_exec($cmd);

$mysqli = new mysqli($host, $user, $pass, $dbname);
if($result = $mysqli->query("SHOW TABLES")) {
    while($row = $result->fetch_row()) {
		$tablename = $row[0];
		if((bool) $tablename) {
			$cmd = 'mysqldump --host='.$host.' --user='.$user.' --password='.$pass.' --add-drop-table '.$dbname.' '.$tablename.' | gzip > '.$dumpdir.'db.'.$tablename.'.sql.gz';
			shell_exec($cmd);		
		}
	}
    $result->close();
}
$mysqli->close();