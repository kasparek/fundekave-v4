package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.ExtInterfaceProxy;
	import net.fundekave.fuup.view.ApplicationMediator;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class UploadFilesCommand extends SimpleCommand implements ICommand
	{
		
		
		override public function execute(notification:INotification):void
		{
			var appMed:ApplicationMediator = facade.retrieveMediator(ApplicationMediator.NAME) as ApplicationMediator;
			if (appMed.state != ApplicationFacade.STATE_SELECTING) return;
				
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			if (proxy.fileList.length > 0) {
				sendNotification(ApplicationFacade.STATE, null, ApplicationFacade.STATE_UPLOADING);
				sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.STATUS, ExtInterfaceProxy.STATUS_BUSY);
				proxy.uploadFiles();
			}
		}
		
		
	}
}