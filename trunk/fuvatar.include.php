<?php

$xajax = true; //---do not reload page settings

if(!empty($_GET['u'])) {
  $fuvatarFlag = 'image';
  $fuvatarId = $_GET['u'];
}
if(isset($fuvatarFlag)) {
  require(INIT_FILENAME);
  
  $fuvatarConfig = array(
  'configTemplateUrl'=> ROOT.ROOT_WEB.'fuvatar/fuvatar.config.template.xml',
  'targetFtp'=>ROOT.'tmp/fuvatar/',
  'targetJpg'=>$user->gidname.'.jpg',
  'confXml'=>array()
  );
  //---set congXml if user have in his settings some specific values
  if(!isset($fuvatarId)) $fuvatarId = $user->gidname;
  
  $fUvatar = new fUvatar($fuvatarId,$fuvatarConfig);
  
  if($fuvatarFlag == 'config') {
    //---show config xml
    header ("content-type: text/xml");
    echo $fUvatar->getConfig();
  } elseif($fuvatarFlag == 'gateway') {
    if(isset($_POST["futa"])) $fUvatar->upload($_POST["futa"]);
  } else {
     $function = '';
     if(isset($_GET['f'])) $function = $_GET['f'];
     if($function == 'ch') {
         //---check time
         if(isset($_GET['u'])) {
            $username = $_GET['u'];
            $fUvatar->check($username);
         }
     } else {
            //---get image
            //---check rules
            if(isset($_GET['u'])) {
              $username = $_GET['u'];
              if(true === $user->fuvatarAccess($username)) {
                header ("content-type: image/jpg");
                echo $fUvatar->download($username);
              }
            }
     }
  }
}