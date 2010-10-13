<?php
/**
 * image processing
 * imagick support implemented
 *
 **/
class FImgProcess {
	var $userImagick = true;
	//---type - 1-GIF,2-JPG,3-PNG,4-SWF,5-PSD,6-BMP,7-TIFF,8-TIFF,9-JPC,10-JP2,11-JPX,12-JB2,13-SWC,14-IFF,15-WBMP,16-XBM
	var $supportedMimeTypes = array(1,2,3);

	var $mode = array('proportional'=>1);

	var $widthMax;
	var $heightMax;
	var $quality = 80;

	var $image;
	var $imagick;

	var $data;

	var $sourceUrl;
	var $sourceWidth;
	var $sourceHeight;
	var $sourceMimeType;

	var $targetUrl;
	var $targetMimeType = 2; //1-gif,2-jpg,3-png

	var $errorArr = array();

	/**
	 * Metaphone map for detecting image file extensions
	 * @var array
	 * @access private
	 */
	var $__mime_metaphone = array(
			'JPK' => 2, //'image/jpeg',
			'JP' => 2, //'image/jpeg',
			'JF' => 1, //'image/gif',
			'NK' => 3, //'image/png',
			'BMP' => 6, //'image/wbmp',
			'SPM' => 16 //'image/x-xbm',
	);

	/**
	 * Soundex map for detecting image file extensions
	 * @var array
	 * @access private
	 */
	var $__mime_soundex = array(
			'J120' => 2, //'image/jpeg',
			'J100' => 2, //'image/jpeg',
			'G100' => 1, //'image/gif',
			'P520' => 3, //'image/png',
			'B510' => 6, //'image/wbmp',
			'W151' => 6, //'image/wbmp',
	);


	function __construct($source,$target='',$mode=array()) {
		$this->sourceUrl = $source;
		if(!empty($target)) $this->targetUrl = $target;
		if(!empty($mode)) $this->mode = $mode;
		if(isset($mode['quality'])) $this->quality = (int) $mode['quality'];
		if(!empty($target)) {
			$p = pathinfo($target);
			if(!empty($p['extension'])) {
				($this->targetMimeType = $this->__mime_metaphone[metaphone($p['extension'])])
				|| ($this->targetMimeType = $this->__mime_soundex[soundex($p['extension'])]);
			}
		}
		if(isset($mode['targetMimeType'])) $this->targetMimeType = (int) $mode['targetMimeType'];

		if(isset($mode['width']) || isset($mode['height'])) {

			if(isset($mode['width'])) $width = $mode['width']; else $width = 0;
			if(isset($mode['height'])) $height = $mode['height']; else $height = 0;
			$this->widthMax = $width;
			$this->heightMax = $height;

			if($this->open() === false) return false;

			$sharpen = false;
			$normalize = false;
			if(isset($mode['optimize'])) {
				$sharpen = true;
				$normalize = true;	
			}
			if(isset($mode['sharpen'])) $sharpen=true;
			if(isset($mode['normalize'])) $normalize=true;
			
			if($this->imagick) {
				if($normalize) $this->imagick->normalizeImage();
				if($sharpen) $this->imagick->unsharpMaskImage(0 , 0.5 , 1 , 0.05);
			}

			$this->resize($width,$height);

			if(isset($mode['rotate'])) $this->rotate();

			$this->data = $this->save();
		}
	}

	static function process($source,$target='',$mode=array()) {
		$p = new FImgProcess($source, $target, $mode);
		return $p->data;
	}

	function open() {
		$props = FImgProcess::getimagesize($this->sourceUrl);

		if($props===false) {
			$this->errorArr[] = 'ImgProcessing: file not found::'.$this->sourceUrl;
			return false;
		}

		$this->sourceWidth = $props[0];
		$this->sourceHeight = $props[1];
		$this->sourceMimeType = $props[2];
		if(isset($props['source'])) $this->sourceUrl = $props['source'];

		$upsize = true;
		if(isset($this->mode['upsize'])) $upsize = $this->mode['upsize'];

		if($upsize === false) {
			$doNotResize = true;
			if($this->widthMax > 0) {
				if($this->sourceWidth > $this->widthMax) {
					$doNotResize = false;
				}
			}
			if($this->heightMax > 0) {
				if($this->heightWidth > $this->heightMax) {
					$doNotResize = false;
				}
			}
			if($doNotResize === true) {
				//return not resized image
				return;
			}
		}

		if(!in_array($this->sourceMimeType,$this->supportedMimeTypes)) $this->errorArr[] = 'ImgProcessing - OPEN: not supported image type::'.$this->sourceUrl;
		else {
			if($this->userImagick===true) {
				if(class_exists('Imagick')) {
					//FError::write_log('FImgProcess::open trying IMAGICK');
					$this->imagick = new Imagick( $this->sourceUrl );
					$this->imagick->stripImage();
					return true;
				} else {
					$this->userImagick = false;
				}
			}
			if($this->userImagick===false) {
				//FError::write_log('FImgProcess::using GD');
				//imagemagick not available
				if($this->sourceMimeType == 1) $this->image = imagecreatefromgif($this->sourceUrl);
				elseif($this->sourceMimeType == 3) $this->image = imagecreatefrompng($this->sourceUrl);
				else $this->image = imagecreatefromjpeg($this->sourceUrl);
				return true;
			}
		}
	}
	/**
	 * resize function
	 *
	 * @param int $width - target
	 * @param int $height - target
	 * @param array $mode - additional params - proportional,crop)
	 */
	function resize($width,$height,$mode=array()) {

		if(!$this->image && !$this->imagick) return;

		if(empty($this->errorArr)) {
			if(empty($mode)) $mode = $this->mode;
			$crop = false;
			if(!empty($mode['crop'])) $crop = true;

			$cropX = 0;
			$cropY = 0;
			$targetX = 0;
			$targetY = 0;
			$cropWidth = $this->sourceWidth;
			$cropHeight = $this->sourceHeight;

			if ($width==0 || $height==0) {
				$mode['proportional'] = 1;
				if ($width==0) $width = floor($this->sourceWidth * $height / $this->sourceHeight);
				if ($height==0) $height = floor($this->sourceHeight * $width / $this->sourceWidth);
			}

			//---DEFAULT is stretch
			$p_width = $width;
			$p_height = $height;
			//---DEFAULT target image size is same as p_width and p_height so we set 0 and is set to that values just before creation if image
			$targetWidth = 0;
			$targetHeight = 0;

			if($crop) {
				if(($this->sourceHeight * $width / $this->sourceWidth) < $height) {
					$cropWidth = $width * $cropHeight / $height;
					$cropX = ($this->sourceWidth-$cropWidth) / 2;
				} else {
					$cropHeight = $height * $cropWidth / $width;
					$cropY = ($this->sourceHeight-$cropHeight) / 2;
				}
			}

			if(isset($mode['proportional'])) {
				$p1_width = $width;
				$p1_height = round($this->sourceHeight * $width / $this->sourceWidth);
				if ($p1_height - $height > 1) {
					$p_height = $height;
					$p_width = round($this->sourceWidth * $height / $this->sourceHeight);
				} else {
					$p_width = $p1_width;
					$p_height = $p1_height;
				}
			}
			//--do resize
			if($targetWidth == 0) $targetWidth = $p_width;
			if($targetHeight == 0) $targetHeight = $p_height;

			if(!$this->imagick) {
				$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
			}

			if($this->imagick) {
				if($crop) {
					$this->imagick->cropImage($cropWidth, $cropHeight, $cropX, $cropY);
				}
				$this->imagick->resizeImage( $targetWidth  , $targetHeight  , Imagick::FILTER_LANCZOS  , 1 );
			} else {
				imagecopyresampled($targetImage, $this->image, $targetX, $targetY, $cropX, $cropY, $p_width, $p_height, $cropWidth, $cropHeight);
				$this->image = $targetImage;
			}
		}
	}

	function rotate($angle=0) {
		if(isset($this->mode['rotate'])) $angle = (int) $this->mode['rotate'];
		$circles = floor($angle/360);
		$angle = $angle - $circles*360;

		if($angle==0) return false;
			
		if($this->imagick) {
			$this->imagick->rotateImage(new ImagickPixel(), $angle);
		}

		if(!$this->image) return false;

		//--- -1 - rotating clockwise
		$bgColorHex = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
		if(isset($mode['bgColorHex'])) $bgColorHex = $mode['bgColorHex'];
		if(isset($mode['bgColorRGB'])) $bgColorHex = imageColorAllocate($this->image, $mode['bgColorRGB']['R'], $mode['bgColorRGB']['G'], $mode['bgColorRGB']['B']);
		$this->image = ImageRotate($this->image, $angle * -1, $bgColorHex);

	}

	function save() {
		if(!empty($this->errorArr)) while($this->errorArr) FError::add(array_shift($this->errorArr));

		if($this->imagick) {
			switch($this->targetMimeType) {
				case 1: //gif
					$this->imagick->setImageFormat('gif');
					break;
				case 3: //png
				 $this->imagick->setImageFormat('png');
				 break;
				default:
				case 2: //jpg
					$this->imagick->setCompression(Imagick::COMPRESSION_JPEG);
					$this->imagick->setImageFormat('jpeg');
					$this->imagick->setImageCompressionQuality($this->quality);
			}


			if($this->targetUrl) {
				$data = $this->imagick->writeImage($this->targetUrl);
			} else {
				//get only data
				$data = $this->imagick->getImage();

			}
			$this->imagick->clear();
			$this->imagick->destroy();

		} else {
			if(empty($this->targetUrl)) ob_start(); // start a new output buffer
			switch($this->targetMimeType) {
				case 1: //gif
					imageTrueColorToPalette($this->image, true, 256);
					$data = imageGIF($this->image, $this->targetUrl);
					break;
				case 3: //png
					imageSaveAlpha($this->image, true);
					imageAlphaBlending($this->image, false);
					$data = imagePNG($this->image, $this->targetUrl);
					break;
				default:
				case 2: //jpg
					imageAlphaBlending($this->image, true);
					$data = imagejpeg($this->image,$this->targetUrl,$this->quality);
					break;
			}
			imagedestroy($this->image);
			if(empty($this->targetUrl)) {
				$data = ob_get_contents();
				ob_end_clean(); // stop this output buffer
			}
		}

		//FError::write_log('FImgProcess:: TRANSFORMATION COMPLETE - '.$this->targetUrl);
		return $data;
	}

	static function getimagesize($image_url) {
		//check if file exists localy
		if(file_exists($image_url)) {
			//check file is not dir
			if(is_dir($image_url)) return false;
			//return image size
			return getimagesize($image_url);
		}

		//filename is not URL
		if(strpos($image_url,'http://')===false && strpos($image_url,'http://')===false) return false;

		$temp_file = tempnam(sys_get_temp_dir(), 'Tux');

		$handle = fopen ($image_url, "rb");
		if (!$handle) return false;

		//read file
		$contents = "";
		do {
			$data = fread($handle, 8192);
			if (strlen($data) == 0) {
				break;
			}
			$contents .= $data;
		} while(true);
		fclose ($handle);

		$handle = fopen($temp_file, "w");
		fwrite($handle, $contents);
		fclose($handle);
		$contents = '';

		if(!file_exists($temp_file)) return false;

		try {
			$imageSize = getimagesize($temp_file);
			$imageSize['source'] = $temp_file;

		} catch (Exception $e) {
			return false;
		}

		return $imageSize;
	}

}