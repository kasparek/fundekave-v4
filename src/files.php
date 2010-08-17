<?php
/*
sleep(rand(5,10)/10);
$r = rand(1,5);
if($r==2) {
echo 'died';
die();
}
/**/

$seq = (int)  $_POST['seq'];
$total = (int)  $_POST['total'];
if(!empty($_FILES)) {
	$file = $_FILES['Filedata'];
	$filename = $file["name"];
	move_uploaded_file($file["tmp_name"], 'chunks/chunk-'.$filename.'-'.$seq.'.txt' );

} else {
	$filename = $_POST['filename'];
	$data = $_POST['data'];
	if(!empty($data)) {
  	file_put_contents('chunks/chunk-'.$filename.'-'.$seq.'.txt',$data);
	}
}


$allExists = true; 
for($i=0;$i<$total;$i++) {
if(!file_exists('chunks/chunk-'.$filename.'-'.$i.'.txt'))  {
$allExists = false;
}
}

$targetFile = 'images/'.$filename.'.jpg';
if($allExists === true && !file_exists($targetFile)) {
	//--concat all files
	
	$handleW = fopen($targetFile, "a");
	
	for($i=0;$i<$total;$i++) {
	 $file = 'chunks/chunk-'.$filename.'-'.$i.'.txt';
		//$encData .= trim(file_get_contents($file));
		$handle = fopen($file, "rb");
		fwrite($handleW, fread($handle, filesize($file)-2));
		fclose($handle);
		unlink($file);
	}

  fclose($handleW);
	//file_put_contents('images/'.$filename.'.jpg',base64_decode( $encData ));
	//file_put_contents('images/'.$filename.'.jpg', $encData );
}

echo 1;