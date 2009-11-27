<?php
class FFile {
	
	static function checkFilename($filename) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/" , $filename);
	}

	static function checkDirname($dirname) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9-\/]*))$/" , $dirname);
	}
	
	static function fileExt($filename) {
		$arr = explode('.',$filename);
		return strtolower($arr[count($arr)-1]);
	}
	
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
	
	static function makeDir($dir,$mode=0777,$recursive=true) {
		if(!file_exists($dir)) {
			 $ret = @mkdir($dir, $mode, $recursive);
			 @chmod($dir, 0777);
			 return $ret;
		}
	}
	
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
}