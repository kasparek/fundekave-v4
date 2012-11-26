<?php
$ff = new FFile();
$list = $ff->fileList('image');
print_r($list);

echo '<hr>';
$options = explode(ImageConfig::$sideOptions);

foreach($list as $item) {
  list($w,$h) = explode("x",$item);
  if(!in_array($w,$options) {
    //delete folder
    $ff->rm_recursive('image/'.$item);
    echo 'deleted: '.$item.'<br>';
  }
}

echo '<hr>';

$list = $ff->fileList('image');
print_r($list);