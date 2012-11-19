package net.fundekave.fuup.model
{
	
	import flash.events.ErrorEvent;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.events.SecurityErrorEvent;
	import flash.net.FileReference;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	import flash.net.URLVariables;
	import flash.net.URLRequestMethod;
	import flash.utils.ByteArray;
	import flash.utils.setTimeout;
	
	import net.fundekave.Application;
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.vo.FileVO;
	import net.fundekave.fuup.view.components.FileView;
	import net.fundekave.lib.FileUpload;
	import net.fundekave.lib.ImageResize;
	
	import org.puremvc.as3.multicore.interfaces.IProxy;
	import org.puremvc.as3.multicore.patterns.proxy.Proxy;
	
	public class FileProxy extends Proxy implements IProxy
	{
		
		public static const NAME:String = 'fileProxy';
		
		//---global settings
		public var widthMax:Number = 500;
		public var heightMax:Number = 500;
		public var outputQuality:Number = 80;
		public var showImages:Boolean = true;
		
		//uploading
		public var serviceURL:String;
		public var serviceVars:Object;
		public var chunkSize:int = 5000;
		public var uploadLimit:int = 3;
		public var sendFlushRequest:Boolean = true;
		
		public var fileList:Array = new Array();
		private var fileVO:FileVO;
		private var currentFile:int = 0;
		private var progressFile:int = 0;
		private var progressFileTotal:int = 0;
		
		private var isCancel:Boolean = false;
		private var imageResize:ImageResize;
		private var fileUploadList:Vector.<FileUpload>;
		
		public function FileProxy()
		{
			super(NAME);
			fileUploadList = new Vector.<FileUpload>;
		}
		
		public function updateFiles():void
		{
			var i:int, len:int = fileList.length;
			if (len > 0)
			{
				for (i = 0; i < len; i++)
				{
					updateFile(fileList[i] as FileVO);
				}
			}
		}
		
		public function updateFile(fileVO:FileVO):void
		{
			fileVO.widthMax = widthMax
			fileVO.heightMax = heightMax
			fileVO.outputQuality = outputQuality
			fileVO.showThumb = showImages;
		}
		
		public function cancel():void
		{
			isCancel = true;
			for each (var fileUpload:FileUpload in fileUploadList)
				fileUpload.stop();
			fileUploadList = new Vector.<FileUpload>;
			for each (var fileVO:FileVO in fileList)
			{
				fileVO.renderer.enabled = true;
				fileVO.renderer.updateStatus('');
			}
		}
		
		private function processFile():void
		{
			if (isCancel)
				return;
			if (currentFile > fileList.length)
				return;
			
			var fileVO:FileVO = fileList[currentFile] as FileVO;
			fileVO.renderer.setLocalState(FileView.BUSY);
			var compareW:int = 0;
			var compareH:int = 0;
			var fileSize:uint = fileVO.file.size;
			if (fileVO.encodedJPG)
			{
				fileSize = fileVO.encodedJPG.length;
				compareW = fileVO.widthNew;
				compareH = fileVO.heightNew;
			}
			else
			{
				compareW = fileVO.widthOriginal
				compareH = fileVO.heightOriginal
			}
			if (compareW > fileVO.widthMax || compareH > fileVO.heightMax || fileVO.rotation != fileVO.rotationCurrent)
			{
				var rot:Number = fileVO.rotation + fileVO.rotationFromOriginal;
				if (rot < 0)
					rot += 360;
				if (rot >= 360)
					rot -= 360;
				fileVO.rotationFromOriginal = rot;
				fileVO.rotation = 0;
				fileVO.rotationCurrent = fileVO.rotation;
				imageResize = new ImageResize(fileVO.widthMax, fileVO.heightMax, rot, fileVO.outputQuality);
				imageResize.addEventListener(ImageResize.RESIZED, onResizeFinished, false, 0, true);
				if (fileVO.file.data && fileVO.file.data.length > 0)
					imageResize.loadBytes(fileVO.file.data);
				else
					imageResize.loadReference(fileVO.file);
				Application.application.stage.addChild(imageResize);
			}
			else
			{
				uploadStart();
			}
		}
		
		private function onResizeFinished(e:Event):void
		{
			imageResize.removeEventListener(ImageResize.RESIZED, onResizeFinished);
			if (isCancel)
			{
				imageResize.dispose();
				imageResize = null;
				return;
			}
			imageResize.addEventListener(ImageResize.ENCODED, onEncodeFinished);
			imageResize.encode();
		}
		
		private function onEncodeFinished(e:Event):void
		{
			imageResize.removeEventListener(ImageResize.ENCODED, onEncodeFinished);
			
			var fileVO:FileVO = fileList[currentFile] as FileVO;
			fileVO.encodedJPG = new ByteArray();
			fileVO.encodedJPG.writeBytes(imageResize.resultBytes);
			fileVO.widthNew = imageResize.widthNew;
			fileVO.heightNew = imageResize.heightNew;
			
			imageResize.dispose();
			imageResize = null;
			
			fileVO.renderer.updateStatus('');
			
			setTimeout(uploadStart, 100);
		}
		
		private function uploadStart():void
		{
			fileVO = fileList[0] as FileVO;
			if(sendFlushRequest) {
				var flushRequest:URLRequest = new URLRequest(serviceURL);
				flushRequest.data = new URLVariables();
				for (var name:String in serviceVars) flushRequest.data[name] = serviceVars[name];
				flushRequest.data['flush'] = fileVO.filename;
				flushRequest.data['total'] = Math.ceil((fileVO.encodedJPG?fileVO.encodedJPG.length:fileVO.file.size)/chunkSize);
				flushRequest.method = URLRequestMethod.POST;
				var service:URLLoader = new URLLoader();
				service.addEventListener(Event.COMPLETE, flushRequestComplete);
				service.addEventListener(IOErrorEvent.IO_ERROR, onUploadError, false, 0, true);
				service.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onUploadError, false, 0, true);
				service.load(flushRequest);
			} else {
				flushRequestComplete(null);
			}
		}
		
		private function flushRequestComplete(e:Event):void {
			if (e) {
				var service:URLLoader = e.target as URLLoader;
				service.removeEventListener(Event.COMPLETE, flushRequestComplete);
				service.removeEventListener(IOErrorEvent.IO_ERROR, onUploadError);
				service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onUploadError);
			}
			var fileUpload:FileUpload = new FileUpload(serviceURL, fileVO.filename, chunkSize, uploadLimit);
			fileUploadList.push(fileUpload);
			fileUpload.extraVars = serviceVars;
			fileUpload.addEventListener(FileUpload.COMPLETE, onUploadComplete, false, 0, true);
			fileUpload.addEventListener(FileUpload.PROGRESS, onUploadProgress, false, 0, true);
			fileUpload.addEventListener(FileUpload.ERROR, onUploadError, false, 0, true);
			if (!fileVO.encodedJPG)
				fileUpload.uploadReference(fileVO.file);
			else
				fileUpload.uploadBytes(fileVO.encodedJPG);
		}
		
		public function uploadFiles():void
		{
			progressFileTotal = fileList.length;
			isCancel = false;
			currentFile = 0;
			progressFile = 0;
			uploadFile();
		}
		
		private function uploadFile():void
		{
			if (fileList.length > 0 && isCancel === false)
			{
				processFile();
			}
			else
			{
				//---upload complete
				sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.COMPLETE);
				sendNotification(ApplicationFacade.ACTION_SELECT);
			}
		}
		
		private function onUploadComplete(e:Event):void
		{
			var fileUpload:FileUpload = e.target as FileUpload;
			fileUploadList.splice(fileUploadList.indexOf(fileUpload), 1);
			
			progressFile++;
			sendNotification(ApplicationFacade.FILE_DELETE, fileVO);
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.IMAGE_UPLOADED, fileVO.filename);
			setTimeout(uploadFile, 500);
			trace('FILEPROXY::UPLOAD COMPLETE EVENT');
		}
		
		private function onUploadProgress(e:ProgressEvent):void
		{
			var p:Number = 0;
			p += (progressFile / progressFileTotal) * 100;
			p += (e.bytesLoaded / e.bytesTotal) * (100 / progressFileTotal);
			p = Math.round(p);
			sendNotification(ApplicationFacade.PROGRESS, p);
			trace('FILEPROXY::UPLOAD PROGRESS::' + p);
		}
		
		private function onUploadError(e:ErrorEvent):void
		{
			var configProxy:ConfigProxy = facade.retrieveProxy(ConfigProxy.NAME) as ConfigProxy;
			if (showImages === true)
			{
				fileVO.renderer.setLocalState(FileView.READY);
			}
			else
			{
				sendNotification(ApplicationFacade.FILE_DELETE, fileVO);
			}
			trace('FILEPROXY::TOTAL SERVICE ERROR');
			
			cancel();
			
			sendNotification(ApplicationFacade.SERVICE_ERROR, 'Service error');
			sendNotification(ApplicationFacade.ACTION_SELECT);
		}
	
	}
}