<?
require("./local.php");
  require(INIT_FILENAME);
$dir = './data/aico/';
$arrFiles = FSystem::fileList($dir,'jpg|jpeg|gif|JPG|JPEG|GIF');
if(!empty($arrFiles)) {
  while ($arrFiles) {
    $file = array_shift($arrFiles);
    $sourceFile = $dir.$file;
    //chmod($sourceFile,0777);
    list($width,$height) = getimagesize($sourceFile);
    if($width!=60 && $height!=25 && $width>0) {
      //resize, update
      $length = strlen($file);
      $extension = substr($file,$length-3);
      
      $destFile = $file;
      /*if($extension!='jpg' && $extension!='JPG' && $extension!='jpeg' && $extension!='JPEG') {
        $destFile = str_replace($extension,'jpg',$file);
        $db->query("update sys_users set avatar='".$destFile."' where avatar='".$file."'");
      }*/
      
      $destinationFile = $dir.$destFile;
      echo $sourceFile.','.$destinationFile.'<br/>';
      $iproc = new FImgProcess($sourceFile,$sourceFile,array('crop'=>1,'width'=>60,'height'=>25,'quality'=>90));
      
      if($sourceFile!=$destinationFile) {
        chmod($destinationFile,0777);
        unlink($sourceFile);
      }
      
    }
  }
}
?>