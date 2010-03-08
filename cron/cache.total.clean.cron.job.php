<?php

$dirs = array(
'/home/www/fundekave.net/fdk_v5/tmp/lite/'
,'/home/www/fundekave.net/fdk_v5/tmp/fuup_chunks/'
,'/home/www/fundekave.net/fdk_v5/tmp/'
);

foreach($dirs as $dir) {

$handle=opendir($dir);

while (false!==($file = readdir($handle))){
  if($file!='.' && $file!='..' && !is_dir($dir.$file)) {
    unlink($dir.$file);
  }        
}

}