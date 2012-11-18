package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.ExtInterfaceProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class SelectFilesCommand extends SimpleCommand implements ICommand
	{
		
		
		override public function execute(notification:INotification):void
		{
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.STATUS, ExtInterfaceProxy.STATUS_READY);
			sendNotification(ApplicationFacade.STATE, null, ApplicationFacade.STATE_SELECTING);
		}
		
		
	}
}