<?php
///----EXAMPLE OF USE fdkforum v1
//---session start is maybe done by your application already
session_start();



//---CONFIG array
$confArray = array(
'salt' => 'fdk35', //--- hash provided by kasparek
'serverPageId' => 'flll1', //--- page id of your guestbook on fundekave.net
'forumPageUri' => '', //--- local url of guestbook - need for page reload after message is sent
'arrErrors' => array('nameEmpty'=>'Nezadali jste jmeno','captchaFail'=>'Spatne jste opsali kod'), //--- error messages
'forumLibsPath' => './libs/', //--- local path to forum libraries - with slash on end
'templatePath' => './template/', //--- local path to HTML templates - with slash on end
'captchaTempFolderPath' => './temp/', //---must be 777 - local path to temporary folder for captcha images - with slash on end
'displayForm' => 1, //---set 0 if you do not want display intpu form - just list of items
'dateFormat' => 'H:i:s d.m.Y'
);


//--- initialization must be done somewhere on begining of the script - before any HTML is printed
require($confArray['forumLibsPath'].'fdkForum.class.php');
$forum = new fdkForum($confArray); 


?>
<!--
<head>....
<body>
...
...
any HTML
...
-->


<?php 
$forum->display(); //--- in the place where should be guestbook template placed 
?>