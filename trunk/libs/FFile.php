<?php
class FFile {

/**
 * get file name for chunk
 *
 **/  	
function chunkFilename($ident,$iter,$f='') {
	$user = FUser::getInstance();
	//TODO: remove if(isset($_GET['f'])) $f = '-'.$_GET['f']; else $f='';
	return  $file = FConf::get("settings","fuup_chunks_path").'chunk-'.$user->userVO->name.$f.'-'.$ident.'-'.$iter.'.txt';
}

/**
 * check if all chunks are uploaded
 * 
 **/  
function hasAllChunks($filename,$total) {
	for($i=0;$i<$total;$i++) {
		if(!file_exists(chunkFilename($filename,$i))) return false;
	}
	return true;
}

/**
 * store one uploaded chunk of file
 * 
 **/  
function storeChunk($file,$seq) {
	FFile::makeDir(FConf::get("settings","fuup_chunks_path"));
  if(!empty($file['tmp_name'])) {
		move_uploaded_file($file["tmp_name"], chunkFilename($file["name"],$seq) );
	} else if(!empty($file['data'])) {
  	file_put_contents(chunkFilename($file["name"],$seq),$file["data"]);
	}
}

/**
 * merge all uploaded chunks into file
 * 
 **/  
function mergeChunks($imagePath, $filename, $total, $isMultipart) {
	//---PUT CHUNKS TOGETHER
	if(!empty($imagePath)) {
		if(file_exists($imagePath)) unlink($imagePath);
		$handleW = fopen($imagePath, "w");
	  for($i=0;$i<$total;$i++) {
			$fileChunk = chunkFilename($filename,$i);
			$handle = fopen($fileChunk, "rb");
			fwrite($handleW, fread($handle, filesize($fileChunk)-($isMultipart===true?2:0)));
			fclose($handle);
			unlink($fileChunk);
		}
	  fclose($handleW);
	}
	//---BASE64 DECODE IF NOT TRANSFERED VIE FILES / MULTIPART 
	if($isMultipart===false) {
		if(file_exists($imagePath)) {
			file_put_contents($imagePath, base64_decode( file_get_contents($imagePath) ));
		}
	}
}

/**
 * print config file for uploader
 * 
 **/  
static function printConfigFile($c,$pageVO) {
	switch($c) {
		case 'uava':
			$tpl = FSystem::tpl('fuup.avatar.config.xml');
			break;
		case 'pava':
			$tpl = FSystem::tpl('fuup.pageAvatar.config.xml');
			break;
		case 'futip':
			$tpl = FSystem::tpl('fuup.event.config.xml');
			break;
		default:
			$tpl = FSystem::tpl('fuup.galery.config.xml');
	}
	$tpl->setVariable('URL','files.php?k='.$pageVO->pageId.(($c)?('&f='.$c):('')));
	$tpl->show();
}	
	
	
	/**
	 * plain file upload handler
	 * 
	 **/	 	 	
	static function upload($file,$kam='',$size=20000,$rewrite=true,$types=array("image/pjpeg","image/jpeg","image/png","image/gif")) {
		$ret = false;
		if (!is_uploaded_file($file["tmp_name"])) FError::addError(FLang::$ERROR_UPLOAD_NOTLOADED);
		else if($file['size'] > $size) FError::addError(FLang::$ERROR_UPLOAD_TOBIG);
		else if (!in_array($file['type'],$types)) FError::addError(FLang::$ERROR_UPLOAD_NOTALLOWEDTYPE);
		else if(file_exists($kam.'/'.$file["name"]) && $rewrite==false) FError::addError(FLang::$ERROR_UPLOAD_FILEEXISTS);
		else if(!FSystem::checkFilename($file['name'])) FError::addError(FLang::$ERROR_UPLOAD_NOTALLOWEDFILENAME);
		else if (!$res = move_uploaded_file($file["tmp_name"], $kam.'/'.$file["name"])) FError::addError(FLang::$ERROR_UPLOAD_NOTSAVED);
		else {
			chmod($kam.'/'.$file["name"],0777); //---upsesne ulozeno
			$ret["kam"] = $kam;
			$ret["name"] = $file["name"];
		}
		return($ret);
	}
	
	/**
	 * list all files in folder
	 **/	 	
	static function fileList($dir,$type="") {
		$arrFiles = array();
		if(is_dir($dir)) {
			$handle=opendir($dir);
			while (false!==($file = readdir($handle))) {
				if ($file != "." && $file != ".." && ($type=="" || preg_match("/(".$type.")$/i",$file))) {
					$arrFiles[]= $file;
				}
			}
			closedir($handle);
		}
		return $arrFiles;
	}
	
	/**
	 * calculate folder size
	 **/	 	
	static function folderSize($dir) {
		$arr = FFile::fileList($dir);
		$size = 0;
		if(!empty($arr))
		foreach($arr as $file) {
			$filename = $dir.'/'.$file;
			if(is_file($filename)) {
				$size += filesize($filename);	
			}
		}
		return $size;
	}
	
	/**
	 * recursive folder create
	 * 
	 **/	 	 	
	static function makeDir($dir,$mode=0777,$recursive=true) {
		if(!file_exists($dir)) {
			 $ret = mkdir($dir, $mode, $recursive);
			 $dirArr = explode('/',$dir);
			 $dir = '';
			 while(count($dirArr)>0) {
			 	$dir .= (($dir=='')?(''):('/')).array_shift($dirArr); 
			 	chmod($dir, $mode);
			 }
			 return $ret;
		}
	}
	
	/**
	 * recursive folder delete
	 **/	 	
	static function rm_recursive($filepath) {
		if (is_dir($filepath) && !is_link($filepath)) {
			if ($dh = opendir($filepath)) {
				while (($sf = readdir($dh)) !== false) {
					if ($sf != '.' && $sf != '..') {
						if (!FFile::rm_recursive($filepath.'/'.$sf)) {
							FError::addError($filepath.'/'.$sf.' could not be deleted.');
						}
					}
				}
				closedir($dh);
			}
			return rmdir($filepath);
		}
		if(file_exists($filepath)) return unlink($filepath);
	}
	
	/**
	 * recursive chmod
	 **/	 	
	static function chmod_recursive($filepath,$mode=0777) {
		if (is_dir($filepath) && !is_link($filepath)) {
			if ($dh = opendir($filepath)) {
				while (($sf = readdir($dh)) !== false) {
					if ($sf != '.' && $sf != '..') {
						if (!FFile::chmod_recursive($filepath.'/'.$sf)) {
							FError::addError($filepath.'/'.$sf.' mode could not be changed.');
						}
					}
				}
				closedir($dh);
			}
			return chmod($filepath, $mode);
		}
		if(file_exists($filepath)) return chmod($filepath, $mode);
	}
	
	/**
	 * validate that file name is safe string
	 **/
	static function checkFilename($filename) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/" , $filename);
	}

	/**
	 * validate that folder name is safe string
	 **/	 	
	static function checkDirname($dirname) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9-\/]*))$/" , $dirname);
	}
	
	/**
	 *  get file extension
	 **/	 	
	static function fileExt($filename) {
		$arr = explode('.',$filename);
		return strtolower($arr[count($arr)-1]);
	}
}