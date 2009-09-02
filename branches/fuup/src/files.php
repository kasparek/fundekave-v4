<?php
sleep(rand(5,10)/5);
$r = rand(1,3);
if($r==2) {
echo 'died';
die();
}

$data = $_POST['data'];
$seq = (int)  $_POST['seq'];
$total = (int)  $_POST['total'];
$filename = $_POST['filename'];
if(!empty($data)) {

file_put_contents('chunks/chunk-'.$filename.'-'.$seq.'.txt',$data);
		
}

$allExists = true; 
for($i=0;$i<$total;$i++) {
if(!file_exists('chunks/chunk-'.$filename.'-'.$i.'.txt'))  {
$allExists = false;
}
}


if($allExists === true) {
	//--concat all files
	$encData = '';
	for($i=0;$i<$total;$i++) {
	 $file = 'chunks/chunk-'.$filename.'-'.$i.'.txt';
		$encData .= trim(file_get_contents($file));
		//unlink($file);
	}

	file_put_contents($filename.'.jpg',base64_decode( $encData ));
}

echo 1;