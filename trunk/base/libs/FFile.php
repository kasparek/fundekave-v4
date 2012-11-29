<?php
class FFile {

  var $ftpLogin;
	var $isFtpMode = false;
	var $ftpServer;
	var $ftpConn;
	var $ftpUser;
	var $ftpPass;
	var $ftpDir='.';

	var $numModified=0;

	/**
	 *
	 * @param String $ftpServer - user:password@ftpserverUrl
	 * @return void
	 */
	function __construct($ftpServer='') {
		if(!empty($ftpServer)) {
		  $this->ftpLogin = $ftpServer;	
		}
	}

	function __destruct() {
		if($this->isFtpMode) {
			ftp_close($this->ftpConn);
		}
	}
  
  function ftpConnect() {
    if($this->isFtpMode) return true;
    if(empty($this->ftpLogin)) return false;
    $this->isFtpMode = true;
    $arr = explode('@',$this->ftpLogin);
    $user = explode(":",$arr[0]);
    $this->ftpServer = $arr[1];
    $this->ftpUser = $user[0];
    $this->ftpPass = $user[1];
    $this->ftpConn = ftp_connect($this->ftpServer);
    $ret = ftp_login($this->ftpConn, $this->ftpUser, $this->ftpPass);
    ftp_pasv($this->ftpConn, true);
    if($ret) {
      FError::write_log('FFile::construc - CONNECTED TO FTP');
      return true;
    } 
    FError::write_log('FFile::construc - FTP FAIL');
    return false;
  }

	function file_exists($filename) {
		if(!$this->ftpConnect()) return file_exists($filename);
		return file_exists('ftp://'.$this->ftpLogin.$filename);
	}

	function is_file($filename) {
		if(!$this->ftpConnect()) return is_file($filename);
		return is_file('ftp://'.$this->ftpLogin.$filename);
	}

	function filesize($filename) {
		if(!$this->ftpConnect()) return filesize($filename);
		return ftp_size($this->ftpConn, $filename);
	}

	function is_dir($filename) {
		if(!$this->ftpConnect()) return is_dir($filename);
    return is_dir('ftp://'.$this->ftpLogin.$filename);
	}

	function is_link($filename) {
		if(!$this->ftpConnect()) return is_link($filename);
		return false;
	}

	function copy($source, $target) {
		if(!$this->file_exists($source)) return false;
		if($this->is_dir($source)) return false;
		if($this->file_exists($target)) return false;
		$targetArr = explode("/",$target);
		array_pop($targetArr);
		$targetFolder = implode("/",$targetArr);
		$this->mkdir($targetFolder);
		copy($source,$target);
	}
	
	function unlink($filename) {
		if(!$this->ftpConnect()) return unlink($filename);
		return ftp_delete($this->ftpConn, $filename);
	}

	function mkdir($filename, $mode=0777, $recursive=true) {
		if(!$this->ftpConnect()) return mkdir($filename, $mode, $recursive);
		$dir=explode("/", $filename);
		if(empty($dir[0])) array_shift($dir);
		$path = "";
		$ret = true;
		for ($i=0;$i<count($dir);$i++) {
			$path.="/".$dir[$i];
			if(!$this->file_exists($path)) {
				if(!ftp_mkdir($this->ftpConn,$path)) {
					$ret=false;
					break;
				} else {
					FError::write_log('FFile::mkdir - '.$filename);
					ftp_chmod($this->ftpConn, $mode, $path);
				}
			}
		}
		return $ret;
	}

	function rmdir($filename) {
		if(!$this->file_exists($filename)) return false;
		if(!$this->ftpConnect()) return rmdir($filename);
		return ftp_rmdir($this->ftpConn, $filename);
	}

	function chmod($filename,$mode=0777) {
		if(empty($filename)) return;
		if(!$this->ftpConnect()) return chmod($filename,$mode);
		return ftp_chmod($this->ftpConn,$mode,$filename);
	}

	function rename($source, $target) {
		if(!$this->ftpConnect()) return rename($source, $target);
		return ftp_rename($this->ftpConn, $source, $target);
	}

	function move_uploaded_file($source, $target) {
		if(!$this->ftpConnect()) return move_uploaded_file($source, $target);
		return ftp_put($this->ftpConn, $source, $target, FTP_BINARY);
	}
	
	function file_put_contents($filename,$content) {
		if(!$this->ftpConnect()) return file_put_contents($filename,$content);
		$tmpFilename = tempnam(sys_get_temp_dir(),'fuup');
		$handleW = fopen($tmpFilename, "w+");
		fwrite($handleW, $content);		
		ftp_put($this->ftpConn,$filename,$tmpFilename,FTP_BINARY);
		fclose($handleW);
	}

	/**
	 * plain file upload handler
	 *
	 **/
	function upload($file,$kam='',$size=20000,$rewrite=true,$types=array("image/pjpeg","image/jpeg","image/png","image/gif")) {
		$ret = false;
		if(!is_dir($kam)) $this->mkdir($kam);
		if (!is_uploaded_file($file["tmp_name"])) FError::add(FLang::$ERROR_UPLOAD_NOTLOADED);
		else if($file['size'] > $size) FError::add(FLang::$ERROR_UPLOAD_TOBIG);
		else if (!in_array($file['type'],$types)) FError::add(FLang::$ERROR_UPLOAD_NOTALLOWEDTYPE);
		else if($this->file_exists($kam.'/'.$file["name"]) && $rewrite==false) FError::add(FLang::$ERROR_UPLOAD_FILEEXISTS);
		else if(!FFile::checkFilename($file['name'])) FError::add(FLang::$ERROR_UPLOAD_NOTALLOWEDFILENAME);
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
		if(!$this->ftpConnect()) {
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
			try {
			if(!ftp_chdir($this->ftpConn, $dir)) return array(); 
			} catch (Exception $e) {
			FError::write_log('FFile::fileList - FTP chdir fail - directory does not exists ');
			return array();
			}
			$list = ftp_rawlist($this->ftpConn, "-A");
			ftp_chdir($this->ftpConn, '/');
			if($dir{strlen($dir)-1}!='/') $dir.='/';
			foreach($list as $rawfile) {
				$info=preg_split("/[\s]+/", $rawfile, 9);        
				$arrFiles[]=$info[8];
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
			$dirArr = explode('/',$dir);
			if($dir{0}=='/') {
				array_shift($dirArr);
				$dirArr[0] = '/'.$dirArr[0];
			}
			$dirTmp='';
			while(count($dirArr)>0) {
				$chmodFrom = array_pop($dirArr);
				if(file_exists(implode('/',$dirArr))) break;
			}
			if(!$ret = $this->mkdir($dir, $mode, $recursive)) {
				FError::write_log('FFile::makeDir - Make dir failed '.$dir);
			}
			
			$dirArr = explode('/',$dir);
			if($dir{0}=='/') {
				array_shift($dirArr);
				$dirArr[0] = '/'.$dirArr[0];
			}
			$dirTmp = '';
			$chmod=false;
			while(count($dirArr)>0) {
				$dirPart = array_shift($dirArr);
				$dirTmp .= (empty($dirTmp) ? '' : '/') . $dirPart;
				if(!$chmod) { if($dirPart==$chmodFrom) $chmod=true; }
				if($chmod) $this->chmod($dirTmp, $mode);
			}
			return $ret;
		}
	}

	/**
	 *recursive copy
	 */
	public $sourceFolder;
	public $targetFolder;
	private $currentTargetFolder='';
	public $recursive = true;
	function replicateToFtp( $dir='' ) {
		if(!file_exists($this->sourceFolder)) return;
		$localCurrentTargetFolder = $this->targetFolder.($this->targetFolder{strlen($this->targetFolder)-1}=='/'?'':'/').$dir;
		$filaArr = scandir($this->sourceFolder.$dir);
		foreach($filaArr as $file) {
			if($file!='.' && $file!='..') {
				$ds = $this->sourceFolder.($this->sourceFolder{strlen($this->sourceFolder)-1}=='/'?'':'/').$dir;
				$fs = $ds . ($ds{strlen($ds)-1}=='/'?'':'/') . $file;
				$dt = $this->targetFolder.($this->targetFolder{strlen($this->targetFolder)-1}=='/'?'':'/').$dir;
				$ft = $dt . ($dt{strlen($dt)-1}=='/'?'':'/') . $file;
				if(!is_dir($fs)) {
					// upload the file
					if(ftp_size($this->ftpConn, $ft)>0) {
						echo 'File Exists :: '.$dir.'::'.$file.'<br>';
					} else {
						$upload = ftp_put($this->ftpConn, $ft, $fs, FTP_BINARY);
						ftp_chmod($this->ftpConn, 0777, $ft);
						if (!$upload) echo "FTP upload has failed! :: $fs -> $ft<br />\n";
						else echo "Uploaded :: $fs -> $ft<br>\n";
					}
				} elseif($this->recursive===true) {
					$this->makeDir( $ft );
					$this->replicateToFtp( $dir.($dir{strlen($dir)-1}=='/'?'':'/').$file );
				}
			}
		}
	}
	
	/**
	 * recursive folder delete
	 **/
	function rm_recursive($filepath) {
		if(substr($filepath,-1) == '/') $filepath = substr($filepath,0,-1);
		if(!$this->file_exists($filepath)) return false;
		if($this->is_link($filepath)) return false;
		if(!is_dir($filepath)) {
			$this->numModified++;
			unlink($filepath);
		}
		$list = $this->fileList($filepath);
		if(!empty($list))
		while($sf=array_pop($list)) {
			$f=$filepath.'/'.$sf;
			if(!$this->rm_recursive($f)) FError::write_log($f.' could not be deleted');
		}
		if($ret = $this->rmdir($filepath)) $this->numModified++;
		return $ret;
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
		}
		if($this->file_exists($filepath)) {
			if($ret = $this->chmod($filepath, $mode)) $this->numModified++;
			return $ret;
		}
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
		if(!$user->idkontrol) return false;
		$cache = FCache::getInstance('d');
		return $cache->getData($user->pageVO->pageId.'-'.$user->userVO->userId,'tempStore');
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