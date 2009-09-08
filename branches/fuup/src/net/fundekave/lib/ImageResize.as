package net.fundekave.lib
{
	import cmodule.jpegencoder.CLibInit;
	
	import de.popforge.imageprocessing.core.Image;
	import de.popforge.imageprocessing.core.ImageFormat;
	import de.popforge.imageprocessing.filters.color.ContrastCorrection;
	import de.popforge.imageprocessing.filters.color.LevelsCorrection;
	import de.popforge.imageprocessing.filters.convolution.Sharpen;
	
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.display.PixelSnapping;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.geom.Rectangle;
	import flash.net.FileReference;
	import flash.utils.ByteArray;
	
	public class ImageResize extends Sprite
	{
		
		public static const RESIZED:String = 'resized';
		public static const ENCODED:String = 'encoded';
		
		private var bmpdOrig:BitmapData;
		private var al_jpegencoder: Object;
		
		public var _resultBmpData:BitmapData;
		public function get resultBmpData():BitmapData {
			return _resultBmpData.clone();
		}
		public var resultBytes:ByteArray;
		
		public var filtersList:XMLList;
		
		public var autoEncode:Boolean = false;
		
		public var widthMax:int;
		public var heightMax:int;
		public var widthNew:int;
		public var heightNew:int;
		public var rotationNew:Number;
		public var outputQuality:int;
		
		public function ImageResize(widthMax:int=0,heightMax:int=0,rotationNew:Number=0,outputQuality:int=100)
		{
			super();
			this.widthMax = widthMax;
			this.heightMax = heightMax;
			this.rotationNew = rotationNew;
			this.outputQuality = outputQuality;
			
			/* init alchemy object */
			
            var init:CLibInit = new CLibInit(); //get library obejct
            al_jpegencoder = init.init(); // initialize library exported class
            /**/
		}
		
		public function loadReference(fileRef:FileReference):void {
			fileRef.load()
			fileRef.addEventListener(Event.COMPLETE, onFileRef);
		}
		
		private function onFileRef( e:Event ):void {
			var file:FileReference = e.target as FileReference;
			file.removeEventListener(Event.COMPLETE, onFileRef);
			this.loadBytes( file.data );
		}
		
		public function loadBytes(bytes:ByteArray):void {
			var image:Loader = new Loader();
    		image.loadBytes( bytes );
    		image.contentLoaderInfo.addEventListener(Event.COMPLETE, onImageReady );
    		this.addChild( image );
		}
		
		private function onImageReady(e:Event):void {
        	var image:Loader = e.target.loader as Loader;
        	image.contentLoaderInfo.removeEventListener(Event.COMPLETE, onImageReady );
        	var imageBmp:Bitmap = image.content as Bitmap;
        	//---calculate new size
        	if(widthMax > 0 || heightMax > 0) {
        		var scaled:Object = BitmapDataProcess.scaleCalc(imageBmp.width,imageBmp.height,widthMax,heightMax);
        		widthNew = scaled.width; 
        		heightNew = scaled.height;
        	} else {
        		widthNew = widthMax; 
        		heightNew = heightMax;
        	}
        	trace('RESIZE::NEW::'+widthNew+'::'+heightNew);
        	
        	bmpdOrig = new BitmapData(imageBmp.width+(imageBmp.width%2), imageBmp.height+(imageBmp.height%2) );
        	bmpdOrig.draw( image, null, null, null, null, true );
        	
        	//---remove image
        	image.addEventListener(Event.REMOVED_FROM_STAGE, onImageRemoved,false,0,true);
        	imageBmp.bitmapData.dispose();
        	image.unload(); 	
        	image.parent.removeChild( image );
        	
  		}
  		private function onImageRemoved(e:Event):void {
  			(e.target as Loader).removeEventListener(Event.REMOVED_FROM_STAGE, onBmpRemoved);
        	//---time for filtering on bmp bitmapdatas
        	//---filtering
        	if(filtersList)
        	if(filtersList.length() > 0) {
        		var popImage:Image = new Image(bmpdOrig.width, bmpdOrig.height, ImageFormat.RGB);
        		popImage.loadBitmapData( bmpdOrig );
        		var filXML:XML;
				for each( filXML in filtersList) {
					var filId:String = String( filXML.attribute('id') );
					switch( filId ) {
						case 'levels':
		        			var filter1: LevelsCorrection = new LevelsCorrection( true );
		  			  		filter1.apply( popImage );
		  			  	break;
		  				case 'sharpen':
		  			  		var filter2: Sharpen = new Sharpen();
							filter2.apply( popImage );
						break;
						case 'contrast':
							var filter3: ContrastCorrection = new ContrastCorrection( 1.2 );
							filter3.apply( popImage );
						break;
					}
				}
				
  			  	bmpdOrig.dispose();
        		bmpdOrig = popImage.bitmapData.clone(); 
  			  	popImage.dispose();
        	}
        	
        	var bmp:Bitmap = new Bitmap(bmpdOrig, PixelSnapping.NEVER, true);
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
        	
        	bmp.addEventListener(Event.ENTER_FRAME, onImageReady2);
        	this.addChild( bmp );
        }
        
        private function onImageReady2(e:Event):void {
        	var bmp:Bitmap = e.target as Bitmap;
        	bmp.removeEventListener(Event.ENTER_FRAME, onImageReady2);
        	
        	//---draw resized rotated bitmap
        	_resultBmpData = new BitmapData(Math.round(bmp.width), Math.round(bmp.height) );
        	_resultBmpData.draw( this, null, null, null, null, true ); 
        	
        	//---dispose
        	bmp.bitmapData.dispose();
        	bmp.addEventListener(Event.REMOVED_FROM_STAGE, onBmpRemoved, false,0,true);
        	bmp.parent.removeChild( bmp );
        	
        	//---dispatch event image resized
        	dispatchEvent( new Event( RESIZED ) );
        }
        
        private function onBmpRemoved(e:Event):void {
        	(e.target as Bitmap).removeEventListener(Event.REMOVED_FROM_STAGE, onBmpRemoved);
        	if(autoEncode === true) {
        		this.encode();
        	}
        }
        
        public function encode(bmpd:BitmapData=null):void {
        	if(!bmpd) bmpd = _resultBmpData;
        	
        	resultBytes = new ByteArray();
        	
  			var baSource: ByteArray = bmpd.getPixels( new Rectangle( 0, 0, bmpd.width, bmpd.height) );			
			baSource.position = 0;
			al_jpegencoder.encodeAsync(onCompressFinished, baSource, resultBytes, bmpd.width, bmpd.height, this.outputQuality );
			/**/
        	/*
        	var jpgEnc:JPEGEncoder = new JPEGEncoder( this.outputQuality );
        	resultBytes = jpgEnc.encode( bmpd );
        	onCompressFinished(null);
        	/**/
        }
        
        private function onCompressFinished( out:ByteArray ):void {
        	
        	
           	//---dispatch event bytes encoded  
        	dispatchEvent( new Event( ENCODED ) );
        }
        
        public function dispose():void {
        	_resultBmpData.dispose();
        	this.parent.removeChild( this );
        }
	}
}