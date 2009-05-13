<?php
class UserVO {
  
  var $id;
  var $idlogin = '';
	var $idloginInDb = '';
  var $gidname = '';
	var $newPassword = '';
	var $galtype=0;
	var $skin = 0;
	var $skinName = '';
	var $skinDir = '';
	var $homePageId = '';
	var $email = '';
	var $icq = '';
	var $zbanner = 1;
	var $zidico = 1;
	var $zaudico = 1;
	var $ip = '';
	var $ipcheck = true;
	var $ico = AVATAR_DEFAULT;
	
	var $dateCreated;
	var $dateLast;
	
	//---additional user information XML structure
	var $xmlProperties = "<user><personal><www/><motto/><place/><food/><hobby/><about/><HomePageId/></personal><webcam /></user>";
		
}