<?php
//unblock system
session_write_close();

$host = FConf::get('db','hostspec');
$user = FConf::get('db','username');
$pass = FConf::get('db','password');
$dbname = FConf::get('db','database');

$baseDir = 'tmp/dbdump/';
$today = date('Y-m-d');
$dumpdir = $baseDir.$today;
if(!is_dir($dumpdir)) mkdir($dumpdir);
$dumpdir .= '/';

//---PHP DUMP style - slower but no need of command line
$dbdump = new DBDump();
$dbdump->srcServer = $host;
$dbdump->srcUser = $user;
$dbdump->srcPass = $pass;
$dbdump->srcDB = $dbname;
$dbdump->srcDumbPath = $dumpdir;

$dump->dumpAll();

//---COMMAND LINE style - not working at the moment, no permissions
/*
//export structure
$cmd = 'mysqldump --no-data --host='.$host.' --user='.$user.' --password='.$pass.' --add-drop-table '.$dbname.' | gzip > '.$dumpdir.'db.scheme.sql.gz';
shell_exec($cmd);

//content dump
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
*/