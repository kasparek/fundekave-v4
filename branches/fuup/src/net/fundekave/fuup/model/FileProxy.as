package net.fundekave.fuup.model
{    
      
      import de.popforge.imageprocessing.core.Image;
      import de.popforge.imageprocessing.core.ImageFormat;
      import de.popforge.imageprocessing.filters.color.ContrastCorrection;
      import de.popforge.imageprocessing.filters.color.LevelsCorrection;
      import de.popforge.imageprocessing.filters.convolution.Sharpen;
      
      import flash.display.Bitmap;
      import flash.display.BitmapData;
      import flash.display.Loader;
      import flash.display.PixelSnapping;
      import flash.events.Event;
      import flash.events.IOErrorEvent;
      import flash.events.SecurityErrorEvent;
      import flash.net.URLVariables;
      import flash.utils.ByteArray;
      import flash.utils.setTimeout;
      
      import mx.utils.Base64Encoder;
      
      import net.fundekave.Application;
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.common.constants.ActionConstants;
      import net.fundekave.fuup.model.vo.*;
      import net.fundekave.lib.BitmapDataProcess;
      import net.fundekave.lib.JPEGEncoder;
      import net.fundekave.lib.Service;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;
      import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;
        
      public class FileProxy extends Proxy implements IProxy
      {
      	
		public static const NAME:String = 'fileProxy';
        
        private var al_jpegencoder: Object;
        
        //---global settings
        [Bindable]
        public var widthMax:Number = 500;
        [Bindable]
        public var heightMax:Number = 500;
        [Bindable]
        public var outputQuality:Number = 80;
        
        public var fileList:Array = new Array();
        
        public function FileProxy( )
        {
			super( NAME );
			
			/* init alchemy object */
			/*
            var init:CLibInit = new CLibInit(); //get library obejct
            al_jpegencoder = init.init(); // initialize library exported class
            /**/
        }
        
        public function updateFiles():void {
        	var i:int;
        	var len:int = fileList.length;
        	if(len > 0) {
        		for(i=0;i<len;i++) {
        			var fileVO:FileVO = fileList[i] as FileVO;
        			updateFile( fileVO );
        		}
        	}
        }
        public function updateFile( fileVO:FileVO ):void {
        	if(fileVO.sizeInheritance == true) {
        		fileVO.widthMax = widthMax
        		fileVO.heightMax = heightMax
        	}
        	fileVO.outputQuality = outputQuality
        	
        	var scaled:Object = BitmapDataProcess.scaleCalc(fileVO.widthOriginal, fileVO.heightOriginal,fileVO.widthMax,fileVO.heightMax);
        	fileVO.widthNew = scaled.width;
        	fileVO.heightNew = scaled.height;
        }
        
        //---processing
        private var currentFile:int = 0;
        private var currentChunk:int = 0;
        private var numChunks:int = 0;
        
        
        public function processFiles():void {
        	currentFile = 0;
        	processFile();
        }
        
        private function processFile():void {
        	var len:int = fileList.length;
        	if(currentFile < len) {
        		var fileVO:FileVO = fileList[currentFile] as FileVO;
        		
        		var image:Loader = new Loader();
        		image.loadBytes( fileVO.file.data );
        		image.contentLoaderInfo.addEventListener(Event.COMPLETE, onImageReady );
        		Application.application.thumbHolder.addChild( image );
        		
        	} else {
        		//---processing done
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        	}
        }
        
        private var baout:ByteArray;
        
        public static function deg2rad(deg:Number):Number {
			return deg * Math.PI / 180;
		}
		        
        private function onImageReady(e:Event):void {
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	fileVO.renderer.statusStr = 'Processing';
        	
        	var image:Loader = e.target.loader as Loader;
        	image.contentLoaderInfo.removeEventListener(Event.COMPLETE, onImageReady );
        	
        	var bmpdOrig:BitmapData = new BitmapData(fileVO.widthOriginal+(fileVO.widthOriginal%2), fileVO.heightOriginal+(fileVO.heightOriginal%2) );
        	bmpdOrig.draw( image );
        	
        	//---time for filtering on bmp bitmapdatas
        	//---filtering
        	var configProxy: ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
        	if(configProxy.filters.length() > 0) {
        		var popImage:Image = new Image(bmpdOrig.width, bmpdOrig.height, ImageFormat.RGB);
        		popImage.loadBitmapData( bmpdOrig );
        		var filXML:XML;
				for each( filXML in configProxy.filters) {
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
        	bmp.width = fileVO.widthNew;
        	bmp.height = fileVO.heightNew;
        	//---rotate bitmap
        	bmp.rotation = fileVO.rotation;
        	//---translate because of rotation
        	switch(fileVO.rotation) {
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
        	Application.application.thumbHolder.addChild( bmp );
        	     
        	//---remove image   	
        	image.parent.removeChild( image );
        }
        
        private function onImageReady2(e:Event):void {
        	var bmp:Bitmap = e.target as Bitmap;
        	bmp.removeEventListener(Event.ENTER_FRAME, onImageReady2);
        	
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	
        	//---draw resized rotated bitmap
        	var bmpd:BitmapData = new BitmapData(bmp.width, bmp.height );
        	bmpd.draw( Application.application.thumbHolder ); 
      		/*
  			baout = new ByteArray();
  			var baSource: ByteArray = bmpd.getPixels( new Rectangle( 0, 0, bmpd.width, bmpd.height) );			
			baSource.position = 0;

			al_jpegencoder.encodeAsync(onCompressFinished, baSource, baout, bmpd.width, bmpd.height, fileVO.outputQuality );
			/**/
						
        	var jpgEnc:JPEGEncoder = new JPEGEncoder( fileVO.outputQuality );
        	baout = jpgEnc.encode( bmpd );
        	onCompressFinished(null);
        	/**/
        	//---dispose
        	bmp.parent.removeChild( bmp );
        	bmpd.dispose();
        }
        
        private function onCompressFinished( out:ByteArray ):void {
        	
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	fileVO.encodedJPG = baout;
        	fileVO.renderer.statusStr = 'Ready. New image size: '+ String(Math.round(baout.length/1024)) + 'kB';
        	baout = new ByteArray();
        
        	currentFile++;
        	//---send progress
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed:currentFile,total:fileList.length} );
        	
        	setTimeout(processFile, 10);       
        	
        }
        
        //uploading
        private var fileVO:FileVO;
        private var serviceURL:String;
        public var chunkSize:int = 5000;
        private var uploadLimit:int = 3;
        private var currentChunks:Array;
        private var chunksUploading:int = 0
        public function uploadFiles():void {
        	currentFile = 0;
        	currentChunk = 0;
        	uploadFile();
        }
        
        private function uploadFile():void {
        	var len:int = fileList.length;
        	if(len > 0) {
        		fileVO = fileList[len-1] as FileVO;
        		
        		var b64enc:Base64Encoder = new Base64Encoder();
        		b64enc.encodeBytes( fileVO.encodedJPG );
        		var encodedStr:String = b64enc.toString();
        	
        		//---prepare all chunks
	        	var chunksNum:int = Math.ceil( encodedStr.length / chunkSize );
	        	currentChunks = [];
	        	for(var i:int=0;i < chunksNum; i++) {
	        		currentChunks.push( {filename:fileVO.filename ,seq:i,total:chunksNum,data:encodedStr.slice( i*chunkSize, (i*chunkSize)+chunkSize )} );	
	        	}
	        	
	        	currentChunk = 0;
	        	numChunks = Number(currentChunks.length);
	        	
	        	encodedStr = null;
	        	 
	        	var configProxy: ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
	        	serviceURL = String( configProxy.getService('files') );
	        	
	        	upload();
	        	
        	} else {
        		
        		//---upload complete
        		trace('UPLOAD COMPLETE');
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        		
        	}
        }
        
        
        public function upload():void        
        {
        	if(currentChunks.length > 0 && chunksUploading < uploadLimit) {
        		
        		//---prepare service
	        	var service:Service = new Service();
	        	service.addEventListener(Event.COMPLETE, onServiceComplete );
	        	service.addEventListener(IOErrorEvent.IO_ERROR, onServiceError );
	        	service.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError );
	        	service.addEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError );
        		
        		var vars:URLVariables = new URLVariables();
        		var dataObj:Object = currentChunks.shift();
     			vars.data = dataObj.data;  
     			vars.seq = dataObj.seq;
     			vars.total = dataObj.total;
     			vars.filename = dataObj.filename;
        		
        		service.url = serviceURL;
        		service.variables = vars; 
				service.send();
				
				chunksUploading++;
				
				trace('CHUNK::UPLOADING::file::'+String(currentFile)+'::chunk::'+String(currentChunk)+'/'+String(numChunks));
				
				//---start more chunks if uploadLimit
				setTimeout( upload, 200 );
        	}
        }
        private function onServiceComplete(e:Event):void
        {   
        	
        	var service:Service = e.target as Service;
	        
	        if(service.data != '1') {
	        	trace('SERVICE RETURN ERROR::ANOTHER ATTEMPT');
	        	service.failed();
	        	return;
	        }
	        
	        service.removeEventListener(Event.COMPLETE, onServiceComplete );
	        service.removeEventListener(IOErrorEvent.IO_ERROR, onServiceError );
	        service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError );
	        service.removeEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError );
        	
			chunksUploading--;
			currentChunk++;
			
			trace('CHUNK::DONE::file::'+String(currentFile)+'::chunk::'+String(currentChunk)+'/'+String(numChunks));
			
			if(chunksUploading < uploadLimit && currentChunks.length > 0) {
				upload();
			}
			
			//---send progress
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed: currentFile + (currentChunk/numChunks) } );
			
			if(chunksUploading == 0 && currentChunks.length == 0) {
				//---upload done
        		trace('FILE COMPLETE');
        		//fileVO.renderer.statusStr = 'Upload DONE';
        		sendNotification( ApplicationFacade.FILE_DELETE, fileVO );
        		
        		currentFile++;
        		uploadFile();
			}
			
        }
   
        private function onServiceError(e:Event):void
        {
        	var service:Service = e.target as Service;
        	service.failed();
        	
        	trace('Connection Error::another attempt');
  			
        }
        
        private function onServiceTotalError(e:Event):void {
        	trace('TOTAL SERVICE ERROR');
        	
        	var service:Service = e.target as Service;
        	service.removeEventListener(Event.COMPLETE, onServiceComplete );
	        service.removeEventListener(IOErrorEvent.IO_ERROR, onServiceError );
	        service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError );
	        service.removeEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError );
        	
        	sendNotification( ApplicationFacade.SERVICE_ERROR, 'Service error' );
        }      
	}
}