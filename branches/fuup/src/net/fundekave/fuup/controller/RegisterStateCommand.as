package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.common.constants.StateConstants;
	import net.fundekave.fuup.view.FilesViewMediator;
	import net.fundekave.fuup.view.LoginViewMediator;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class RegisterStateCommand extends SimpleCommand implements ICommand
	{
		/**
		 * RegisterStateCommand called everytime StateMachine sends
		 * a StateMachine.CHANGED Notification.  The current state name
		 * and current view is passed as params enabling referencing
		 * of the applicable Mediator. Simple check made to see if 
		 * Mediator is registered with the Facade and if false
		 * the newly loaded view and its Mediator are registered
		 * with the framework.
		 */
		override public function execute( note:INotification ):void
		{			
			checkForMediator( note.getType(), note.getBody() as Object );
		}
		
		protected function checkForMediator( state:String, view:Object ):void
        {
            switch ( state )
            {
				 case StateConstants.STATE_LOGIN:
				      if ( ! facade.hasMediator( LoginViewMediator.NAME ) )
				         facade.registerMediator( new LoginViewMediator( view ) );
				      break;

				 case StateConstants.STATE_SETUP:
				 case StateConstants.STATE_PROCESS:
				 case StateConstants.STATE_UPLOAD:
				 case StateConstants.STATE_CHECK:
				      if ( ! facade.hasMediator( FilesViewMediator.NAME ) )
				         facade.registerMediator( new FilesViewMediator( view ) );
				      break;

              }        
        }
	}
}