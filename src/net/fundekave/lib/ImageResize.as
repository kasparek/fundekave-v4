package net.fundekave.lib
{
	//import cmodule.aircall.CLibInit;
		
	import de.polygonal.gl.codec.JPEGEncode;
	import de.popforge.imageprocessing.core.Image;
	import de.popforge.imageprocessing.core.ImageFormat;
	import de.popforge.imageprocessing.filters.convolution.Sharpen;
	
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.display.PixelSnapping;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.geom.Point;
	import flash.geom.Rectangle;
	import flash.net.FileReference;
	import flash.utils.ByteArray;
	import flash.utils.setTimeout;
		
	public class ImageResize extends Sprite
	{
		public static const RESIZED:String = 'resized';
		public static const ENCODED:String = 'encoded';
		public static const INFO:String = 'info';
		
		private var bmpdOrig:BitmapData;
		
		public var _resultBmpData:BitmapData;
		public function get resultBmpData():BitmapData {
			return _resultBmpData.clone();
		}
		public var resultBytes:ByteArray;
		
		public var filtersList:XMLList;
		
		public var autoEncode:Boolean = false;
		
		public var crop:Boolean = false;
		public var widthOriginal:int;
		public var heightOriginal:int;
		public var widthMax:int;
		public var heightMax:int;
		public var widthNew:int;
		public var heightNew:int;
		public var rotationNew:Number;
		public var outputQuality:int;
		/*
		private var init:CLibInit;
		private var lib:Object;
		*/
		public function ImageResize(widthMax:int=0,heightMax:int=0,rotationNew:Number=0,outputQuality:int=90)
		{
			super();
			
			//alchemy init
			/*
			init = new CLibInit();
			lib = init.init();
			/**/
			this.widthMax = widthMax;
			this.heightMax = heightMax;
			this.rotationNew = rotationNew;
			this.outputQuality = outputQuality;
		}
		
		private var localFireRef:FileReference;
		public function loadReference(fileRef:FileReference):void {
			localFireRef = fileRef;
			fileRef.addEventListener(Event.COMPLETE, onFileRef);
			fileRef.load()
		}
		private var onlyInfo:Boolean = false;
		public function loadInfoFromReference(fileRef:FileReference):void {
			onlyInfo = true;
			localFireRef = fileRef;
			localFireRef.addEventListener(Event.COMPLETE, onFileRef);
			localFireRef.load()
		}
		
		private function onFileRef( e:Event ):void {
			localFireRef.removeEventListener(Event.COMPLETE, onFileRef);
			this.loadBytes( localFireRef.data );
		}
		
		private var image:Loader;
		public function loadBytes(bytes:ByteArray):void {
			image = new Loader();
    		image.loadBytes( bytes );
    		image.contentLoaderInfo.addEventListener(Event.COMPLETE, onImageReady);
    		this.addChild( image );
		}
		
		private function onImageReady(e:Event):void {
			if(localFireRef) {
				localFireRef.data.clear();
				localFireRef = null;
			}
        	image.contentLoaderInfo.removeEventListener(Event.COMPLETE, onImageReady );
        	var imageBmp:Bitmap = image.content as Bitmap;
			this.widthOriginal = imageBmp.width;
			this.heightOriginal = imageBmp.height;
        	if(this.onlyInfo===true) {
				this.onlyInfo=false;
				dispatchEvent( new Event( INFO ));
			} else {       	
        		bmpdOrig = new BitmapData(imageBmp.width+(imageBmp.width%2), imageBmp.height+(imageBmp.height%2) );
        		bmpdOrig.draw( image, null, null, null, null, true );
			}
        	imageBmp.bitmapData.dispose();
			imageBmp=null;
			 
        	image.parent.removeChild( image );
			setTimeout(onImageRemoved,1);
  		}
		
		private function applyFilters(bmpd:BitmapData):BitmapData {
			if(filtersList)
				if(filtersList.length() > 0) {
					var popImage:Image = new Image(bmpd.width, bmpd.height, ImageFormat.RGB);
					popImage.loadBitmapData( bmpd );
					var filXML:XML;
					for each( filXML in filtersList) {
						var filId:String = String( filXML.attribute('id') );
						switch( filId ) {
							case 'sharpen':
								var filter2: Sharpen = new Sharpen(0.1);
								filter2.apply( popImage );
								filter2=null;
								break;
						}
					}
					
					bmpd.dispose();
					bmpd = popImage.bitmapData.clone();
					
					popImage.dispose();
					popImage = null;
				}
			return bmpd;
		}
		
		private var bmp:Bitmap;
  		private function onImageRemoved():void {
			image.unload();
			image=null;
			if(!bmpdOrig) return;
        	//---time for filtering on bmp bitmapdatas
        	//---filtering
			bmpdOrig = this.applyFilters(bmpdOrig); 
        	
			//crop cropped if needed
			if(crop === true) {
				var cropped:Rectangle = ImageResize.cropCalc( bmpdOrig.width,bmpdOrig.height,widthMax,heightMax );
				var croppedBmpd:BitmapData = new BitmapData( cropped.width, cropped.height );
				croppedBmpd.copyPixels( bmpdOrig, cropped, new Point() );
				bmpdOrig.dispose();
				bmpdOrig = croppedBmpd;
				croppedBmpd=null;
			}
			
        	bmp = new Bitmap(bmpdOrig, PixelSnapping.NEVER, true);
			
			//---calculate new size
			if(widthMax > 0 || heightMax > 0) {
				var scaled:Rectangle = ImageResize.scaleCalc( bmpdOrig.width,bmpdOrig.height,widthMax,heightMax );
				widthNew = scaled.width; 
				heightNew = scaled.height;
			} else {
				widthNew = widthMax; 
				heightNew = heightMax;
			}
			trace('RESIZE::NEW::'+widthNew+'::'+heightNew);
			
        	//---resize bitmap
        	bmp.width = widthNew;
        	bmp.height = heightNew;
        	
        	this.x = -widthNew;
        	this.y = -heightNew;
        	
        	//---rotate bitmap
        	bmp.rotation = rotationNew;
        	//---translate because of rotation
        	switch(rotationNew) {
  				case 90:
  					bmp.x = bmp.width;
  				break;
  				case 270:
  					bmp.y = bmp.height;
  				break;
  				case 180:
  					bmp.x = bmp.width;
  					bmp.y = bmp.height;
  				break;
  			}
			resizeDispatched = false;
			setTimeout(onImageReady2,1);
        	this.addChild( bmp );
        }
        private var resizeDispatched:Boolean;
        private function onImageReady2(e:Event=null):void {
        	if(resizeDispatched===false){
	        	//---draw resized rotated bitmap
	        	_resultBmpData = new BitmapData(Math.round(bmp.width), Math.round(bmp.height) );
	        	_resultBmpData.draw( this, null, null, null, null, true ); 
	        	
				//_resultBmpData = this.applyFilters(_resultBmpData);
				        	
	        	//---dispatch event image resized
				resizeDispatched = true;
	        	dispatchEvent( new Event( RESIZED ) );
			}
			//---dispose
			setTimeout(onBmpRemoved,1);
			bmp.parent.removeChild( bmp );
        }
        
        private function onBmpRemoved():void {
			bmp.bitmapData.dispose();
			bmp=null;
        	if(autoEncode === true) {
        		this.encode();
        	}
        }

        public function encode(bmpd:BitmapData=null):void {
        	if(!bmpd) bmpd = _resultBmpData;
        	resultBytes = new ByteArray();
			        	       	
        	var jpgEnc:JPEGEncode = new JPEGEncode( outputQuality );
        	resultBytes = jpgEnc.encode( bmpd );
			jpgEnc=null;
			/**/
			/*
			var ba:ByteArray = bmpd.getPixels( bmpd.rect );
			ba.position = 0;
			lib.encode(ba,resultBytes,bmpd.width,bmpd.height,outputQuality);
			ba.clear();
			/**/
        	onCompressFinished(null);
			bmpd.dispose();
			bmpd=null;
        	/**/
        }
        
        private function onCompressFinished( out:ByteArray ):void {
           	//---dispatch event bytes encoded  
        	dispatchEvent( new Event( ENCODED ) );
        }
        
        public function dispose():void {
        	if(_resultBmpData) _resultBmpData.dispose();
			_resultBmpData = null;
			if(resultBytes) resultBytes.clear();
			resultBytes = null;
			if(bmpdOrig) bmpdOrig.dispose();
			bmpdOrig = null;
			if(this.parent) this.parent.removeChild( this );
        }
		
		static public function scaleCalc(originalWidth:Number,originalHeight:Number, maxWidth:Number, maxHeight:Number):Rectangle {
			var testWidth:Number  = (originalWidth * maxHeight / originalHeight);
			var testHeight:Number = (originalHeight * maxWidth / originalWidth);
			var rect:Rectangle = new Rectangle();
			if (testHeight < maxHeight) {
				rect.width = maxWidth;
				rect.height = Math.round( testHeight );
			} else if (testWidth < maxWidth) {
				rect.width = Math.round( testWidth );
				rect.height = maxHeight;
			} else {
				rect.width = Math.round( maxWidth );
				rect.height = Math.round( maxHeight );
			}
			return rect;
		}
		
		static public function cropCalc(originalWidth:Number,originalHeight:Number, newWidth:Number, newHeight:Number):Rectangle {
			var rect:Rectangle = new Rectangle(0,0,originalWidth,originalHeight);
			if((originalWidth/originalHeight) != (newWidth/newHeight)) {
				//---do CROP
				var ptmp_height:int = (originalHeight * newWidth) / originalWidth;
				if(ptmp_height < newHeight) {
					rect.width = (newWidth * originalHeight) / newHeight ;
					rect.x = ((originalWidth - rect.width) / 2);
				} else {
					rect.height =  (newHeight * originalWidth) / newWidth ;
					rect.y = (originalHeight - rect.height) / 2;
				}
			}
			return rect;
		}
	}
}