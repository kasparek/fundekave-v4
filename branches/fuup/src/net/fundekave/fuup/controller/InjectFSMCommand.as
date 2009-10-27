package net.fundekave.fuup.controller
{
	
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.ActionConstants;
	import net.fundekave.fuup.common.constants.StateConstants;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	import org.puremvc.as3.multicore.utilities.statemachine.FSMInjector;
	
	/**
	 * Create and inject the StateMachine.
	 */
	public class InjectFSMCommand extends SimpleCommand
	{
		/**
		 * Inject the Finite State Machine.
		 * 
		 */
		override public function execute ( note:INotification ) : void
		{
			//FSM definition
			//entering,exiting - notification sent on state transition
			var fsm:XML = <fsm initial={StateConstants.STATE_INITING}>
				<state name={StateConstants.STATE_INITING}>
					<transition action={ActionConstants.ACTION_LOGIN} target={StateConstants.STATE_LOGINING}/>
					<transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUPING}/>
				</state>
			  	<state name={StateConstants.STATE_LOGINING}>
					<transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUPING}/>
					<transition action={ActionConstants.ACTION_INIT} target={StateConstants.STATE_INITING}/>
				</state>
				<state name={StateConstants.STATE_SETUPING}>
					<transition action={ActionConstants.ACTION_PROCESS} target={StateConstants.STATE_PROCESSING}/>
					<transition action={ActionConstants.ACTION_UPLOAD} target={StateConstants.STATE_UPLOADING}/>
				</state>
				<state name={StateConstants.STATE_PROCESSING} entering={ApplicationFacade.IMAGES_CHECK_FOR_PROCESSING} exiting={ApplicationFacade.IMAGES_PROCESSED}>
					<transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUPING}/>
				</state>
				<state name={StateConstants.STATE_UPLOADING} entering={ApplicationFacade.IMAGES_CHECK_FOR_UPLOADING}>
					<transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUPING}/>
				</state>
			</fsm> ;
			
			// Create and inject the StateMachine 
			var injector:FSMInjector = new FSMInjector( fsm );
			injector.initializeNotifier(this.multitonKey);
			injector.inject();
			
			// State machine prep complete, load and register core view
            sendNotification( ApplicationFacade.INJECTED );
		}
	}
}
