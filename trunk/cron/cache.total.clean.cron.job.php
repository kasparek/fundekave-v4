<?php

$dir = '/home/www/fundekave.net/tmp/cache-lite/';

$handle=opendir($dir);

while (false!==($file = readdir($handle))){
  if($file!='.' && $file!='..') {
    unlink($dir.$file);
  }        
}