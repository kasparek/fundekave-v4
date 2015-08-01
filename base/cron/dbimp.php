<?php
set_time_limit(3600);
//unblock system

$host = FConf::get('db','hostspec');
$user = FConf::get('db','username');
$pass = FConf::get('db','password');
$dbname = FConf::get('db','database');

$baseDir = 'tmp/dbdump';
$today = '/'.date('Y-m-d');

$today = '/2015-07-18';

$dumpdir = $baseDir.$today;
if(!is_dir($baseDir)) mkdir($baseDir);
if(!is_dir($dumpdir)) mkdir($dumpdir);

$dumpdir .= '/';

//---PHP DUMP style - slower but no need of command line
session_write_close();

$dbdump = new DBDump();
$dbdump->tgtServer = $host;
$dbdump->tgtUser = $user;
$dbdump->tgtPass = $pass;
$dbdump->tgtDB = $dbname;
$dbdump->srcDumpPath = $dbdump->tgtDumpPath = $dumpdir;
echo 'Current dir: '.getcwd()."<br>\n";
echo 'Table are being loaded from: '.$dbdump->srcDumpPath."<br>\n";
$dbdump->importAll();