<?php
//---service
//---data
header('Content-type: text/xml');
switch($_GET['service']) {
  case 'config':
    echo file_get_contents('xml/config.xml');
  break;
  case 'login':
    echo file_get_contents('xml/login.xml');
  break;
}