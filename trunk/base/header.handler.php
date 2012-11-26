<?php
$c = $_GET['c'];
if(strpos($c,'.jpg')!==false) $contentType = 'image/jpeg';
else if(strpos($c,'.gif')!==false) $contentType = 'image/gif';
else if(strpos($c,'.png')!==false) $contentType = 'image/png';
else if(strpos($c,'.ico')!==false) $contentType = 'image/x-icon';
else if(strpos($c,'.css')!==false) $contentType = 'text/css';
else if(strpos($c,'.js')!==false) $contentType = 'text/javascript';
else {
	FError::write_log('header_handler - UNSPECIFIED TYPE - '.$c);
	exit;
}
$filesize = 0; $dataLastChange = ''; $data = '';
if($_GET['header_handler']=='css' && strpos($c,'/')===false) {
	//compile global css with skin css
	$filename = 'css/global.css';
	$dataLastChange = filemtime($filename);
	$fp = fopen($filename, 'rb');
	$data .= fread($fp,filesize($filename));
	$data = str_replace('url(','url(css/',$data);
	fclose($fp);
	//skin file
	$filename = 'css/skin/'.str_replace('.css','',$c).'/screen.css';
	if(filemtime($filename) < $dataLastChange) $dataLastChange = filemtime($filename);
	$fp = fopen($filename, 'rb');
	$data .= str_replace('url(','url(css/skin/'.str_replace('.css','',$c).'/',fread($fp,filesize($filename)));
	fclose($fp);
	//remove comments
	$data = preg_replace('/\/\*(.*)\*\/\r\n|\n\r/i', '', $data);
	$data = preg_replace('/\s\s+/', ' ', $data);
}
//TODO: odkomentovat az to bude zive
/*
 if($contentType == 'text/javascript') {
	$data = file_get_contents($c);
	$data = preg_replace('/\/\*(.*)\*\/\r\n|\n\r/i', '', $data);
	$data = preg_replace('/\s\s+/', ' ', $data);
	}
	*/
if(empty($data) && !file_exists($c)) {
	FError::write_log('header_handler - FILE NOT EXISTS - '.$c);
	exit;
}
header('Content-Type: '.$contentType);
header("Cache-control: max-age=290304000, public");
header("Last-Modified: " . date(DATE_ATOM,($dataLastChange==''?filemtime($c):$dataLastChange)));
header("Expires: ".gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+31536000));
if(empty($data)) {
	$fp = fopen($c, 'rb');
	fpassthru($fp);
	fclose($fp);
} else {
	echo $data;
}
exit;