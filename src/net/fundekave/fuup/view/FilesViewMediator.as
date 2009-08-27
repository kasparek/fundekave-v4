package net.fundekave.fuup.view
{
	
	import flash.events.Event;
	
	import net.fundekave.fuup.common.constants.ActionConstants;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.vo.FileVO;
	import net.fundekave.fuup.view.components.FileView;
	import net.fundekave.fuup.view.components.FilesView;
	
	import org.puremvc.as3.multicore.interfaces.IMediator;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
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
			
			filesView.addEventListener( FileView.FILE_CREATED, onFileCreated );
			filesView.addEventListener( FileView.SETTINGS_INHERIT, onFileIhnerit );
			filesView.addEventListener( FilesView.SETTINGS_CHANGE, onSettingsChange );
			filesView.addEventListener( FilesView.ACTION_PROCESS, onProcess );
			filesView.addEventListener( FilesView.ACTION_UPLOAD, onUpload );
		}
		
		protected function onFileCreated( e:Event ):void {
			//---register file with proxy
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			var fileVO:FileVO = (e.target as FileView).fileVO;
			proxy.fileList.push( fileVO );
			fileVO.widthMax = proxy.widthMax;
			fileVO.heightMax = proxy.heightMax;
			proxy.updateFile( fileVO );
		}
		
		protected function onFileIhnerit( e:Event ):void {
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			proxy.updateFiles();
		}
		
		protected function onSettingsChange(e:Event):void {
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			proxy.widthMax = Number( filesView.newWidthInput.text );
			proxy.heightMax = Number( filesView.newHeightInput.text );
			proxy.updateFiles();
		}
		
		protected function onProcess(e:Event):void {
			sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_PROCESS );
		}
		
		protected function onUpload(e:Event):void {
			sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_UPLOAD );
		}
		
		protected function get filesView():FilesView
		{
			return viewComponent as FilesView;
		}		
	}
}