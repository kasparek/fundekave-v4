<?php
$ff = new FFile();
$list = $ff->fileList('image');
print_r($list);

echo '<hr>';
$options = explode(",",ImageConfig::$sideOptions);

foreach($list as $item) {
	list($w,$h) = explode("x",$item);
	$delete = false;
	if(!in_array($w,$options)) $delete=true;
	if(!in_array($h,$options)) $delete=true;
	if(empty($w)) $delete=true;
	if(empty($h)) $delete=true;
	if($delete) {
		$ff->rm_recursive('image/'.$item);
		echo 'deleted: '.$item.'<br>';
	}
}

echo '<hr>';

$list = $ff->fileList('image');
print_r($list);