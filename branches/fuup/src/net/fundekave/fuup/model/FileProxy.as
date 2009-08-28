package net.fundekave.fuup.model
{    

      import cmodule.jpegencoder.CLibInit;
      
      import flash.display.BitmapData;
      import flash.display.Loader;
      import flash.events.Event;
      import flash.events.IOErrorEvent;
      import flash.geom.Matrix;
      import flash.geom.Rectangle;
      import flash.net.URLLoader;
      import flash.net.URLRequest;
      import flash.net.URLVariables;
      import flash.utils.ByteArray;
      import flash.utils.setTimeout;
      
      import mx.utils.Base64Encoder;
      
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.common.constants.ActionConstants;
      import net.fundekave.fuup.model.vo.*;
      import net.fundekave.lib.BitmapDataProcess;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;
      import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;
        
      public class FileProxy extends Proxy implements IProxy
      {
		public static const NAME:String = 'fileProxy';
        
        private var al_jpegencoder: Object;
        
        //---global settings
        [Bindable]
        public var widthMax:Number = 700;
        [Bindable]
        public var heightMax:Number = 700;
        [Bindable]
        public var outputQuality:Number = 80;
        
        public var fileList:Array = new Array();
        
        public function FileProxy( )
        {
			super( NAME );
			
			/* init alchemy object */
            var init:CLibInit = new CLibInit(); //get library obejct
            al_jpegencoder = init.init(); // initialize library exported class
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
        	
        	var scaled:Object = BitmapDataProcess.scaleCalc(fileVO.widthOriginal,fileVO.heightOriginal,fileVO.widthMax,fileVO.heightMax);
        	fileVO.widthNew = scaled.width;
        	fileVO.heightNew = scaled.height;
        }
        
        //---processing
        private var currentFile:int = 0;
        
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
        		fileVO.renderer.addChild( image ); 
        		
        	} else {
        		//---processing done
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        	}
        }
        
        private var baout:ByteArray;
        
        private function onImageReady(e:Event):void {
        	var image:Loader = e.target.loader as Loader;
        	image.contentLoaderInfo.removeEventListener(Event.COMPLETE, onImageReady );
        	
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	fileVO.renderer.statusStr = 'Processing';
        	
        	var ratio:Number = fileVO.widthNew / fileVO.widthOriginal;
        	var matrix:Matrix = new Matrix();
  			matrix.scale( ratio, ratio );
  			
  			fileVO.widthNew = Math.round(fileVO.widthNew);
  			fileVO.heightNew = Math.round(fileVO.heightNew);
  			
  			baout = new ByteArray();
 
  			var bmpd:BitmapData = new BitmapData( fileVO.widthNew, fileVO.heightNew );	
  			bmpd.draw( image.content, matrix, null, null, null, true );
			var baSource: ByteArray = bmpd.getPixels( new Rectangle( 0, 0, fileVO.widthNew, fileVO.heightNew) );			
			baSource.position = 0;

			al_jpegencoder.encodeAsync(onCompressFinished, baSource, baout, bmpd.width, bmpd.height, fileVO.outputQuality );
			
			/*			
        	var jpgEnc:JPEGEncoder = new JPEGEncoder( fileVO.outputQuality );
        	baout = jpgEnc.encode( bmpd );
        	onCompressFinished(null);
        	/**/
        	
        	image.parent.removeChild( image );
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
        private var service:URLLoader
        private var serviceURL:String;
        private var chunkSize:int = 10000;
        private var uploadLimit:int = 5;
        private var currentChunks:Array;
        public function uploadFiles():void {
        	currentFile = 0;
        	uploadFile();
        }
        
        private function uploadFile():void {
        	var len:int = fileList.length;
        	if(currentFile < len) {
        		var fileVO:FileVO = fileList[currentFile] as FileVO;
        		
        		var b64enc:Base64Encoder = new Base64Encoder();
        		b64enc.encodeBytes( fileVO.encodedJPG );
        		var encodedStr:String = b64enc.toString();
        	
	        	var chunksNum:int = Math.ceil( encodedStr.length / chunkSize );
	        	currentChunks = [];
	        	for(var i:int=0;i < chunksNum; i++) {
	        		currentChunks.push( '<data><chunk seq="'+i+'" to="'+chunksNum+'"><![CDATA['+encodedStr.slice( i*chunkSize, (i*chunkSize)+chunkSize )+']]></chunk></data>' );	
	        	}
	        	
	        	encodedStr = null;
	        	
	        	service = new URLLoader();
	        	service.addEventListener(Event.COMPLETE, onServiceComplete );
	        	service.addEventListener(IOErrorEvent.IO_ERROR, onServiceError );
	        	 
	        	var configProxy: ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
	        	serviceURL = String( configProxy.getService('files') );
	        	
	        	upload();
	        	//---take chunks
	        	//---checking progress
        		//---when finishid send another chunks
        	} else {
        		//---upload complete
        		trace('UPLOAD COMPLETE');
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        	}
        }
        
        private var chunksUploading:int = 0
        public function upload():void        
        {
        	while(currentChunks.length > 0 && chunksUploading < uploadLimit) {
        		
        		var serviceLoc:URLLoader = new URLLoader();
	        	serviceLoc.addEventListener(Event.COMPLETE, onServiceComplete );
	        	serviceLoc.addEventListener(IOErrorEvent.IO_ERROR, onServiceError );
        		
        		var vars:URLVariables = new URLVariables();
     			vars.data = currentChunks.shift(); 
        		
				var req:URLRequest = new URLRequest( serviceURL );
				req.method = 'POST';
				req.data = vars;
				serviceLoc.load( req );
				trace('CHUNK::UPLOADING::rest::'+String(currentChunks.length));
				chunksUploading++;
        	}
        }
        private function onServiceComplete(e:Event):void
        {       
        	var fileVO:FileVO = fileList[currentFile] as FileVO;  
			chunksUploading--;
			trace('CHUNK::DONE::rest::'+String(currentChunks.length));
			if(chunksUploading < uploadLimit && currentChunks.length > 0) {
				upload();
			}
			if(chunksUploading==0 && currentChunks.length == 0) {
				//---upload done
        		trace('FILE COMPLETE');
        		fileVO.renderer.statusStr = 'Upload DONE';
        		
        		currentFile++;
        		uploadFile();
			}
        }
   
        private function onServiceError(e:Event):void
        {
        	trace('Connection Error');
  			sendNotification( ApplicationFacade.SERVICE_ERROR, 'Service error' );
        }       
	}
}