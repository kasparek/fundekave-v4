<?php
//unblock system
session_write_close();

$truncate = array( WEBROOT.'tmp/fuup_chunks' ,WEBROOT.'tmp', FConf::get('settings','tmp') );
$recursive = array( FConf::get('settings','tmp').FConf::get('settings','cache_path') );

$counter = 0;
foreach($truncate as $dir) {
$handle=opendir($dir);
while (false!==($file = readdir($handle))) 
	if($file!='.' && $file!='..') { 
		$filename = $dir.'/'.$file;
		if(!is_dir($filename)) {
			echo 'Deleting: '.$filename." <br>\n";
			unlink($dir.'/'.$file);
			$counter++;
		}
	}
}
echo "Deleted files: ".$counter."<br>\n";

$ff = new FFile();
foreach($recursive as $dir) {
	$dir = rtrim($dir, "/");
	if($ff->file_exists($dir)) {
		$ff->rename($dir, $dir.'_'.date("U").'_delete_');
		$ff->makeDir($dir);
		echo "Recursive flushed: ".$dir."<br>\n";
	}
}