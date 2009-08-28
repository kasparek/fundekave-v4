<?php
//---service
//---data
switch($_GET['service']) {
  case 'config':
    echo file_get_contents('xml/config.xml');
  break;
  case 'login':
    echo file_get_contents('xml/login.xml');
  break;
}
?>