package net.fundekave.fuup.model
{    
      
      import flash.events.ErrorEvent;
      import flash.events.Event;
      import flash.events.ProgressEvent;
      import flash.utils.setTimeout;
      
      import net.fundekave.Application;
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.common.constants.ActionConstants;
      import net.fundekave.fuup.model.vo.*;
      import net.fundekave.lib.FileUpload;
      import net.fundekave.lib.ImageResize;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;
      import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;
        
      public class FileProxy extends Proxy implements IProxy
      {
      	
		public static const NAME:String = 'fileProxy';
        
        //---global settings
        [Bindable]
        public var widthMax:Number = 500;
        [Bindable]
        public var heightMax:Number = 500;
        [Bindable]
        public var outputQuality:Number = 80;
        
        public var fileList:Array = new Array();
        
        private var currentFile:int = 0;
        
        //uploading
        private var fileVO:FileVO;
        private var serviceURL:String;
        private var chunkSize:int = 5000;
        private var uploadLimit:int = 3;
        
        public function FileProxy( )
        {
			super( NAME );
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
        }
        
        //---processing
        public function processFiles():void {
        	currentFile = 0;
        	processFile();
        }
        
        private function processFile():void {
        	var len:int = fileList.length;
        	if(currentFile < len) {
        		var fileVO:FileVO = fileList[currentFile] as FileVO;
        		fileVO.renderer.statusStr = 'Processing';
        		
        		var imageResize:ImageResize = new ImageResize(fileVO.widthMax,fileVO.heightMax,fileVO.rotation,fileVO.outputQuality);
        		imageResize.autoEncode = true;
        		var configProxy: ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
        		imageResize.filtersList = configProxy.filters;
        		imageResize.loadBytes( fileVO.file.data );
        		imageResize.addEventListener( ImageResize.ENCODED, onCompressFinished );
        		Application.application.stage.addChild( imageResize );
        		
        	} else {
        		//---processing done
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        	}
        }
        
        private function onCompressFinished( e:Event ):void {
        	var imageResize:ImageResize = e.target as ImageResize;
        	
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	fileVO.encodedJPG = imageResize.resultBytes;
        	fileVO.renderer.statusStr = 'Ready. New image size: '+ String(Math.round(imageResize.resultBytes.length/1024)) + 'kB';
        
        	currentFile++;
        	//---send progress
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed:currentFile,total:fileList.length} );
        	
        	imageResize.dispose();
        	
        	setTimeout(processFile, 10);       
        }
        
        public function uploadFiles():void {
        	var configProxy: ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
	        serviceURL = String( configProxy.getService('files') );
	        chunkSize = Number( configProxy.getValue('chunkSize') );
	        uploadLimit = Number( configProxy.getValue('chunkLimit') );
	        	
        	currentFile = 0;

        	uploadFile();
        }
        
        private function uploadFile():void {
        	var len:int = fileList.length;
        	if(len > 0) {
        		fileVO = fileList[len-1] as FileVO;
        		
        		var fileUpload:FileUpload = new FileUpload(serviceURL,fileVO.filename, chunkSize, uploadLimit);
        		fileUpload.addEventListener( FileUpload.COMPLETE, onUploadComplete );
        		fileUpload.addEventListener( FileUpload.PROGRESS, onUploadProgress );
        		fileUpload.addEventListener( FileUpload.ERROR, onUploadError );
        		fileUpload.uploadBytes( fileVO.encodedJPG );

	        	
        	} else {
        		
        		//---upload complete
        		trace('UPLOAD COMPLETE');
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        		
        	}
        }
        
        private function onUploadComplete(e:Event):void {
        	sendNotification( ApplicationFacade.FILE_DELETE, fileVO );
        	currentFile++;
        	uploadFile();
        }
        
        private function onUploadProgress(e:ProgressEvent):void {
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed: currentFile + (e.bytesLoaded/e.bytesTotal) } );
        }
        
        private function onUploadError(e:ErrorEvent):void {
        	trace('TOTAL SERVICE ERROR');
        	sendNotification( ApplicationFacade.SERVICE_ERROR, 'Service error' );
        }
   
             
	}
}