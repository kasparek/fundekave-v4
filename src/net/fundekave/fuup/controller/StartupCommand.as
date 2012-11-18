package net.fundekave.fuup.controller
{

	import net.fundekave.fuup.view.*;
	import net.fundekave.fuup.model.*;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	
	import net.fundekave.fuup.ApplicationFacade;

	public class StartupCommand extends SimpleCommand implements ICommand
	{
		override public function execute ( note:INotification ) : void
		{
			// Register the ApplicationMediator
			var app:Fuup = note.getBody() as Fuup;
			facade.registerMediator( new ApplicationMediator( app ) );
			//setup data structure
			facade.registerProxy( new ConfigProxy() );
			facade.registerProxy( new FileProxy() );
			facade.registerProxy( new ExtInterfaceProxy() );
			//get config data
			sendNotification( ApplicationFacade.STATE,null,ApplicationFacade.STATE_INITING );
			sendNotification( ApplicationFacade.CONFIG_LOAD );
		}
	}
}