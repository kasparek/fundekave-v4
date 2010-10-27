<?php
class FFile {
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
			$this->isFtpMode = true;
			$arr = explode('@',$ftpServer);
			$user = explode(":",$arr[0]);
			$this->ftpServer = $arr[1];
			$this->ftpUser = $user[0];
			$this->ftpPass = $user[1];
			$this->ftpConn = ftp_connect($this->ftpServer);
			ftp_login($this->ftpConn, $this->ftpUser, $this->ftpPass);
			ftp_pasv($this->ftpConn, true);
		}
	}

	function __destruct() {
		if($this->isFtpMode) {
			ftp_close($this->ftpConn);
		}
	}

	function file_exists($filename) {
		if(!$this->isFtpMode) return file_exists($filename);
		$isdir=false;
		if(ftp_chdir($this->ftpConn, $filename)) {
			ftp_chdir($this->ftpConn, '/');
			$iddir=true;
		}
		return $iddir || ftp_size($this->ftpConn, $filename)>0;
	}

	function is_file($filename) {
		if(!$this->isFtpMode) return is_file($filename);
		if(ftp_chdir($this->ftpConn, $filename)) {
			ftp_chdir($this->ftpConn, '/');
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
		$currentDir = ftp_pwd($this->ftpConn);
		if(ftp_chdir($this->ftpConn, $filename)) {
			ftp_chdir($this->ftpConn, $current);
			return true;
		}
		return false;
	}

	function is_link($filename) {
		if(!$this->isFtpMode) return is_link($filename);
		return false;
	}

	function unlink($filename) {
		if(!$this->isFtpMode) return unlink($filename);
		return ftp_delete($this->ftpConn, $filename);
	}

	function mkdir($filename, $mode=0777, $recursive=true) {
		if(!$this->isFtpMode) return mkdir($filename, $mode, $recursive);
		
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
		if(!$this->isFtpMode) return rmdir($filename);
		return ftp_rmdir($this->ftpConn, $filename);
	}

	function chmod($filename,$mode=0777) {
		if(empty($filename)) return;
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
		$ff=new FFile();
		$ff->makeDir(FConf::get("settings","fuup_chunks_path"));
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
				$tmpFilename = tempnam(WEBROOT.'tmp','fuup');
				$handleW = fopen($tmpFilename, "w+");
			} else {
				$handleW = fopen($imagePath, "w+");
			}
			for($i=0;$i<$total;$i++) {
				$fileChunk = $this->chunkFilename($filename,$i);
				$handle = fopen($fileChunk, "rb");
				fwrite($handleW, fread($handle, filesize($fileChunk)-($isMultipart===true?2:0)));
				fclose($handle);
				unlink($fileChunk);
				FError::write_log('writing image to:'.$imagePath.' deleting chunk '.$fileChunk);
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
				ftp_put($this->ftpConn,$imagePath,$tmpFilename,FTP_BINARY);
				unlink($tmpFilename);
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
	static function printConfigFile($c) {
		if(empty($c)) return;
		$c = FSystem::safeText($c);
		$user = FUser::getInstance();
		if(!$user->idkontrol) return;
		if(empty($user->pageVO)) return;
		$pageVO = $user->pageVO;
		$imgConf = FConf::get('image_conf');
		$sizeOptList = explode(',',$imgConf['sideOptions']);
		$maxSize = array_pop($sizeOptList);
		//defaults
		$vars = array(
		'SETTINGSENABLED'=>0
		,'SETTINGSON'=>0
		,'AUTOPROCESS'=>0
		,'AUTOUPLOAD'=>0
		,'DISPLAYCONTENT'=>1
		,'CROP'=>0
		,'MULTI'=>1
		,'QUALITY'=>90
		,'WIDTHMAX'=>$maxSize
		,'HEIGHTMAX'=>$maxSize
		,'APPWIDTH'=>-1
		,'APPHEIGHT'=>200
		,'ONLOADED'=>''
		,'ONONEUPLOADED'=>'galeryCheck'
		,'ONUPLOADED'=>''
		,'URL'=>'files.php?k='.$pageVO->pageId.(($c)?('&f='.$c):(''))
		,'AUTH'=>$user->getRemoteAuthToken()
		);
		switch($c) {
			case 'tempstore':
				$vars['AUTOPROCESS']=1;
				$vars['AUTOUPLOAD']=1;
				$vars['DISPLAYCONTENT']=0;
				$vars['MULTI']=0;
				$vars['APPHEIGHT']=25;
				$vars['APPWIDTH']=400;
				$vars['ONONEUPLOADED']='';
				$vars['ONUPLOADED']='fuupUploadComplete';
				break;
		}
		$tpl = FSystem::tpl('fuup.config.xml');
		$tpl->setVariable($vars);
		$tpl->show();
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
			ftp_chdir($this->ftpConn, $dir); 
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
		if($this->is_dir($filepath) && !$this->is_link($filepath)) {
			$list = $this->fileList($filepath);
			if(!empty($list)) {
				while($sf=array_pop($list)) {
					$f=$filepath.'/'.$sf;
					if(!$this->rm_recursive($f)) FError::write_log($f.' could not be deleted');
				}
			}
			if($ret = $this->rmdir($filepath)) $this->numModified++;
			return $ret;
		}
		if($this->file_exists($filepath)) {
			if($ret = $this->unlink($filepath)) $this->numModified++;
			return $ret;
		}
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