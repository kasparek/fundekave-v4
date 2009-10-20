package net.fundekave.fuup.controller
{
	
	import net.fundekave.fuup.model.ConfigProxy;
	import net.fundekave.fuup.model.ExtInterfaceProxy;
	import net.fundekave.fuup.model.FileProxy;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class PrepModelCommand extends SimpleCommand
	{
		override public function execute ( note:INotification ) : void
		{

			facade.registerProxy( new ConfigProxy() );
			//facade.registerProxy( new LoginProxy() );
			facade.registerProxy( new FileProxy() );
			facade.registerProxy( new ExtInterfaceProxy() );
			
		}
	}
}
