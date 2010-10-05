<?php
class FFile {
	var $isFtpMode = false;
	var $ftpServer;
	var $ftpConn;
	var $ftpUser;
	var $ftpPass;
	var $ftpDir='.';

	function __construct($ftpServer='',$ftpUser='',$ftpPass='') {
		if(!empty($ftpServer) && !empty($ftpUser)) {
			$this->isFtpMode = true;
			$this->ftpServer = $ftpServer;
			$this->ftpUser = $ftpUser;
			$this->ftpPass = $ftpPass;
			$this->ftpConn = ftp_connect($this->ftpServer);
			ftp_login($this->ftpConn, $this->ftpUser, $this->ftpPass);
		}
	}

	function __destruct() {
		if($this->isFtpMode) {
			ftp_close($this->ftpConn);
		}
	}

	function file_exists($filename) {
		if(!$this->isFtpMode) return file_exists($filename);
		if(strpos($filename,'/')!==false) {
			$f = explode('/',$filename);
			$filename = array_pop($f);
		}
		$list = $this->fileList(implode('/',$f));
		return in_array($filename,$list);
	}

	function is_file($filename) {
		if(!$this->isFtpMode) return is_file($filename);
		if (ftp_chdir($this->ftpConn, $filename)) {
			ftp_chdir($this->ftpConn, '..');
			return false;
		} else {
			return true;
		}
	}

	function filesize($filename) {
		if(!$this->isFtpMode) return filesize($filename);
		return ftp_size($this->ftpConn, $filename);
	}

	function is_dir($filename) {
		if(!$this->isFtpMode) return is_dir($filename);
		if (ftp_chdir($this->ftpConn, $filename)) {
			ftp_chdir($this->ftpConn, '..');
			return true;
		} else {
			return false;
		}
	}

	function is_link($filename) {
		if(!$this->isFtpMode) return is_link($filename);
		return false;
	}

	function unlink($filename) {
		if(!$this->isFtpMode) return unlink($filename);
		if(strpos($filename,'/')!==false) {
			$f = explode('/',$filename);
			$filename = array_pop($f);
			ftp_chdir($this->ftpConn, implode('/',$filename));
		}
		return ftp_delete($this->ftpConn, $filename);
	}

	function mkdir($filename, $mode=0777, $recursive=true) {
		if(!$this->isFtpMode) return mkdir($filename, $mode, $recursive);	
		//TODO:do recursive create
		return ftp_mkdir($this->ftpConn, $filename);
	}

	function rmdir($filename) {
		if(!$this->isFtpMode) return rmdir($filename);
		return ftp_rmdir($this->ftpConn, $filename);
	}

	function chmod($filename,$mode=0777) {
		FError::write_log('FFile::chmod '.$filename.' '.$mode .' ftp:'.$this->isFtpMode);
		if(!$this->isFtpMode) return chmod($filename,$mode);
		return ftp_chmod($this->ftpConn,$mode,$filename);
	}

	function rename($source, $target) {
		if(!$this->isFtpMode) return rename($source, $target);
		return ftp_rename($this->ftpConn, $source, $target);
	}
	
	function move_uploaded_file($source, $target) {
		if(!$this->isFtpMode) return move_uploaded_file($source, $target);
		return ftp_put($this->ftpConn, $source, $target, FTP_BINARY);
	}

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
			if(!file_exists($this->chunkFilename($filename,$i))) return false;
		}
		return true;
	}

	/**
	 * store one uploaded chunk of file
	 *
	 **/
	function storeChunk($file,$seq) {
		$this->makeDir(FConf::get("settings","fuup_chunks_path"));
		if(!empty($file['tmp_name'])) {
			move_uploaded_file($file["tmp_name"], $this->chunkFilename($file["name"],$seq) );
		} else if(!empty($file['data'])) {
			file_put_contents($this->chunkFilename($file["name"],$seq),$file["data"]);
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
			if($this->isFtpMode) {
				$handleW = tmpfile();
			} else {
				$handleW = fopen($imagePath, "w");
			}
			for($i=0;$i<$total;$i++) {
				$fileChunk = $this->chunkFilename($filename,$i);
				$handle = fopen($fileChunk, "rb");
				fwrite($handleW, fread($handle, filesize($fileChunk)-($isMultipart===true?2:0)));
				fclose($handle);
				unlink($fileChunk);
			}
			//---BASE64 DECODE IF NOT TRANSFERED VIE FILES / MULTIPART
			if($isMultipart===false) {
				$data = '';
				ftruncate($handleW,0);
				rewind($handleW);
				while (!feof($handleW)) {
					$data .= fread($handleW, 8192);
				}
				fwrite($handleW, base64_decode( $data ));
			}
			if($this->isFtpMode) {
				ftp_fput($this->ftpConn,$imagePath,$handleW,FTP_BINARY);
			}
			fclose($handleW);
		}
	}
	
	function file_put_contents($filename,$content) {
	    if(!$this->isFtpMode) return file_put_contents($filename,$content);
	    $handleW = tmpfile();
	    fwrite($handleW, $content);
	    ftp_fput($this->ftpConn,$filename,$handleW,FTP_BINARY);
	    fclose($handleW);
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
			case 'tempStore':
				$tpl = FSystem::tpl('fuup.tempStore.config.xml');
				break;
			default:
				$tpl = FSystem::tpl('fuup.galery.config.xml');
		}
		$tpl->setVariable('URL','files.php?k='.$pageVO->pageId.(($c)?('&f='.$c):('')));
		$user = FUser::getInstance();
		$tpl->setVariable('AUTH',$user->getRemoteAuthToken());
		$tpl->show();
	}


	/**
	 * plain file upload handler
	 *
	 **/
	static function upload($file,$kam='',$size=20000,$rewrite=true,$types=array("image/pjpeg","image/jpeg","image/png","image/gif")) {
		$ret = false;
		if (!is_uploaded_file($file["tmp_name"])) FError::add(FLang::$ERROR_UPLOAD_NOTLOADED);
		else if($file['size'] > $size) FError::add(FLang::$ERROR_UPLOAD_TOBIG);
		else if (!in_array($file['type'],$types)) FError::add(FLang::$ERROR_UPLOAD_NOTALLOWEDTYPE);
		else if($this->file_exists($kam.'/'.$file["name"]) && $rewrite==false) FError::add(FLang::$ERROR_UPLOAD_FILEEXISTS);
		else if(!FSystem::checkFilename($file['name'])) FError::add(FLang::$ERROR_UPLOAD_NOTALLOWEDFILENAME);
		else if (!$res = $this->move_uploaded_file($file["tmp_name"], $kam.'/'.$file["name"])) FError::add(FLang::$ERROR_UPLOAD_NOTSAVED);
		else {
			$this->chmod($kam.'/'.$file["name"],0777); //---upsesne ulozeno
			$ret["kam"] = $kam;
			$ret["name"] = $file["name"];
		}
		return($ret);
	}

	/**
	 * list all files in folder
	 **/
	function fileList($dir,$type="") {
		$arrFiles = array();
		if(!$this->isFtpMode) {
			//local
			if(is_dir($dir)) {
				$handle=opendir($dir);
				while (false!==($file = readdir($handle))) {
					if ($file != "." && $file != ".." && ($type=="" || preg_match("/(".$type.")$/i",$file))) {
						$arrFiles[]= $file;
					}
				}
				closedir($handle);
			}
		} else {
			//ftp
			$list = ftp_nlist($this->ftpConn,$dir);
			if($dir{strlen($dir)-1}!='/') $dir.='/';
			foreach($list as $file) {
				if ($file != "." && $file != ".." && ($type=="" || preg_match("/(".$type.")$/i",$file))) {
					$arrFiles[]= str_replace($dir,'',$file);
				}
			}

		}
		return $arrFiles;
	}

	/**
	 * calculate folder size
	 **/
	function folderSize($dir) {
		$arr = $this->fileList($dir);
		$size = 0;
		if(!empty($arr))
		foreach($arr as $file) {
			$filename = $dir.'/'.$file;
			if($this->is_file($filename)) {
				$size += $this->filesize($filename);
			}
		}
		return $size;
	}

	/**
	 * recursive folder create
	 *
	 **/
	function makeDir($dir,$mode=0777,$recursive=true) {
		if(!$this->file_exists($dir)) {
			if(!$ret = $this->mkdir($dir, $mode, $recursive)) {
				FError::write_log('FFile::makeDir - Make dir failed '.$dir);
			}
			$dirArr = explode('/',$dir);
			$dir = '';
			while(count($dirArr)>0) {
				$dir .= (($dir=='')?(''):('/')).array_shift($dirArr);
				$this->chmod($dir, $mode);
			}
			return $ret;
		}
	}

	/**
	 * recursive folder delete
	 **/
	function rm_recursive($filepath) {
		if (is_dir($filepath) && !is_link($filepath)) {
			$list = $this->fileList($filepath);
			if(!empty($list)) {
				while (count($list)>0) {
					$sf = array_pop($list);
					if ($sf != '.' && $sf != '..') {
						if (!$this->rm_recursive($filepath.'/'.$sf)) {
							FError::write_log($filepath.'/'.$sf.' could not be deleted.');
						}
					}
				}
			}
			return rmdir($filepath);
		}
		if(file_exists($filepath)) return unlink($filepath);
	}

	/**
	 * recursive chmod
	 **/
	function chmod_recursive($filepath,$mode=0777) {
		if ($this->is_dir($filepath) && !$this->is_link($filepath)) {
			$list = $this->fileList($filepath);
			if(!empty($list)) {
				while (count($list)>0) {
					$sf = array_pop($list);
					if ($sf != '.' && $sf != '..') {
						if (!$this->chmod_recursive($filepath.'/'.$sf)) {
							FError::write_log($filepath.'/'.$sf.' mode could not be changed.');
						}
					}
				}
			}
			return $this->chmod($filepath, $mode);
		}
		if($this->file_exists($filepath)) return $this->chmod($filepath, $mode);
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

	static function safeFilename($filename) {
		$arr = explode('.',strtolower($filename));
		$ext = array_pop($arr);
		return FSystem::safeText(implode('',$arr)) .'.'. FSystem::safeText($ext);
	}

	/**
	 *  get file extension
	 **/
	static function fileExt($filename) {
		$arr = explode('.',$filename);
		return strtolower($arr[count($arr)-1]);
	}
	
	/**
	 * store temporary filename
	 * @param String $filename
	 * @param String $pageId
	 * @return String safe checked filenam
	 */
	static function setTempFilename($filename) {
		$user = FUser::getInstance();
		if($user->userVO->userId==0) return false;
		$dir = FConf::get("galery","tempStore") . $user->userVO->name;
		$filename = FFile::safeFilename($filename);
		$imagePath = $dir . '/' . $filename;
		$cache = FCache::getInstance('d');
		$cache->setData($imagePath,$user->pageVO->pageId.'-'.$user->userVO->userId,'tempStore');
		return $imagePath;	
	}
	
	/**
	 * 
	 * @param String $pageId
	 * @return String retrive temp filename
	 */
	static function getTemplFilename() {
		$user = FUser::getInstance();
		if($user->userVO->userId==0) return false;
		$cache = FCache::getInstance('d');
		$ret = $cache->getData($user->pageVO->pageId.'-'.$user->userVO->userId,'tempStore');
		return $ret;
	}
	
	static function flushTemplFile() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('d');
		$filename = $cache->getData($user->pageVO->pageId.'-'.$user->userVO->userId,'tempStore');
		if($filename!==false) {
			if(file_exists($filename)) {
				unlink($filename);
			}
			$cache->invalidateData($user->pageVO->pageId.'-'.$user->userVO->userId,'tempStore');
		}
	}
	
}