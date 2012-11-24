package net.fundekave.fuup.view
{
	
	import flash.events.Event;
	import flash.events.MouseEvent;
	
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.*;
	import net.fundekave.fuup.model.vo.FileVO;
	import net.fundekave.fuup.view.components.*;
	
	import org.puremvc.as3.multicore.interfaces.IMediator;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
	
	public class FilesViewMediator extends Mediator implements IMediator
	{
		public static const NAME:String = 'FilesViewMediator';
		
		public function FilesViewMediator(viewComponent:Object)
		{
			super(NAME, viewComponent);
		}
		
		override public function onRegister():void
		{
			
			filesView.addEventListener(FileView.FILE_CREATED, onFileCreated, false, 0, true);
			filesView.addEventListener(FileView.FILE_REMOVE, onFileRemove, false, 0, true);
			filesView.addEventListener(FilesView.FILE_CHECK_EXITS, onFileCheck, false, 0, true);
			filesView.addEventListener(FilesView.ACTION_LOAD, onLoad, false, 0, true);
			filesView.addEventListener(FilesView.ACTION_UPLOAD, onUpload, false, 0, true);
			filesView.addEventListener(FilesView.ACTION_CANCEL, onCancel, false, 0, true);
			filesView.addEventListener(FilesView.FILE_ERROR_NUMLIMIT, onFileErrorNumLimit, false, 0, true);
			filesView.addEventListener(FilesView.PROGRESS, onProgress, false, 0, true);
			filesView.addEventListener(FilesView.BROWSEIMGERROR, onBrowseImgError, false, 0, true);
			
			var configProxy:ConfigProxy = facade.retrieveProxy(ConfigProxy.NAME) as ConfigProxy;
			filesView.filesNumMax = Number(configProxy.config.settings.fileLimit);
			filesView.multiFiles = Number(configProxy.config.settings.multi) == 1 ? true : false;
			filesView.autoUpload = Number(configProxy.config.settings.autoUpload) == 1 ? true : false;
			filesView.showControls = Number(configProxy.config.settings.showControls) == 1 ? true : false;
			filesView.showImages  = Number(configProxy.config.settings.showImages) == 1 ? true : false;
			filesView.embedWidth = Number(configProxy.config.settings.appSize.width);
			filesView.embedHeight = Number(configProxy.config.settings.appSize.height);
			filesView.fileTypes = configProxy.config.settings.image.type;
			if(configProxy.config.settings.browseImg) filesView.browseImgUrl = configProxy.config.settings.browseImg;
			filesView.setup();
		}
		
		private function onBrowseImgError(e:Event):void {
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.TRACE, 'BrowseImg not loaded');
		}
		
		private function onProgress(e:Event):void 
		{
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.PROGRESS, String(filesView.progress));
		}
		
		private function onCancel(e:Event):void
		{
			var appMed:ApplicationMediator = facade.retrieveMediator(ApplicationMediator.NAME) as ApplicationMediator;
			if (appMed.state != ApplicationFacade.STATE_SELECTING) 
			{
				sendNotification(ApplicationFacade.ACTION_CANCEL);	
			}
		}
		
		protected function onFileCreated(e:Event):void
		{
			//---register file with proxy
			var proxy:FileProxy = facade.retrieveProxy(FileProxy.NAME) as FileProxy;
			var fileVO:FileVO = (e.target as FileView).fileVO;
			proxy.fileList.push(fileVO);
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.IMAGE_LOADED, fileVO.filename);
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.IMAGE_NUM, String(proxy.fileList.length));
			proxy.updateFile(fileVO);
		}
		
		protected function onFileRemove(e:Event):void
		{
			sendNotification(ApplicationFacade.FILE_DELETE, (e.target as FileView).fileVO);
		}
		
		protected function onFileCheck(e:Event):void
		{
			sendNotification(ApplicationFacade.FILE_CHECK_EXISTS, filesView.currFile.name);
		}
		
		protected function onFileErrorNumLimit(e:Event):void {
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.ERROR, ExtInterfaceProxy.ERROR_FILE_NUM_LIMIT);
			sendNotification(ApplicationFacade.ACTION_SELECT);
		}
		
		protected function onLoad(e:Event):void 
		{
			sendNotification(ApplicationFacade.ACTION_LOAD);
		}
		
		protected function onUpload(e:Event):void
		{
			sendNotification(ApplicationFacade.ACTION_UPLOAD);
		}
		
		override public function listNotificationInterests():Array
		{
			return [ApplicationFacade.STATE, ApplicationFacade.PROGRESS, ApplicationFacade.FILE_CHECK_FAIL, ApplicationFacade.FILE_CHECK_OK, ApplicationFacade.SERVICE_ERROR];
		}
		
		override public function handleNotification(note:INotification):void
		{
			var configProxy:ConfigProxy = facade.retrieveProxy(ConfigProxy.NAME) as ConfigProxy;
			var proxy:FileProxy = facade.retrieveProxy(FileProxy.NAME) as FileProxy;
			switch (note.getName())
			{
				case ApplicationFacade.STATE: 
					switch (note.getType())
				{
					case ApplicationFacade.STATE_SELECTING: 
						filesView.cancel();
						break;
					case ApplicationFacade.STATE_UPLOADING: 
						filesView.initProgress();
						break;
				}
					break;
				case ApplicationFacade.SERVICE_ERROR: 
					sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.ERROR, ExtInterfaceProxy.ERROR_UPLOAD);
					break;
				case ApplicationFacade.FILE_CHECK_FAIL: 
					filesView.failFile();
					break;
				case ApplicationFacade.FILE_CHECK_OK: 
					filesView.addFile();
					break;
				case ApplicationFacade.PROGRESS: 
					var progress:Number = Number(note.getBody());
					sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.PROGRESS, String(progress));
					filesView.toProgress(progress);
					break;
			}
		}
		
		protected function get filesView():FilesView
		{
			return viewComponent as FilesView;
		}
	}
}