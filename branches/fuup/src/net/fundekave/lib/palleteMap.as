package net.fundekave.lib
{
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.utils.ByteArray;
	
	public class palleteMap
	{

	    private var image:Bitmap; // current frame
	    private var pixels:ByteArray; // BGR byte array from frame
	    private var indexedPixels:ByteArray // converted frame indexed to palette
	    private var pixelIndexes:Array;
	    
	    private var colorDepth:int; // number of bit planes
	    private var colorTab:ByteArray; // RGB palette
	    
	    private var usedEntry:Array = new Array; // active palette entries
	    private var palSize:int = 7; // color table size (bits-1)
	    private var dispose:int = -1; // disposal code (-1 = use default)
	    
	    private var sample:int = 10; // default sample interval for quantizer
	    
	    private var colors:int = 8; //number of colors used
		
		public function palleteMap(bmp:BitmapData,colorsParam:int,sampleParam:int):void {
			image = new Bitmap ( bmp );
			getImagePixels();
			colors = colorsParam;
			sample = sampleParam;
		}
		public function process():void {
			analyzePixels(); // build color table & map pixels
		}
		public function getConverted():BitmapData {
			var w:int = image.width;
		    var h:int = image.height;
		    
		    var count:int = 0;
		    for ( var i:int = 0; i < h; i++ ) {
		    	for (var j:int = 0; j < w; j++ ) {
		    		var pixelPallIndex:int = indexedPixels[count];
		    		var index:int = pixelPallIndex*3; 
		    		count++;
		    		var r:int = colorTab[index++] & 0xff;
		    		var g:int = colorTab[index++] & 0xff;
		    		var b:int = colorTab[index] & 0xff;
		    		var color:Number = r << 16 | g << 8 | b;
		    		image.bitmapData.setPixel( j, i, color );
		    	}
		    }
		    return image.bitmapData;
		}
		
		/**
		* Analyzes image colors and creates color map.
		*/
		private function analyzePixels():void {
			var len:int = pixels.length;
		    var nPix:int = len / 3;
		    indexedPixels = new ByteArray;
		    var nq:NeuQuant = new NeuQuant(pixels, len, sample,colors);
		    // initialize quantizer
		    colorTab = nq.process(); // create reduced palette
		    // map image pixels to new palette
		    //pixelIndexes =new Array();
		    var k:int = 0;
		    for (var j:int = 0; j < nPix; j++) {
		      var index:int = nq.map(pixels[k++] & 0xff, pixels[k++] & 0xff, pixels[k++] & 0xff);
		      usedEntry[index] = true;
		      //pixelIndexes.push(index);
		      indexedPixels[j] = index;
		    }
		    
		    pixels = null;
		    colorDepth = 8;
		    palSize = 7;
		}
		
		/**
		* Returns index of palette color closest to c
		*
		*/
		private function findClosest(c:Number):int {
			if (colorTab == null) return -1;
		    var r:int = (c & 0xFF0000) >> 16;
		    var g:int = (c & 0x00FF00) >> 8;
		    var b:int = (c & 0x0000FF);
		    var minpos:int = 0;
		    var dmin:int = 256 * 256 * 256;
		    var len:int = colorTab.length;
			
		    for (var i:int = 0; i < len;) {
		      var dr:int = r - (colorTab[i++] & 0xff);
		      var dg:int = g - (colorTab[i++] & 0xff);
		      var db:int = b - (colorTab[i] & 0xff);
		      var d:int = dr * dr + dg * dg + db * db;
		      var index:int = i / 3;
		      if (usedEntry[index] && (d < dmin)) {
		        dmin = d;
		        minpos = index;
		      }
		      i++;
		    }
		    return minpos;
		}
		
		/**
		* Extracts image pixels into byte array "pixels
		*/
		private function getImagePixels():void {
		    var w:int = image.width;
		    var h:int = image.height;
		    pixels = new ByteArray;
		    var count:int = 0;
		    for ( var i:int = 0; i < h; i++ ) {
		    	for (var j:int = 0; j < w; j++ ) {
		    		var pixel:Number = image.bitmapData.getPixel( j, i );
		    		pixels[count] = (pixel & 0xFF0000) >> 16;
		    		count++;
		    		pixels[count] = (pixel & 0x00FF00) >> 8;
		    		count++;
		    		pixels[count] = (pixel & 0x0000FF);
		    		count++;
		    	}
		    }
		}
		
		  
	}
}