<?php
/**
 * Cache engine
 */
class FCacheFile
{
	/*** Directory where to put the cache files */
	private $_cacheDir = '/tmp/';
	/*** Enable / disable caching */
	private $_caching = true;
	/*** Cache lifetime (in seconds) If null, the cache is valid forever.*/
	private $_lifeTime = null;
	public function setLifeTime($t) { $this->_lifeTime=$t; }
	/*** Enable / disable fileLocking*/
	private $_fileLocking = true;
	/*** Timestamp of the last valid cache*/
	private $_refreshTime;
	/*** File name (with path)*/
	private $_file;
	/*** File name (without path)*/
	private $_fileName;
	/*** Current cache id*/
	private $_id;
	/*** Current cache group*/
	private $_group;
	/*** Enable/Disable "Memory Caching" There is no lifetime for memory caching ! */
	private $_memoryCaching = true;
	/*** Enable / Disable "Only Memory Caching" boolean */
	private $_onlyMemoryCaching = false;
	/*** Memory caching array */
	private $_memoryCachingArray = array();
	/*** Nested directory level int */
	private $_hashedDirectoryLevel = 0;
	/*** Umask for hashed directory structure */
	private $_hashedDirectoryUmask = 0777;
	/**
	 * Constructor
	 * $options is an assoc. Available options are :
	 * $options = array(
	 *     'cacheDir' => directory where to put the cache files (string),
	 *     'caching' => enable / disable caching (boolean),
	 *     'lifeTime' => cache lifetime in seconds (int),
	 *     'fileLocking' => enable / disable fileLocking (boolean),
	 *     'memoryCaching' => enable / disable memory caching (boolean),
	 *     'onlyMemoryCaching' => enable / disable only memory caching (boolean),
	 *     'hashedDirectoryLevel' => level of the hashed directory system (int),
	 *     'hashedDirectoryUmask' => umask for hashed directory structure (int),
	 * );
	 * @param array $options options
	 */
	function __construct($options = array(NULL)) {
		foreach($options as $key => $value) $this->setOption($key, $value);
		if($this->_cacheDir{strlen($this->_cacheDir)-1}!='/') $this->_cacheDir.='/';
	}
	/**
	 * Generic way to set a Cache_Lite option
	 * see Cache_Lite constructor for available options
	 * @var string $name name of the option
	 * @var mixed $value value of the option
	 */
	public function setOption($name, $value) {
		$availableOptions = array('hashedDirectoryUmask', 'hashedDirectoryLevel', 'memoryCaching', 'onlyMemoryCaching', 'cacheDir', 'caching', 'lifeTime', 'fileLocking');
		if (in_array($name, $availableOptions)) $this->{'_'.$name} = $value;
	}
	/**
	 * Test if a cache is available and (if yes) return it
	 * @param string $id cache id
	 * @param string $group name of the cache group
	 * @return string data of the cache (else : false)
	 */
	public function get($id, $group = 'default') {
		$this->_id = $id;
		$this->_group = $group;
		$data = false;
		if ($this->_caching) {
			$this->_setRefreshTime();
			$this->_setFileName($id, $group);
			clearstatcache();
			if ($this->_memoryCaching) {
				if (isset($this->_memoryCachingArray[$this->_file])) return $this->_memoryCachingArray[$this->_file];
				if ($this->_onlyMemoryCaching) return false;
			}
			if (is_null($this->_refreshTime)) {
				if (file_exists($this->_file)) $data = $this->_read();
			} else {
				if ((file_exists($this->_file)) && (@filemtime($this->_file) > $this->_refreshTime)) $data = $this->_read();
			}
			if (($data) and ($this->_memoryCaching)) $this->_memoryCacheAdd($data);
			return $data;
		}
		return false;
	}
	/**
	 * Save some data in a cache file
	 * @param string $data data to put in cache
	 * @param string $id cache id
	 * @param string $group name of the cache group
	 * @return boolean true if no problem (else : false or a PEAR_Error object)
	 */
	public function save($data, $id = NULL, $group = 'default') {
		if(!is_null($id)) $this->_id = $id;
		if($this->_group!=$group) $this->_group = $group;
		if ($this->_caching) {
			if (isset($id)) $this->_setFileName($id, $group);
			if ($this->_memoryCaching) {
				$this->_memoryCacheAdd($data);
				if ($this->_onlyMemoryCaching) return true;
			}
			return $this->_write($data);
		}
		return false;
	}
	/**
	 * Remove a cache file
	 * @param string $id cache id
	 * @param string $group name of the cache group
	 * @param boolean $checkbeforeunlink check if file exists before removing it
	 * @return boolean true if no problem
	 */
	public function remove($id, $group = 'default', $checkbeforeunlink = true) {
		$this->_setFileName($id, $group);
		if ($this->_memoryCaching) {
			if (isset($this->_memoryCachingArray[$this->_file])) unset($this->_memoryCachingArray[$this->_file]);
			if ($this->_onlyMemoryCaching) return true;
		}
		if ( $checkbeforeunlink ) if (!file_exists($this->_file)) return true;
		return $this->_unlink($this->_file);
	}
	/**
	 * Clean the cache
	 * if no group is specified all cache files will be destroyed
	 * else only cache files of the specified group will be destroyed
	 * @param string $group name of the cache group
	 * @param string $mode flush cache mode : 'old', 'ingroup'
	 * @return boolean true if no problem
	 */
	public function clean($group = false, $mode = 'ingroup') {
		return $this->_cleanDir($this->_cacheDir, $group, $mode);
	}
	/**
	 * Write error to log
	 * @param string $msg error message
	 * @param int $code error code
	 */
	public function raiseError($msg, $code) {
		FError::write_log($msg);
		return false;
	}
	/**
	* Compute & set the refresh time
	*/
	private function _setRefreshTime() {
		if (is_null($this->_lifeTime)) $this->_refreshTime = null;
		else $this->_refreshTime = time() - $this->_lifeTime;
	}
	/**
	 * Remove a file
	 * @param string $file complete file path and name
	 * @return boolean true if no problem
	 */
	private function _unlink($file) {
		if (!unlink($file)) return $this->raiseError('Cache_Lite : Unable to remove cache !', -3);
		return true;
	}
	/**
	 * Recursive function for cleaning cache file in the given directory
	 * @param string $dir directory complete path (with a trailing slash)
	 * @param string $group name of the cache group
	 * @param string $mode flush cache mode : 'old', 'ingroup'
	 * @return boolean true if no problem
	 */
	private function _cleanDir($dir, $group = false, $mode = 'ingroup') {
		if(!empty($group)) $dir = $dir.$group.'/';
		if ($this->_memoryCaching) {
			if($group) {
				if(!empty($this->_memoryCachingArray))
				foreach($this->_memoryCachingArray as $key => $v)
				if (strpos($key, '/'.$group.'/') !== false)
				unset($this->_memoryCachingArray[$key]);
			} else {
				$this->_memoryCachingArray = array();
			}
			if ($this->_onlyMemoryCaching) return true;
		}
		if($mode=='ingroup') {
			if(is_dir($dir)) return FCacheFile::rrmdir($dir);
			return false;
		}
		if (!($dh = opendir($dir))) return $this->raiseError('Cache_Lite : Unable to open cache directory !', -4);
		$result = true;
		while ($file = readdir($dh)) {
			if (($file != '.') && ($file != '..')) {
				if(is_dir($dir . $file)) {
					$result = ($result and ($this->_cleanDir($dir . $file . '/', $group, $mode)));
				} else  {
					if($mode=='old') {
						// files older than lifeTime get deleted from cache
						if (!is_null($this->_lifeTime)) {
							if ((time() - @filemtime($dir.$file)) > $this->_lifeTime) {
								$result = ($result and ($this->_unlink($file2)));
							}
						}
					}
				}
			}
		}
		return $result;
	}
	/**
	 * Delete a file or recursively delete a directory
	 * @param string $str Path to file or directory
	 */
	static function rrmdir($path) {
		return is_file($path)?@unlink($path):array_map(array('FCacheFile','rrmdir'),glob($path.'/*'))==@rmdir($path);
	}
	/**
	 * Add some date in the memory caching array
	 * @param string $data data to cache
	 */
	private function _memoryCacheAdd($data) {
		$this->_memoryCachingArray[$this->_file] = $data;
	}
	/**
	 * Make a file name (with path)
	 * @param string $id cache id
	 * @param string $group name of the group
	 */
	private function _setFileName($id, $group) {
		$suffix = 'cache_'.$id;
		$root = $this->_cacheDir.$group.'/';
		if ($this->_hashedDirectoryLevel>0) {
			$hash = md5($suffix);
			for ($i=0 ; $i<$this->_hashedDirectoryLevel ; $i++) {
				$root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
			}
		}
		$this->_fileName = $suffix;
		$this->_file = $root.$suffix;
	}
	/**
	 * Read the cache file and return the content
	 * @return string content of the cache file (else : false)
	 */
	private function _read() {
		$fp = @fopen($this->_file, "rb");
		if ($this->_fileLocking) @flock($fp, LOCK_SH);
		if ($fp) {
			clearstatcache();
			$length = @filesize($this->_file);
			$mqr = get_magic_quotes_runtime();
			if ($mqr) set_magic_quotes_runtime(0);
			if ($length) $data = @fread($fp, $length); else $data = '';
			if ($mqr) set_magic_quotes_runtime($mqr);
			if ($this->_fileLocking) @flock($fp, LOCK_UN);
			@fclose($fp);
			return $data;
		}
		return $this->raiseError('Cache_Lite : Unable to read cache !', -2);
	}
	/**
	 * Write the given data in the cache file
	 * @param string $data data to put in cache
	 * @return boolean true if ok (a PEAR_Error object else)
	 */
	private function _write($data) {
		$root = $this->_cacheDir;
		if (!(@is_dir($root))) @mkdir($root, $this->_hashedDirectoryUmask);
		$root .= $this->_group.'/';
		if (!(@is_dir($root))) @mkdir($root, $this->_hashedDirectoryUmask);
		if ($this->_hashedDirectoryLevel > 0) {
			$hash = md5($this->_fileName);
			for ($i=0 ; $i<$this->_hashedDirectoryLevel ; $i++) {
				$root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
				if (!(@is_dir($root))) @mkdir($root, $this->_hashedDirectoryUmask);
			}
		}
		$fp = @fopen($this->_file, "wb");
		if ($fp) {
			if ($this->_fileLocking) @flock($fp, LOCK_EX);
			$mqr = get_magic_quotes_runtime();
			if ($mqr) set_magic_quotes_runtime(0);
			@fwrite($fp, $data);
			if ($mqr) set_magic_quotes_runtime($mqr);
			if ($this->_fileLocking) @flock($fp, LOCK_UN);
			@fclose($fp);
			return true;
		}
		return $this->raiseError('Cache_Lite : Unable to write cache file : '.$this->_file, -1);
	}
}