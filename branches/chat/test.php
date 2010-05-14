<?php
session_start();

if(isset($_REQUEST['start'])) {
echo $_SESSION['userRecipient']; 
exit;
}

if($_GET['a']==1) {
  $_SESSION['userId'] = 53;
  $_SESSION['userRecipient'] = 1;
} else {
  $_SESSION['userId'] = 1;
  $_SESSION['userRecipient'] = 53;
}
print_r($_SESSION);