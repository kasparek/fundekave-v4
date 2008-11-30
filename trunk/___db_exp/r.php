<?php
set_time_limit(300);

function setMod($directory) {
  $arr = scandir($directory);
  foreach($arr as $dir) {
    if($dir!='.' && $dir!='..') {
      if(!chmod($directory.$dir,0777)) echo $directory.$dir; 
      if(is_dir($directory.$dir)) { 
        setMod($directory.$dir.'/');
        //rmdir($directory.$dir);
      } else {
        //unlink($directory.$dir);
      }
    }
  }
}

setMod('./');
?>