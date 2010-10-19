package net.fundekave.fuup.view
{
	
	import flash.events.Event;
	
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.ActionConstants;
	import net.fundekave.fuup.common.constants.StateConstants;
	import net.fundekave.fuup.model.ConfigProxy;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.vo.FileVO;
	import net.fundekave.fuup.view.components.FileView;
	import net.fundekave.fuup.view.components.FilesView;
	
	import org.puremvc.as3.multicore.interfaces.IMediator;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
	import org.puremvc.as3.multicore.utilities.statemachine.State;
	import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;

	public class FilesViewMediator extends Mediator implements IMediator
	{
		public static const NAME:String = 'FilesViewMediator';
		
		public function FilesViewMediator( viewComponent:Object )
		{
			super( NAME, viewComponent );
		}
		
		override public function onRegister():void
		{
			
			filesView.addEventListener( FileView.FILE_CREATED, onFileCreated, false, 0, true );
			filesView.addEventListener( FileView.FILE_REMOVE, onFileRemove, false, 0, true );
			filesView.addEventListener( FilesView.FILE_CHECK_EXITS, onFileCheck, false, 0, true );
			filesView.addEventListener( FilesView.ACTION_PROCESS, onProcess, false, 0, true );
			filesView.addEventListener( FilesView.ACTION_UPLOAD, onUpload, false, 0, true );
			filesView.addEventListener( FilesView.ACTION_CANCEL, onCancel, false, 0, true );
			
			var configProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
			filesView.lang = configProxy.lang;
			filesView.filesNumMax = Number( configProxy.getValue("fileLimit") );
			filesView.multiFiles = Number( configProxy.getValue("multi") )==1 ? true : false;
			filesView.settingsVisible = Number( configProxy.getValue("settingsEnabled") )==1 ? true : false;
			
			filesView.settingsOn = Number( configProxy.getValue("settingsOn") )==1 ? true : false;
			
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			proxy.initSettings( filesView.settingsOn );
			
			filesView.autoProcess = Number( configProxy.getValue("autoProcess") )==1 ? true : false;
			filesView.autoUpload = Number( configProxy.getValue("autoUpload") )==1 ? true : false;
			filesView.displayContent = Number( configProxy.getValue("displayContent") )==1 ? true : false;
		}

		private function onCancel(e:Event):void
		{
			sendNotification( ApplicationFacade.CANCEL );
		}
		
		protected function onFileCreated( e:Event ):void {
			//---register file with proxy
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			var fileVO:FileVO = (e.target as FileView).fileVO;
			proxy.fileList.push( fileVO );
			proxy.updateFile( fileVO );
		}
		
		protected function onFileRemove( e:Event ):void {
			if(stateName===null || stateName == StateConstants.STATE_SETUPING) {
				var fileVO:FileVO = (e.target as FileView).fileVO;
				sendNotification( ApplicationFacade.FILE_DELETE, fileVO );
			}
		}
		
		protected function onFileCheck( e:Event ):void {
			sendNotification( ApplicationFacade.FILE_CHECK_EXISTS, filesView.currFile.name );
		}
				
		protected function onProcess(e:Event):void {
			var useFilters:Boolean = filesView.correctionsCheckbox.selected;
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			proxy.useFilters = useFilters;
			sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_PROCESS );
		}
		
		protected function onUpload(e:Event):void {
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			//---check if there are some files processed ready for upload
			if(proxy.fileList.length > 0) {
				sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_UPLOAD );
			}
		}
		
		override public function listNotificationInterests():Array
		{
			return [
					ApplicationFacade.CONFIG_LOADED,
					StateMachine.CHANGED,
					ApplicationFacade.GLOBAL_PROGRESS_INIT,
					ApplicationFacade.PROCESS_PROGRESS,
					ApplicationFacade.FILE_CHECK_FAIL,
					ApplicationFacade.FILE_CHECK_OK,
					ApplicationFacade.IMAGES_PROCESSED,
					ApplicationFacade.SERVICE_ERROR,
					ApplicationFacade.FILESIZE_ERROR
					];
		}
		
		private var imagesProcessed:Boolean;
		private var stateName:String;
		override public function handleNotification(note:INotification):void
		{
			var configProxy:ConfigProxy
			switch ( note.getName() )
			{
				case ApplicationFacade.FILESIZE_ERROR:
					configProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
					filesView.globalMessages.text = String(configProxy.lang.toobig).replace('LIMITSIZE', String(Math.round(Number(note.getBody())/1024))); 
					filesView.globalMessagesBox.visible = true;
					break;
				case ApplicationFacade.SERVICE_ERROR:
					configProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
					filesView.globalMessages.text = configProxy.lang.uploaderror; 
					filesView.globalMessagesBox.visible = true;
					break;
				case ApplicationFacade.IMAGES_PROCESSED:
					imagesProcessed = true;
					break;
				case ApplicationFacade.FILE_CHECK_FAIL:
					filesView.failFile();
				break;
				case ApplicationFacade.FILE_CHECK_OK:
					filesView.addFile();
				break;
				case ApplicationFacade.GLOBAL_PROGRESS_INIT:
					var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
					filesView.globalMessagesBox.visible = false;
					stateName = String( note.getBody() );
					switch(stateName) {
						case StateConstants.STATE_PROCESSING:
							filesView.initProgress('processing',proxy.fileList.length);
						break;
						case StateConstants.STATE_UPLOADING:
							filesView.initProgress('uploading',proxy.fileList.length);
						break;
					}
				break;	
				case ApplicationFacade.PROCESS_PROGRESS:
					var obj:Object = (note.getBody() as Object);
					filesView.toProgress(Number(obj.processed));
				break;
				case StateMachine.CHANGED:
					stateName = State( note.getBody() ).name;
            		switch( stateName ) {
            			case StateConstants.STATE_SETUPING:
            				filesView.closeProgress();
							if(filesView.autoUpload===true) {
								if(this.imagesProcessed===true || filesView.autoProcess===false) {
									this.imagesProcessed=false;
									this.onUpload(null);
								}
							}
            			break;
            		}
                    break;
                
			}
		}
		
		protected function get filesView():FilesView
		{
			return viewComponent as FilesView;
		}		
	}
}