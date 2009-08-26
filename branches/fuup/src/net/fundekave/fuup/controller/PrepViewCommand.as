package net.fundekave.fuup.controller
{
	
	import net.fundekave.fuup.view.ApplicationMediator;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class PrepViewCommand extends SimpleCommand
	{
		override public function execute ( note:INotification ) : void
		{
			// Register the ApplicationMediator
			var app:FuUp = note.getBody() as FuUp;
			facade.registerMediator( new ApplicationMediator( app ) );
		}
	}
}