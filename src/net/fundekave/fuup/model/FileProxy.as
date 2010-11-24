package net.fundekave.fuup.model
{    
            
      import flash.events.ErrorEvent;
      import flash.events.Event;
      import flash.events.ProgressEvent;
      import flash.net.FileReference;
      import flash.utils.ByteArray;
      import flash.utils.setTimeout;
      
      import net.fundekave.Application;
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.common.constants.ActionConstants;
      import net.fundekave.fuup.common.constants.StateConstants;
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
		
		public var state:String;
        
        //---global settings
        public var filtersList:XMLList;
        public var widthMax:Number = 500;
        public var heightMax:Number = 500;
        public var outputQuality:Number = 80;
		public var crop:Boolean = false;
		public var displayContent:Boolean = true;
        
        //uploading
        public var serviceURL:String;
		public var authToken:String;
		public var maxSize:int = 100000;
        public var chunkSize:int = 5000;
        public var uploadLimit:int = 3;
        
        public var fileList:Array = new Array();
        private var fileVO:FileVO;
        private var currentFile:int = 0;
		private var progressFile:int = 0;
		
		public var useFilters:Boolean = true;
		public var resize:Boolean=false;
		
		public function initSettings(v:Boolean):void {
			useFilters = v;
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
       		fileVO.widthMax = widthMax
       		fileVO.heightMax = heightMax
        	fileVO.outputQuality = outputQuality
			fileVO.crop = crop;
			fileVO.showThumb = displayContent;
        }
        
        //---processing
        public function processFiles():void {
			isCancel = false;
        	currentFile = 0;
			progressFile = 0;
        	processFile();
        }
		
		public function cancel():void {
			isCancel = true;
		}
        
		private var imageResize:ImageResize;
        private function processFile(onlyOne:Boolean=false):Boolean {
        	var len:int = fileList.length;
        	if(currentFile < len && isCancel===false) {
        		var fileVO:FileVO = fileList[currentFile] as FileVO;
				var compareW:int = 0;
				var compareH:int = 0;
				var fileSize:uint = fileVO.file.size;
				if(fileVO.encodedJPG) {
					fileSize = fileVO.encodedJPG.length;
					compareW = fileVO.widthNew;
					compareH = fileVO.heightNew;
				} else {
					compareW = fileVO.widthOriginal
					compareH = fileVO.heightOriginal
				}
        		if(compareW > fileVO.widthMax || compareH > fileVO.heightMax || fileVO.rotation!=fileVO.rotationCurrent || useFilters!=fileVO.useFiltersPrev
					|| fileSize > this.maxSize) {
					fileVO.useFiltersPrev=useFilters;
					fileVO.renderer.setLocalState( FileView.STATE_PROCESSING );
					var rot:Number = fileVO.rotation+fileVO.rotationFromOriginal;
					if(rot<0) rot += 360;
					if(rot>=360) rot -=360;
	        		imageResize = new ImageResize(fileVO.widthMax,fileVO.heightMax,rot,fileVO.outputQuality);
					imageResize.crop = crop;
					fileVO.rotationFromOriginal = rot; 
					fileVO.rotation = 0;
					fileVO.rotationCurrent = fileVO.rotation;
	        		imageResize.autoEncode = true;
					if(useFilters===true) {
	        			imageResize.filtersList = filtersList;
					}
					var loadBytes:Boolean = true;
					if(!fileVO.file.data) {
						loadBytes = false
					} else if(fileVO.file.data.length==0) {
						loadBytes = false
					}
					if(loadBytes === true) {
	        			imageResize.loadBytes( fileVO.file.data );
					} else {
						imageResize.loadReference( fileVO.file );
					}
	        		imageResize.addEventListener( ImageResize.ENCODED, onCompressFinished,false,0,true );
	        		Application.application.stage.addChild( imageResize );
				} else {
					if(onlyOne===false) {
						//---skip file
						currentFile++;
						progressFile++;
						processFile();
					} else {
						return true;
					}
				}
        	} else {
        		//---processing done
        		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        	}
			return false;
        }
        
        private function onCompressFinished( e:Event ):void {
			imageResize.removeEventListener(ImageResize.ENCODED, onCompressFinished );
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
			fileVO.encodedJPG = new ByteArray();
			fileVO.encodedJPG.writeBytes(imageResize.resultBytes);
			fileVO.widthNew = imageResize.widthNew;
			fileVO.heightNew = imageResize.heightNew;
			//fileVO.renderer.updateThumb();
			var statemachine:StateMachine
			if(state == StateConstants.STATE_UPLOADING) {
				setTimeout(uploadFile,500);
			} else {
	        	currentFile++;
				progressFile++;
	        	//---send progress
	        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed:progressFile,total:fileList.length} );
	        	setTimeout(processFile, 10);
			}
			imageResize.dispose();
			imageResize=null;
        }
        
        public function uploadFiles():void {
			isCancel = false;
        	currentFile = 0;
			progressFile=0;
        	uploadFile();
        }
        
		private var isCancel:Boolean = false;
        private function uploadFile():void {
        	if(fileList.length > 0 && isCancel===false) {
				if(this.resize) {
					if(!processFile(true)) return;
				}
				fileVO = fileList[0] as FileVO;
        		var fileUpload:FileUpload = new FileUpload(serviceURL, fileVO.filename, chunkSize, uploadLimit);
				fileUpload.extraVars = {auth:this.authToken};
        		fileUpload.addEventListener( FileUpload.COMPLETE, onUploadComplete,false,0,true );
        		fileUpload.addEventListener( FileUpload.PROGRESS, onUploadProgress,false,0,true );
        		fileUpload.addEventListener( FileUpload.ERROR, onUploadError,false,0,true );
        		if(!fileVO.encodedJPG) fileUpload.uploadReference( fileVO.file );
				else fileUpload.uploadBytes( fileVO.encodedJPG );
        	} else {
	    		//---upload complete
	    		trace('FILEPROXY::UPLOAD COMPLETE');
	    		sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
				sendNotification( ApplicationFacade.CALLBACK, ExtInterfaceProxy.UPLOAD_COMPLETE );
			}
        }
        
        private function onUploadComplete(e:Event):void {
			progressFile++;
        	sendNotification( ApplicationFacade.FILE_DELETE, fileVO );
			sendNotification( ApplicationFacade.CALLBACK, ExtInterfaceProxy.UPLOAD_ONE_COMPLETE );
        	setTimeout(uploadFile,500);
			trace('FILEPROXY::UPLOAD COMPLETE EVENT');
        }
        
        private function onUploadProgress(e:ProgressEvent):void {
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed: progressFile + (e.bytesLoaded/e.bytesTotal) } );
			trace('FILEPROXY::UPLOAD PROGRESS');
        }
        
        private function onUploadError(e:ErrorEvent):void {
			var configProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
			if(displayContent===true) {
				fileVO.renderer.updateStatus(configProxy.lang.uploaderror,false,1);
			} else {
				sendNotification( ApplicationFacade.FILE_DELETE, fileVO);
			}
			trace('FILEPROXY::TOTAL SERVICE ERROR');
        	sendNotification( ApplicationFacade.SERVICE_ERROR, 'Service error' );
			sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
        }
   
             
	}
}