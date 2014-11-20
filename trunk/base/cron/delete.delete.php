<?php
//unblock system
session_write_close();

$dir = WEBROOT.'tmp';

$counter = 0;
$deleted = '';
$handle=opendir($dir);
$ff = new FFile();
while (false!==($file = readdir($handle))) {
	if($file!='.' && $file!='..') { 
		if(is_dir($dir.'/'.$file)) {
			if(strpos($file,'delete_')==0) {
				$filename = $dir.'/'.$file;
				$ff->rm_recursive($dir);	
				$deleted .= $dir . "<br>/n";
				$counter++;
			}
		}
	}
}
echo "deleted files: ".$counter."<br>\n";