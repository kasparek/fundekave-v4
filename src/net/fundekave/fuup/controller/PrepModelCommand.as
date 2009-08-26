package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.LoginProxy;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class PrepModelCommand extends SimpleCommand
	{
		override public function execute ( note:INotification ) : void
		{

			facade.registerProxy( new LoginProxy() );
			facade.registerProxy( new FileProxy() );
			
		}
	}
}
