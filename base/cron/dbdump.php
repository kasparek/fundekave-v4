<?php
//unblock system

$host = FConf::get('db','hostspec');
$user = FConf::get('db','username');
$pass = FConf::get('db','password');
$dbname = FConf::get('db','database');

$baseDir = 'tmp/dbdump';
$today = '/'.date('Y-m-d');
$dumpdir = $baseDir.$today;
if(!is_dir($baseDir)) mkdir($baseDir);
if(!is_dir($dumpdir)) mkdir($dumpdir);

$dumpdir .= '/';

//---PHP DUMP style - slower but no need of command line
session_write_close();

$dbdump = new DBDump();
$dbdump->srcServer = $host;
$dbdump->srcUser = $user;
$dbdump->srcPass = $pass;
$dbdump->srcDB = $dbname;
$dbdump->srcDumpPath = $dbdump->tgtDumpPath = $dumpdir;
echo 'Current dir: '.getcwd()."<br>\n";
echo 'Table are being dumped into: '.$dbdump->srcDumpPath."<br>\n";
$dbdump->importing = false;
$dbdump->connect();
$dbdump->importing = true;
$dbdump->getTables();
$dbdump->importing = false;
$dbdump->dumpAll(false);


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