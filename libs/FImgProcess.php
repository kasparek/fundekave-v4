<?php
class FImgProcess {
    //---type - 1-GIF,2-JPG,3-PNG,4-SWF,5-PSD,6-BMP,7-TIFF,8-TIFF,9-JPC,10-JP2,11-JPX,12-JB2,13-SWC,14-IFF,15-WBMP,16-XBM
    var $supportedMimeTypes = array(1,2,3);
    
    var $mode = array('proportional'=>1);
    
    var $quality = 80;
    
    var $image;
    
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
            ($this->targetMimeType = $this->__mime_metaphone[metaphone($p['extension'])])
				|| ($this->targetMimeType = $this->__mime_soundex[soundex($p['extension'])]);
        }
        if(isset($mode['targetMimeType'])) $this->targetMimeType = (int) $mode['targetMimeType'];
        $this->open();
        if(isset($mode['width']) || isset($mode['height'])) {
            if(isset($mode['width'])) $width = $mode['width']; else $width = 0;
            if(isset($mode['height'])) $height = $mode['height']; else $height = 0;
            if(isset($mode['unsharpMask'])) $this->unsharpMask();
            $this->resize($width,$height);
            if(isset($mode['reflection'])) $this->reflection();
            if(isset($mode['rotate'])) $this->rotate();
            return $this->save();
        }
    }
    
    static function process($source,$target='',$mode=array()) {
    	return new FImgProcess($source, $target, $mode);
    }
    
    function open() {
        if(!is_dir($this->sourceUrl) && file_exists($this->sourceUrl)) {
            list($this->sourceWidth,$this->sourceHeight,$this->sourceMimeType) = getimagesize($this->sourceUrl);
            if(!in_array($this->sourceMimeType,$this->supportedMimeTypes)) $this->errorArr[] = 'ImgProcessing - OPEN: not supported image type::'.$this->sourceUrl;
            else {
                if($this->sourceMimeType == 1) $this->image = imagecreatefromgif($this->sourceUrl);
                elseif($this->sourceMimeType == 3) $this->image = imagecreatefrompng($this->sourceUrl);
                else $this->image = imagecreatefromjpeg($this->sourceUrl);
            }
        } else $this->errorArr[] = 'ImgProcessing: file not found::'.$this->sourceUrl;
    }
    /**
     * resize function
     *
     * @param int $width - target
     * @param int $height - target
     * @param array $mode - additional params - proportional,crop,frame(frameWidth,frameHeight,bgColorHex or bgColorRGB - array(R=>0-255,G=>0-255,B=>0-255))
     */
    function resize($width,$height,$mode=array()) {
        if(empty($this->errorArr)) {
            if(empty($mode)) $mode = $this->mode;
            
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
    		//---DEFAULT target image size is same as p_width and p_height so we set 0 and is set to that values just before creation if image but for frame will be the settings different
    		$targetWidth = 0;
    		$targetHeight = 0;
    		
    		if(isset($mode['frame'])) {
    		    if(isset($mode['frameWidth'])) $targetWidth = $mode['frameWidth'];
    		    else $targetWidth = $width;
    		    if(isset($mode['frameHeight'])) $targetHeight = $mode['frameHeight'];
    		    else $targetHeight = $height;
    		    if(!isset($mode['crop'])) $mode['proportional'] = 1;
    		}
    		
    		if(isset($mode['crop'])) {
    		    $ptmp_width = $width;
    		    $ptmp_height = round($this->sourceHeight * $width / $this->sourceWidth);
    		    if($ptmp_height < $height) {
                    $cropWidth = round( $width * $cropHeight / $height );
                    $cropX = round(($this->sourceWidth-$cropWidth) / 2);
    		    } else {
    		        $cropHeight = round( $height * $cropWidth / $width );
                    $cropY = round(($this->sourceHeight-$cropHeight) / 2);
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
			
			$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
			
			if(isset($mode['frame'])) {
                //FIXME: kombinace crop a frame zlobi protoze tohle
			    $targetX = ($targetWidth / 2) - ($p_width / 2);
               $targetY = ($targetHeight / 2) - ($p_height / 2);
    			//---set backgroung color
    			if(isset($mode['bgColorHex'])) $bgColorHex = $mode['bgColorHex'];
    			if(isset($mode['bgColorRGB'])) $bgColorHex = imageColorAllocate($targetImage, $mode['bgColorRGB']['R'], $mode['bgColorRGB']['G'], $mode['bgColorRGB']['B']);
				if(isset($bgColorHex)) ImageFill($targetImage,1,1,$bgColorHex);
				else {
				//---works just for PNG target
				  $colorTransparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
				 imagefill($targetImage, 0, 0, $colorTransparent);
				}
            }
			
			imagecopyresampled($targetImage, $this->image, $targetX, $targetY, $cropX, $cropY, $p_width, $p_height, $cropWidth, $cropHeight);
			$this->image = $targetImage;
			
        }
    }
    
    
    function reflection() {
      if(!$this->image) return false;
      $src_img = $this->image;
      $src_height = imagesy($src_img);
      $src_width = imagesx($src_img);
      $dest_height = $src_height + ($src_height / 2);
      $dest_width = $src_width;
     
      $reflected = imagecreatetruecolor($dest_width, $dest_height);
      if($this->targetMimeType == 3) { //--png
        imagealphablending($reflected, false);
        imagesavealpha($reflected, true);
      } else {
        imagealphablending($reflected, true);
        imagesavealpha($reflected, false);
      }
     
      imagecopy($reflected, $src_img, 0, 0, 0, 0, $src_width, $src_height);
      $reflection_height = $src_height / 2;
      $alpha_step = 80 / $reflection_height;
      for ($y = 1; $y <= $reflection_height; $y++) {
        for ($x = 0; $x < $dest_width; $x++) {
          // copy pixel from x / $src_height - y to x / $src_height + y
          $rgba = imagecolorat($src_img, $x, $src_height - $y);
          $alpha = ($rgba & 0x7F000000) >> 24;
          $alpha =  max($alpha, 47 + ($y * $alpha_step));
          $rgba = imagecolorsforindex($src_img, $rgba);
          $rgba = imagecolorallocatealpha($reflected, $rgba['red'], $rgba['green'], $rgba['blue'], $alpha);
          imagesetpixel($reflected, $x, $src_height + $y - 1, $rgba);
        }
      }
     
      $this->image = $reflected;
    }
    
    function rotate($angle=0) {
      if(!$this->image) return false;
      if(isset($this->mode['rotate'])) $angle = (int) $this->mode['rotate'];
      $circles = floor($angle/360);
      $angle = $angle - $circles*360;
    
      if($angle>0) {
        //--- -1 - rotating clockwise
        $bgColorHex = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        if(isset($mode['bgColorHex'])) $bgColorHex = $mode['bgColorHex'];
   			if(isset($mode['bgColorRGB'])) $bgColorHex = imageColorAllocate($this->image, $mode['bgColorRGB']['R'], $mode['bgColorRGB']['G'], $mode['bgColorRGB']['B']);
        $this->image = ImageRotate($this->image, $angle * -1, $bgColorHex);
      }
    }
    
    function save() {
      if(!empty($this->errorArr)) while($this->errorArr) FError::addError(array_shift($this->errorArr));
      
      if(!$this->image) return false;
        $ret = false;
        if(empty($this->targetUrl)) ob_start(); // start a new output buffer
        switch($this->targetMimeType) {
            case 1: //gif
                imageTrueColorToPalette($this->image, true, 256);
				$ret = @imageGIF($this->image, $this->targetUrl);
            break;
            case 3: //png
                imageSaveAlpha($this->image, true);
				  imageAlphaBlending($this->image, false);
				$ret = imagePNG($this->image, $this->targetUrl);
            break;
            default:
            case 2: //jpg
                 
				      imageAlphaBlending($this->image, true);
                $ret = imagejpeg($this->image,$this->targetUrl,$this->quality);
            break;
        }
        imagedestroy($this->image);
        
		if(empty($this->targetUrl)) {
			$data = ob_get_contents();
   			//$dataLength = ob_get_length();
			ob_end_clean(); // stop this output buffer
			return $data;
		} else return $ret;
    }
    
    function unsharpMask($amount=80, $radius=0.5, $threshold=3) { 
        if(!$this->image) return false;
        if(isset($this->mode['unsharpMaskAmount'])) $amount = $this->mode['unsharpMaskAmount'];
        if(isset($this->mode['unsharpMaskRadius'])) $radius = $this->mode['unsharpMaskRadius'];
        if(isset($this->mode['unsharpMaskTreshold'])) $threshold = $this->mode['unsharpMaskTreshold'];
    ////////////////////////////////////////////////////////////////////////////////////////////////  
    ////  
    ////                  Unsharp Mask for PHP - version 2.1.1  
    ////  
    ////    Unsharp mask algorithm by Torstein H�nsi 2003-07.  
    ////             thoensi_at_netcom_dot_no.  
    ////               Please leave this notice.  
    ////  
    //// def value to use
    //// Amount:  	 80  	(typically 50 - 200)
    //// Radius: 	0.5	(typically 0.5 - 1)
    //// Threshold: 	3	(typically 0 - 5)
    ///////////////////////////////////////////////////////////////////////////////////////////////  
        if ($amount > 500)    $amount = 500; 
        $amount = $amount * 0.016; 
        if ($radius > 50)    $radius = 50; 
        $radius = $radius * 2; 
        if ($threshold > 255)    $threshold = 255; 
        $radius = abs(round($radius));     // Only integers make sense. 
        if ($radius == 0) return false;
        $w = imagesx($this->image); 
        $h = imagesy($this->image); 
        $imgCanvas = imagecreatetruecolor($w, $h); 
        $imgBlur = imagecreatetruecolor($w, $h); 
        if (function_exists('imageconvolution')) { // PHP >= 5.1  
            // Gaussian blur matrix: 
                $matrix = array(  
                array( 1, 2, 1 ),  
                array( 2, 4, 2 ),  
                array( 1, 2, 1 )  
            );  
            imagecopy ($imgBlur, $this->image, 0, 0, 0, 0, $w, $h); 
            imageconvolution($imgBlur, $matrix, 16, 0);  
        }  else {  
        // Move copies of the image around one pixel at the time and merge them with weight 
        // according to the matrix. The same matrix is simply repeated for higher radii. 
            for ($i = 0; $i < $radius; $i++)    { 
                imagecopy ($imgBlur, $this->image, 0, 0, 1, 0, $w - 1, $h); // left 
                imagecopymerge ($imgBlur, $this->image, 1, 0, 0, 0, $w, $h, 50); // right 
                imagecopymerge ($imgBlur, $this->image, 0, 0, 0, 0, $w, $h, 50); // center 
                imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h); 
                imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up 
                imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down 
            } 
        } 
        if($threshold>0){ 
            // Calculate the difference between the blurred pixels and the original 
            // and set the pixels 
            for ($x = 0; $x < $w-1; $x++)    { // each row
                for ($y = 0; $y < $h; $y++)    { // each pixel 
                    $rgbOrig = ImageColorAt($this->image, $x, $y); 
                    $rOrig = (($rgbOrig >> 16) & 0xFF); 
                    $gOrig = (($rgbOrig >> 8) & 0xFF); 
                    $bOrig = ($rgbOrig & 0xFF); 
                    $rgbBlur = ImageColorAt($imgBlur, $x, $y); 
                    $rBlur = (($rgbBlur >> 16) & 0xFF); 
                    $gBlur = (($rgbBlur >> 8) & 0xFF); 
                    $bBlur = ($rgbBlur & 0xFF); 
                    // When the masked pixels differ less from the original 
                    // than the threshold specifies, they are set to their original value. 
                    $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig; 
                    $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig; 
                    $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig; 
                    if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) { 
                            $pixCol = ImageColorAllocate($this->image, $rNew, $gNew, $bNew); 
                            ImageSetPixel($this->image, $x, $y, $pixCol); 
                    } 
                } 
            } 
        } else { 
            for ($x = 0; $x < $w; $x++)    { // each row 
                for ($y = 0; $y < $h; $y++)    { // each pixel 
                    $rgbOrig = ImageColorAt($img, $x, $y); 
                    $rOrig = (($rgbOrig >> 16) & 0xFF); 
                    $gOrig = (($rgbOrig >> 8) & 0xFF); 
                    $bOrig = ($rgbOrig & 0xFF); 
                    $rgbBlur = ImageColorAt($imgBlur, $x, $y); 
                    $rBlur = (($rgbBlur >> 16) & 0xFF); 
                    $gBlur = (($rgbBlur >> 8) & 0xFF); 
                    $bBlur = ($rgbBlur & 0xFF); 
                    $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig; 
                    if($rNew>255) $rNew=255; elseif($rNew<0) $rNew=0; 
                    $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig; 
                    if($gNew>255) $gNew=255; elseif($gNew<0) $gNew=0;
                    $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig; 
                    if($bNew>255) $bNew=255; elseif($bNew<0) $bNew=0; 
                    $rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew; 
                    ImageSetPixel($this->image, $x, $y, $rgbNew); 
                } 
            } 
        } 
        imagedestroy($imgCanvas); 
        imagedestroy($imgBlur); 
        return true; 
    }
}