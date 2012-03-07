<?php
$ff = new FFile();
$list = $ff->fileList('image');
print_r($list);

echo '<hr>';

foreach($list as $item) {
  list($w,$h) = explode("x",$item);
  
  if($w!=170)
  if(round($w/10)!=$w/10 || ($w>100 && round($w/100)!=$w/100)) {
    //delete folder
    $ff->rm_recursive('image/'.$item);
    echo 'deleted: '.$item.'<br>';
  }
}

echo '<hr>';

$list = $ff->fileList('image');
print_r($list);