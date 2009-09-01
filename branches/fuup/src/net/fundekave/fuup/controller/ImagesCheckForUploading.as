package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.vo.FileVO;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;

	public class ImagesCheckForUploading extends SimpleCommand implements ICommand
	{
		
		override public function execute(notification:INotification):void
		{
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			for each(var fileVO:FileVO in proxy.fileList) {
				if( !fileVO.encodedJPG ) {

					sendNotification( StateMachine.CANCEL );
					
				}
			}
		}
		
	}
}