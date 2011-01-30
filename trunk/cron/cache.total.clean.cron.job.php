<?php
$truncate = array( WEBROOT.'tmp/fuup_chunks' ,WEBROOT.'tmp' );
$recursive = array( FConf::get('settings','cache_path') );

$counter = 0;
foreach($truncate as $dir) {
$handle=opendir($dir);
while (false!==($file = readdir($handle))) 
	if($file!='.' && $file!='..' && !is_dir($dir.'/'.$file)) { 
		unlink($dir.'/'.$file);
		$counter++;
	}
}
echo "deleted files: ".$counter."<br>\n";

$ff = new FFile();
foreach($recursive as $dir) {
$ff->rm_recursive($dir);
$ff->makeDir($dir);
echo "recursive flushed: ".$dir."<br>\n";
}
echo "deleted files: ".$ff->numModified."<br>\n";