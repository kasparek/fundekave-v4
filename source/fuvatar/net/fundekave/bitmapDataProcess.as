package net.fundekave
{
	import flash.display.BitmapData;
	import flash.geom.Matrix;
	import flash.geom.Point;

	public class bitmapDataProcess 
	{
		private var _bmpData:BitmapData;
		private var width:int;
		private var height:int;
		public function bitmapDataProcess(bmpData:BitmapData)
		{
			_bmpData = bmpData;
			width = _bmpData.width;
			height =_bmpData.height;
		}
		public function resizeWithCrop(newWidth:int,newHeight:int):BitmapData {
			width = _bmpData.width;
			height =_bmpData.height;
			if(newWidth <= width && newHeight <= height ) {
				//---crop & resize original
				var originalRatio:Number = width/height;
				var newRatio:Number = newWidth/newHeight;
				if(originalRatio != newRatio) {
					//---do CROP
					var cropX:int = 0;
	            	var cropY:int = 0;
					var cropWidth:int = width;
	            	var cropHeight:int = height;
					var ptmp_width:int = newWidth;
	    		    var ptmp_height:int = height * newWidth / width;
	    		    if(ptmp_height < newHeight) {
	                    cropWidth = newWidth * cropHeight / newHeight ;
	                    cropX = ((width - cropWidth) / 2);
	    		    } else {
	    		        cropHeight =  newHeight * cropWidth / newWidth ;
	                    cropY = (height - cropHeight) / 2;
	    		    }
					_bmpData = crop(cropX,cropY,cropWidth,cropHeight);
				}
				//---do resize
				_bmpData = resize(newWidth,newHeight);
			}
			return _bmpData;
		}
		public function resize(newWidth:int,newHeight:int):BitmapData {	
			width = _bmpData.width;
			height =_bmpData.height;
	 		var originalWidth:Number = width;
	 		var originalHeight:Number = height;
		 	var m:Matrix = new Matrix();
	 	
		  	var sx:Number =  newWidth / originalWidth;
		  	var sy:Number = newHeight / originalHeight;
	  		var scale:Number = Math.min(sx, sy);
	  		newWidth = originalWidth * scale;
	  		newHeight = originalHeight * scale;	
	 		m.scale(scale, scale);
	 	 	var resized:BitmapData = new BitmapData( newWidth, newHeight);
 			resized.draw(_bmpData, m);
 		 	return resized;
		}
	public function crop(panX:int,panY:int,newWidth:int,newHeight:int):BitmapData {
		width = _bmpData.width;
		height =_bmpData.height;
		
		var minusWidth:Number = (newWidth - width) /2;
		var minusHeight:Number = (newHeight -height)/2;
		
		_bmpData.rect.offset(panX, panY);
		_bmpData.rect.inflate(minusWidth, minusHeight);
		
		var newBitmapData:BitmapData = new BitmapData(newWidth, newHeight)
		 newBitmapData.copyPixels(_bmpData, _bmpData.rect, new Point(0, 0));
		 return newBitmapData;
	}
	}
}