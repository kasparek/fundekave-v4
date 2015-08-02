<?php
//unblock system
session_write_close();

$dir = WEBROOT . 'tmp';

$counter = 0;
$deleted = '';
$handle=opendir($dir);
$ff = new FFile();
while (false!==($file = readdir($handle))) {
	if($file!='.' && $file!='..') { 
		if(is_dir($dir.'/'.$file)) {
			echo $dir.'/'.$file." <br>\n";
			if(strpos($file,'delete_')==0) {
				$filename = $dir.'/'.$file;
				echo 'Deleting';
				$ff->rm_recursive($filename);	
				$deleted .= $filename . "<br>/n";
				$counter++;
			}
		}
	}
}
echo $deleted;
echo "Deleted files: ".$counter."<br>\n";