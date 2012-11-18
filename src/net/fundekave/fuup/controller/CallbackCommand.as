package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.ExtInterfaceProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	
	public class CallbackCommand extends SimpleCommand implements ICommand
	{
		override public function execute ( note:INotification ) : void
		{
			var proxy:ExtInterfaceProxy = facade.retrieveProxy(ExtInterfaceProxy.NAME) as ExtInterfaceProxy;
			proxy.call( note.getBody() as String, note.getType() as String );
		}
	}
}