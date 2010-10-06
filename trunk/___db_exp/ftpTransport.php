<?php
class FTPTransport {
	var $sourceFolder = '/home/www/fundekave.net/fdk_v5/tmp/dump/';
	var $targetFolder = '/httpdocs/tmp/dump/';

	var $recursive = false;

	var $targetServer = 'ftp.awake33.com';
	var $targetUsername = '';
	var $targetPassword = '';

	var $conn;
	
	function __construct($src='',$tgt='',$recursive=false) {
		$this->sourceFolder = $src;
		$this->targetFolder = $tgt;
		$this->recursive = $recursive;
	}

	function connect() {
		// set up basic connection
		$this->conn = ftp_connect($this->targetServer);
		// login with username and password
		$login_result = ftp_login($this->conn, $this->targetUsername, $this->targetPassword); 
		// check connection
		if((!$this->conn) || (!$login_result)) { 
			echo "FTP connection has failed!";
			echo "Attempted to connect to $this->targetServer for user $this->targetUsername"; 
			exit; 
		} else {
			echo "Connected to $this->targetServer, for user $this->targetUsername";
		}
			if(!empty($this->targetFolder)) ftp_chdir($this->conn,$this->targetFolder);
	}

	function createFolder( $folderName ) {
		echo '<strong>Create Directory:: '.$file."</strong><br>\n";
		ftp_mkdir($this->conn, $folderName);
		ftp_chdir($this->conn, $folderName);
		ftp_chmod($this->conn, 0777, $folderName);
	}
	function copyfile($src,$tgt) {
	ftp_put($this->conn, $tgt, $src, FTP_BINARY);
	ftp_chmod($this->conn, 0777, $file);
	}
	function copy( $dir='' ) {
		$filaArr = scandir($this->sourceFolder.$dir);
		foreach($filaArr as $file) {
			if($file!='.' && $file!='..') { 
				if(!is_dir($this->sourceFolder.$dir.'/'.$file)) {
					// upload the file
					if(ftp_size($this->conn, $file)>0) { 
						//echo 'File Exists :: '.$thisdir.'::'.$file.'<br>';
					} else {
						$upload = ftp_put($this->conn, $file, $this->sourceFolder.$dir."/".$file, FTP_BINARY);
						ftp_chmod($this->conn, 0777, $file);
						if (!$upload) echo "FTP upload has failed! :: ".$this->sourceFolder.$dir."/".$file."<br />\n";
						else echo "Uploaded :: $thisdir :: $file<br>\n";
					}
				} else {
					if($this->recursive===true) {
						$this->createFolder( $file );
						$this->copy($dir.'/'.$file);
					}
				}
			}
		}
		if($dir!='') ftp_cdup($this->conn);
	}
	function close() {
		ftp_close($this->conn);
	}	 
}