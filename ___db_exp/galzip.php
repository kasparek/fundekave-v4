<?php
set_time_limit(3000);
$ftp_server = 'xspace.cz';
$ftp_user_name = 'admin.fundekave.net';
$ftp_user_pass = 'funka4';
// set up basic connection
$conn_id = ftp_connect($ftp_server); 

// login with username and password
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 

// check connection
if ((!$conn_id) || (!$login_result)) { 
        echo "FTP connection has failed!";
        echo "Attempted to connect to $ftp_server for user $ftp_user_name"; 
        exit; 
    } else {
        echo "Connected to $ftp_server, for user $ftp_user_name";
    }


//$zip = new ZipArchive();
//$filename = "./test112.zip";


/*if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
    exit("cannot open <$filename>\n");
}
*/
$dirRoot = '/home/fundekave/www/fundekave/data/idfoto/';
$dirRootDestionation = 'subdomeny/www/data/idfoto/';
ftp_chdir($conn_id,$dirRootDestionation);
function addToArchive($thisdir) {
  global $zip,$dirRoot,$conn_id;
  
  $filaArr = scandir($dirRoot.$thisdir);
  
  //if(count($fileArr)>0) 
  foreach($filaArr as $file) {
    if($file!='.' && $file!='..') { 
      if(!is_dir($dirRoot.$thisdir.'/'.$file)) {
        //$ret = $zip->addFile($dirRoot.$thisdir."/".$file,"/".$thisdir.'/'.$file);
        // upload the file
        if(ftp_size($conn_id, $file)>0) { 
          //echo 'File Exists :: '.$thisdir.'::'.$file.'<br>';
          ftp_chmod($conn_id, 0777, $file);
        } else {
          $upload = ftp_put($conn_id, $file, $dirRoot.$thisdir."/".$file, FTP_BINARY);
          if (!$upload) { 
              echo "FTP upload has failed! :: ".$dirRoot.$thisdir."/".$file.'<br />';
          } else {
            ftp_chmod($conn_id, 0777, $file);
              echo "Uploaded :: $thisdir :: $file<br>";
          }
        }
        
       } else {
        echo '<strong>Create Directory:: '.$file.'</strong><br>';
         
        @ftp_mkdir($conn_id, $file);
        ftp_chdir($conn_id, $file);
         addToArchive($thisdir.'/'.$file);
       }
    }
  
  }
  ftp_cdup($conn_id);
}


addToArchive('');

/*echo "numfiles: " . $zip->numFiles . "\n";
echo "status:" . $zip->status . "\n";
$zip->close();*/
// close the FTP stream 
ftp_close($conn_id); 
?>