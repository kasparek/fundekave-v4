package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.ExtInterfaceProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class LoadFilesCommand extends SimpleCommand implements ICommand
	{
		override public function execute(notification:INotification):void
		{
			
			sendNotification(ApplicationFacade.STATE, null, ApplicationFacade.STATE_LOADING);
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.STATUS, ExtInterfaceProxy.STATUS_BUSY);
		}
	}
}