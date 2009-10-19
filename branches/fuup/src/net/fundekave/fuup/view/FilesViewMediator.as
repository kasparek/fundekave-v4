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
			filesView.addEventListener( FileView.SETTINGS_INHERIT, onFileIhnerit, false, 0, true );
			filesView.addEventListener( FilesView.SETTINGS_CHANGE, onSettingsChange, false, 0, true );
			filesView.addEventListener( FilesView.ACTION_PROCESS, onProcess, false, 0, true );
			filesView.addEventListener( FilesView.ACTION_UPLOAD, onUpload, false, 0, true );
		}
		
		protected function onFileCreated( e:Event ):void {
			//---register file with proxy
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			var fileVO:FileVO = (e.target as FileView).fileVO;
			proxy.fileList.push( fileVO );
			proxy.updateFile( fileVO );
		}
		
		protected function onFileRemove( e:Event ):void {
			var fileVO:FileVO = (e.target as FileView).fileVO;
			sendNotification( ApplicationFacade.FILE_DELETE, fileVO );
		}
		
		protected function onFileCheck( e:Event ):void {
			sendNotification( ApplicationFacade.FILE_CHECK_EXISTS, filesView.currFile.name );
		}
		
		protected function onFileIhnerit( e:Event ):void {
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			proxy.updateFiles();
		}
		
		protected function onSettingsChange(e:Event):void {
			/*
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			proxy.widthMax = Number( filesView.newWidthInput.text );
			proxy.heightMax = Number( filesView.newHeightInput.text );
			proxy.updateFiles();
			*/
		}
		
		protected function onProcess(e:Event):void {
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
					ApplicationFacade.FILE_CHECK_OK
					];
		}
		
		override public function handleNotification(note:INotification):void
		{
			var stateName:String;
			switch ( note.getName() )
			{
				case ApplicationFacade.CONFIG_LOADED:
					var configProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
					filesView.lang = configProxy.lang;
					break;
				case ApplicationFacade.FILE_CHECK_FAIL:
					filesView.failFile();
				break;
				case ApplicationFacade.FILE_CHECK_OK:
					filesView.addFile();
				break;
				case ApplicationFacade.GLOBAL_PROGRESS_INIT:
					var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
					
					filesView.globalProgressBar.visible = true;
					filesView.globalProgressBar.value = 0;
					stateName = String( note.getBody() );
					switch(stateName) {
						case StateConstants.STATE_PROCESSING:
							filesView.globalProgressBar.label = filesView.lang.processing;
							filesView.globalProgressBar.maximum = proxy.fileList.length;
						break;
						case StateConstants.STATE_UPLOADING:
							filesView.globalProgressBar.label = filesView.lang.uploading;
							filesView.globalProgressBar.maximum = proxy.fileList.length;
						break;
					}
				break;	
				case ApplicationFacade.PROCESS_PROGRESS:
					var obj:Object = (note.getBody() as Object);
					filesView.globalProgressBar.value = Number( obj.processed );
					trace('IMAGE PROCESSED::'+obj.processed+'/'+obj.total);
				break;
				case StateMachine.CHANGED:
					stateName = State( note.getBody() ).name;
            		switch( stateName ) {
            			case StateConstants.STATE_SETUPING:
            				filesView.globalProgressBar.visible = false;
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