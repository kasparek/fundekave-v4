<?php
set_time_limit(3000);
$ftp_server = 'xspace.cz';
$ftp_user_name = 'admin.fundekave.net';
$ftp_user_pass = 'funka4';
$dirRoot = '/home/fundekave/www/fundekave/obr/';
//---relative to login path
$dirRootDestionation = 'subdomeny/www/obr/';

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

ftp_chdir($conn_id,$dirRootDestionation);
$limiter = 10;
$counter = 0;
function copyRecursive($thisdir='') {
    global $dirRoot,$conn_id,$limiter,$counter;
    $filaArr = scandir($dirRoot.$thisdir);
    foreach($filaArr as $file) {
        if($counter<$limiter) {
            if($file!='.' && $file!='..') { 
                if(!is_dir($dirRoot.$thisdir.'/'.$file)) {
                // upload the file
                    if(ftp_size($conn_id, $file)>0) { 
                        //echo 'File Exists :: '.$thisdir.'::'.$file.'<br>';
                    } else {
                        $upload = ftp_put($conn_id, $file, $dirRoot.$thisdir."/".$file, FTP_BINARY);
                        if (!$upload) echo "FTP upload has failed! :: ".$dirRoot.$thisdir."/".$file.'<br />';
                        else echo "Uploaded :: $thisdir :: $file<br>";
                        //$counter++;
                    }
                } else {
                    echo '<strong>Create Directory:: '.$file.'</strong><br>';
                    @ftp_mkdir($conn_id, $file);
                    ftp_chdir($conn_id, $file);
                    copyRecursive($thisdir.'/'.$file);
                }
            }
        }
    }
    if($thisdir!='') ftp_cdup($conn_id);
}

copyRecursive();
 
ftp_close($conn_id); 