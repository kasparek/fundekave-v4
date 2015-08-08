<?php
//unblock system
session_write_close();

$dir = FConf::get('settings','tmp');

$counter = 0;
$deleted = '';
$handle=opendir($dir);
$ff = new FFile();
while (false!==($file = readdir($handle))) {
	if($file!='.' && $file!='..') { 
		if(is_dir($dir.'/'.$file)) {
			if(strpos($file,'delete_')!==false) {
				$filename = $dir.'/'.$file;
				echo 'Deleting: '.$dir.'/'.$file." <br>\n";
				$ff->rm_recursive($filename);	
				$deleted .= $filename . "<br>/n";
				$counter++;
			}
		}
	}
}
echo $deleted;
echo "Deleted files: ".$counter."<br>\n";