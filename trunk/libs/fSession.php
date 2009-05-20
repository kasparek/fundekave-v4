<?php
/**
 * @file session.php
 * thx to drupal.com
 * User session handling functions.
 */
function sess_open($save_path, $session_name) {
	return 1;
}
function sess_close() {
	return 1;
}
function sess_read($key) {
	$db = FDBConn::getInstance();
	$data=$db->getCol("SELECT session FROM sys_sessions WHERE sid = '".$key."'");
	if(count($data)==0) {
		$db->query("INSERT INTO sys_sessions (sid, hostname, timestamp) values('".$key."', '".getHostIp()."', ".date("U").")");
		$session="";
	} else {
		$session=$data[0];
	}
	return($session);
}
function sess_write($key, $value) {
	$db = FDBConn::getInstance();
	$db->query("UPDATE sys_sessions SET hostname = '".getHostIp()."', session = '".$value."', timestamp = ".Date("U")." WHERE sid = '".$key."'");
	return '';
}
function sess_destroy($key) {
	$db = FDBConn::getInstance();
	$db->query("DELETE FROM sys_sessions WHERE sid = '$key'");
}
function sess_gc($lifetime) {
	$db = FDBConn::getInstance();
	$db->query("DELETE FROM sys_sessions WHERE timestamp < '".(Date("U")-$lifetime)."'");
	return 1;
}
function getHostIp(){
	$ip=$_SERVER["REMOTE_ADDR"]
	."@"
	.((!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))?($_SERVER["HTTP_X_FORWARDED_FOR"]):(''))
	."@"
	.((!empty($_SERVER["HTTP_FORWARDED"]))?($_SERVER["HTTP_FORWARDED"]):(''))
	."@"
	.((!empty($_SERVER["HTTP_CLIENT_IP"]))?($_SERVER["HTTP_CLIENT_IP"]):(''))
	."@"
	.((!empty($_SERVER["X_HTTP_FORWARDED_FOR"]))?($_SERVER["X_HTTP_FORWARDED_FOR"]):(''));
	return $ip;
}