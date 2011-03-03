<?php
class FCaptcha
{
	//	Default options, can be overridden from the calling code
	/**
	 * Absolute path to a Tempfolder (with trailing slash!). This must be writeable for PHP and also accessible via HTTP, because the image will be stored there.
	 **/
	var $tempfolder;
	var $ftpwebpath; //---physical root of web
	var $urlwebpath; //---url root of web
	/**
	 * Absolute path to folder with TrueTypeFonts (with trailing slash!). This must be readable by PHP.
	 **/
	var $TTF_folder;
	/** The minimum number of characters to use for the captcha * Set to the same as maxchars to use fixed length captchas **/
	var $minchars = 5;
	/** The maximum number of characters to use for the captcha **/
	var $maxchars = 7;
	/** The minimum font size to use **/
	var $minsize = 20;
	/** The maximum font size to use **/
	var $maxsize = 30;
	/** The maximum degrees a Char should be rotated. Set it to 30 means a random rotation between -30 and 30. **/
	var $maxrotation = 30;
	/** Background noise On/Off (if is FALSE, a grid will be created) **/
	var $noise = TRUE;
	/** This will only use the 216 websafe color pallette for the image. **/
	var $websafecolors = FALSE;
	/** Prefix of captcha image filenames **/
	var $filename_prefix = 'cptch_';
	/** Maximum lifetime of a captcha (in seconds) before being deleted during garbage collection **/
	var $maxlifetime = 600;
	/** Make all letters uppercase (does not preclude symbols) **/
	var $case_sensitive = TRUE;
	//	Private options, these are fixed options
	/** String of valid characters which may appear in the captcha **/
	var $validchars = 'abcdefghjkmnpqrstuvwxyz23456789?@#$%&*ABCDEFGHJKLMNPQRSTUVWXYZ23456789?@#$%&*';
	/** Picture width **/
	var $lx;
	/** Picture height **/
	var $ly;
	/** JPEG Image quality **/
	var $jpegquality = 80;
	/** Noise multiplier (number of characters gets multipled by this to define noise)
  * Note: This doesn't quite make sense, do you really want less noise in a smaller captcha? **/
	var $noisefactor = 9;
	/** Number of backgrond noise characters **/
	var $nb_noise;
	/** Holds the list of possible fonts **/
	var $TTF_RANGE;
	/** Holds the currently selected font filename **/
	var $TTF_file;
	/** Holds the number of characters in the captcha **/
	var $chars;
	var $public_K;
	var $private_K;
	/** Captcha filename **/
	var $filename;
	/** Holds the version number of the GD-Library **/
	var $gd_version;

	var $r;
	var $g;
	var $b;
	
	var $salt = 'thisIScaptCH4s47t4NDnobodYCAN+tGU8SS';


	////////////////////////////////
	//
	//	CONSTRUCTOR
	//

		/**
		  * Extracts the config array and overrides default settings.
		  *
		  **/
		function FCaptcha($config=array())
		{
			$config = array_merge(FConf::get('captcha'),$config);
			
			if(!file_exists(WEBROOT.$config['tempfolder'])) {
				$ff=new FFile();
				$ff->makeDir(WEBROOT.$config['tempfolder']);
			}
			// extracts config array
			if(!empty($config)) foreach($config as $k=>$v) $this->$k = $v;
			// check TrueTypeFonts
			$this->TTF_RANGE = array('0');
			if ($handle = opendir($this->TTF_folder)) {
				$i=0;
   				while (false !== ($file = readdir($handle))) {
		       	if ($file != '.' && $file != '..') {
							$this->TTF_RANGE[$i]=$file;
						}
				}
				closedir($handle);
			}
			// select first TrueTypeFont
			$this->change_TTF();
			// get number of noise-chars for background if is enabled
			$this->nb_noise = $this->noise ? ($this->chars * $this->noisefactor) : 0;
		}

	////////////////////////////////
	//
	//	PUBLIC METHODS
	//

		/**
		  * Generates a captcha image and returns the complete path to the image
		  *
		  **/
		function getImageUrl()
		{
			$this->make_captcha();
			$url = str_replace($this->ftpwebpath,$this->urlwebpath,$this->tempfolder).$this->filename_prefix.$this->public_key.'.jpg';
			return $url;
		}

		/**
		  *
		  * Validates submission and returns result
		  * Returns 0 = invalid sumbit | 1 = valid submit
		  *
		  **/
		function validateSubmit($image,$attempt) {
			if(empty($image)) return false;
			if(empty($attempt)) return false;
			$correct_hash = substr($image,-36,32);
			if(strlen($correct_hash)!=32) return false;
			if($this->case_sensitive==0) $attempt = strtoupper($attempt);
			if(file_exists($this->get_filename($correct_hash))) unlink($this->get_filename($correct_hash));
			else return false;
			return (md5($attempt.$this->salt)===$correct_hash) ? true : false;
		}



	////////////////////////////////
	//
	//	PRIVATE METHODS
	//

		/** @private **/
		function make_captcha()
		{
			$private_key='';
			$this->chars = mt_rand($this->minchars,$this->maxchars);
			for($i=0; $i < $this->chars; $i++) {
				$private_key .= $this->validchars{mt_rand(0,(strlen($this->validchars)-1))};
			}
			if($this->case_sensitive==0) $private_key = strtoupper($private_key);
			$this->public_key = md5($private_key.$this->salt);
				
			// set dimension of image
			$this->lx = (strlen($private_key) + 1) * (int)(($this->maxsize + $this->minsize) / 1.5);
			$this->ly = (int)(1.9 * $this->maxsize);

			// set number of noise-chars for background if is enabled
			$this->nb_noise = $this->noise ? (strlen($private_key) * $this->noisefactor) : 0;

			// create Image and set the apropriate function depending on GD-Version & websafecolor-value
			if($this->gd_version >= 2 && !$this->websafecolors)
			{
				$func1 = 'imagecreatetruecolor';
				$func2 = 'imagecolorallocate';
			}
			else
			{
				$func1 = 'imageCreate';
				$func2 = 'imagecolorclosest';
			}
			$image = $func1($this->lx,$this->ly);

			// Set Backgroundcolor
			$this->random_color(224, 255);
			$back =  @imagecolorallocate($image, $this->r, $this->g, $this->b);
			@ImageFilledRectangle($image,0,0,$this->lx,$this->ly,$back);
			// allocates the 216 websafe color palette to the image
			if($this->gd_version < 2 || $this->websafecolors) $this->makeWebsafeColors($image);

			// fill with noise or grid
			//if($this->nb_noise > 0) {
				// random characters in background with random position, angle, color
				if($this->debug) echo "\n<br>-Captcha-Debug: Fill background with noise: (".$this->nb_noise.')';
				for($i=0; $i < $this->nb_noise; $i++)
				{
					$size	= intval(mt_rand((int)($this->minsize / 1.9), (int)($this->maxsize / 1.2)));
					$angle	= intval(mt_rand(0, 360));
					$x		= intval(mt_rand(0, $this->lx));
					$y		= intval(mt_rand(0, (int)($this->ly - ($size / 5))));
					$this->random_color(117, 138);
					$color	= $func2($image, $this->r, $this->g, $this->b);
					$text	= chr(intval(mt_rand(45,250)));
					@ImageTTFText($image, $size, $angle, $x, $y, $color, $this->change_TTF(), $text);
				}
			//} else {
				// generate grid
				$scew = mt_rand(-$this->ly/4,$this->ly/4);
				if($this->debug) echo "\n<br>-Captcha-Debug: Fill background with x-gridlines: (".(int)($this->lx / (int)($this->minsize / 1.5)).')';
				for($i=0; $i < $this->lx; $i += (int)($this->minsize / 1.5))
				{
					$this->random_color(160, 224);
					$color	= $func2($image, $this->r, $this->g, $this->b);
					@imageline($image, $i, 0, $i+$scew, $this->ly, $color);
				}
				if($this->debug) echo "\n<br>-Captcha-Debug: Fill background with y-gridlines: (".(int)($this->ly / (int)(($this->minsize / 1.8))).')';
				for($i=0 ; $i < $this->ly; $i += (int)($this->minsize / 1.8))
				{
					$this->random_color(160, 224);
					$color	= $func2($image, $this->r, $this->g, $this->b);
					@imageline($image, 0, $i, $this->lx, $i+$scew, $color);
				}
			//}

			// generate Text
			if($this->debug) echo "\n<br>-Captcha-Debug: Fill forground with chars and shadows: (".$this->chars.')';
			for($i=0, $x = intval(mt_rand($this->minsize,$this->maxsize)); $i < strlen($private_key); $i++)
			{
				$text	= substr($private_key, $i, 1);
				$angle	= intval(mt_rand(($this->maxrotation * -1), $this->maxrotation));
				$size	= intval(mt_rand($this->minsize, $this->maxsize));
				$y		= intval(mt_rand((int)($size * 1.5), (int)($this->ly - ($size / 7))));
				$this->random_color(0, 127);
				$color	=  $func2($image, $this->r, $this->g, $this->b);
				$this->random_color(0, 127);
				$shadow = $func2($image, $this->r + 127, $this->g + 127, $this->b + 127);
				@ImageTTFText($image, $size, $angle, $x + (int)($size / 15), $y, $shadow, $this->change_TTF(), $text);
				@ImageTTFText($image, $size, $angle, $x, $y - (int)($size / 15), $color, $this->TTF_file, $text);
				$x += (int)($size + ($this->minsize / 5));
			}
			@ImageJPEG($image, $this->get_filename(), $this->jpegquality);
			@ImageDestroy($image);
		}

		/** @private **/
		function makeWebsafeColors(&$image) {
			for($r = 0; $r <= 255; $r += 51) {
				for($g = 0; $g <= 255; $g += 51) {
					for($b = 0; $b <= 255; $b += 51) {
						$color = imagecolorallocate($image, $r, $g, $b);
					}
				}
			}
		}

		/** @private **/
		function random_color($min,$max) {
			$this->r = intval(mt_rand($min,$max));
			$this->g = intval(mt_rand($min,$max));
			$this->b = intval(mt_rand($min,$max));
		}

		/** @private **/
		function change_TTF() {
			if(is_array($this->TTF_RANGE)) {
				$key = array_rand($this->TTF_RANGE);
				$this->TTF_file = $this->TTF_folder.$this->TTF_RANGE[$key];
			} else {
				$this->TTF_file = $this->TTF_folder.$this->TTF_RANGE;
			}
			return $this->TTF_file;
		}

		/** @private **/
		function get_filename($hash='') {
			return $this->tempfolder.$this->filename_prefix.($hash!=''?$hash:$this->public_key).'.jpg';
		}

		// Scanns the tempfolder for jpeg-files with nameprefix used by the class and trash them if they are older than maxlifetime.
		function collect_garbage() {
			$OK = FALSE;
			$captchas = 0;
			$trashed = 0;
			if($handle = @opendir($this->tempfolder)) {
				$OK = TRUE;
				while(false !== ($file = readdir($handle))) {
					if(!is_file($this->tempfolder.$file)) continue;
					// check for name-prefix, extension and filetime
					if(substr($file,0,strlen($this->filename_prefix)) == $this->filename_prefix) {
						if(strrchr($file, '.') == '.jpg') {
							$captchas++;
							if((time() - filemtime($this->tempfolder.$file)) >= $this->maxlifetime) {
								$trashed++;
								$res = @unlink($this->tempfolder.$file);
								if(!$res) $OK = FALSE;
							}
						}
					}
				}
				closedir($handle);
			}
			return $OK;
		}

}