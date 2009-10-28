package net.fundekave.fuup.model
{    
      
      import flash.events.ErrorEvent;
      import flash.events.Event;
      import flash.events.ProgressEvent;
      import flash.utils.ByteArray;
      import flash.utils.setTimeout;
      
      import net.fundekave.Application;
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.common.constants.ActionConstants;
      import net.fundekave.fuup.model.vo.FileVO;
      import net.fundekave.fuup.view.components.FileView;
      import net.fundekave.lib.FileUpload;
      import net.fundekave.lib.ImageResize;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;
      import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;
        
      public class FileProxy extends Proxy implements IProxy
      {
      	
		public static const NAME:String = 'fileProxy';
        
        //---global settings
        public var filtersList:XMLList;
        public var widthMax:Number = 500;
        public var heightMax:Number = 500;
        public var outputQuality:Number = 80;
		public var crop:Boolean = false;
        
        //uploading
        public var serviceURL:String;
		public var maxSize:int = 100000;
        public var chunkSize:int = 5000;
        public var uploadLimit:int = 3;
        
        public var fileList:Array = new Array();
        private var fileVO:FileVO;
        private var currentFile:int = 0;
		
		private var _useFilters:Boolean = true;
		private var _useFiltersPrev:Boolean = true;
		public function set useFilters(b:Boolean):void {
			_useFilters = b;	
		}
		public function get useFilters():Boolean {
			return _useFilters;
		}
        
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
			fileVO.crop = crop;
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
				var compareW:int;
				var compareH:int;
				if(fileVO.encodedJPG) {
					compareW = fileVO.widthNew;
					compareH = fileVO.heightNew;
				} else {
					compareW = fileVO.widthOriginal
					compareH = fileVO.heightOriginal
				}
        		if(compareW > fileVO.widthMax || compareH > fileVO.heightMax || fileVO.rotation!=fileVO.rotationCurrent || _useFilters!=_useFiltersPrev) {
					fileVO.renderer.setLocalState( FileView.STATE_PROCESSING );
					var rot:Number = fileVO.rotation+fileVO.rotationFromOriginal;
					if(rot<0) rot += 360;
					if(rot>=360) rot -=360;
	        		var imageResize:ImageResize = new ImageResize(fileVO.widthMax,fileVO.heightMax,rot,fileVO.outputQuality);
					imageResize.crop = crop;
					fileVO.rotationFromOriginal = rot; 
					fileVO.rotation = 0;
					fileVO.rotationCurrent = fileVO.rotation;
	        		imageResize.autoEncode = true;
					if(_useFilters===true) {
	        			imageResize.filtersList = filtersList;
					}
	        		imageResize.loadBytes( fileVO.file.data );
	        		imageResize.addEventListener( ImageResize.ENCODED, onCompressFinished );
	        		Application.application.stage.addChild( imageResize );
				} else {
					//---skip file
					currentFile++;
					processFile();
				}
        	} else {
        		//---processing done
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
				_useFiltersPrev = _useFilters;
        	}
        }
        
        private function onCompressFinished( e:Event ):void {
        	var imageResize:ImageResize = e.target as ImageResize;
        	
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	fileVO.encodedJPG = imageResize.resultBytes;
			fileVO.widthNew = imageResize.widthNew;
			fileVO.heightNew = imageResize.heightNew;
			fileVO.renderer.updateThumb();
        
        	currentFile++;
        	//---send progress
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed:currentFile,total:fileList.length} );
        	
        	imageResize.dispose();
        	
        	setTimeout(processFile, 10);       
        }
        
        public function uploadFiles():void {
        	currentFile = 0;
        	uploadFile();
        }
        
        private function uploadFile():void {
        	var len:int = fileList.length;
			var noUpload:Boolean = true;
        	if(len > 0) {
				var i:int = 0;
				while(i<len) {
	        		fileVO = fileList[i] as FileVO;
	        		var data:ByteArray = (fileVO.encodedJPG)?(fileVO.encodedJPG):(fileVO.file.data);
					if(data.length < this.maxSize) {
		        		var fileUpload:FileUpload = new FileUpload(serviceURL, fileVO.filename, chunkSize, uploadLimit);
		        		fileUpload.addEventListener( FileUpload.COMPLETE, onUploadComplete );
		        		fileUpload.addEventListener( FileUpload.PROGRESS, onUploadProgress );
		        		fileUpload.addEventListener( FileUpload.ERROR, onUploadError );
		        		fileUpload.uploadBytes( data );
						noUpload=false;
						break;
					} else {
						//---show error on file
						//---it wont get here because it is stopped on checking before upload						
					}
					i++;
				}
        	} 
        	
			if(noUpload===true) {
	    		//---upload complete
	    		trace('UPLOAD COMPLETE');
	    		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
				sendNotification( ApplicationFacade.CALLBACK, ExtInterfaceProxy.UPLOAD_COMPLETE );
			}
        	
        }
        
        private function onUploadComplete(e:Event):void {
        	sendNotification( ApplicationFacade.FILE_DELETE, fileVO );
        	currentFile++;
			sendNotification( ApplicationFacade.CALLBACK, ExtInterfaceProxy.UPLOAD_ONE_COMPLETE );
        	uploadFile();
        }
        
        private function onUploadProgress(e:ProgressEvent):void {
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed: currentFile + (e.bytesLoaded/e.bytesTotal) } );
        }
        
        private function onUploadError(e:ErrorEvent):void {
			var configProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
			fileVO.renderer.updateStatus(configProxy.lang.uploaderror,false,1);
        	trace('TOTAL SERVICE ERROR');
        	sendNotification( ApplicationFacade.SERVICE_ERROR, 'Service error' );
        }
   
             
	}
}