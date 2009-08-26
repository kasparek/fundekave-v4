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
			var fsm:XML = <fsm initial={StateConstants.STATE_INIT}>
				<state name={StateConstants.STATE_INIT} changed={StateConstants.STATE_INIT}>
					<transition action={ActionConstants.ACTION_LOGIN} target={StateConstants.STATE_LOGIN}/>
					<transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUP}/>
				</state>
			  	<state name={StateConstants.STATE_LOGIN} changed={StateConstants.STATE_LOGIN}>
					<transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUP}/>
					<transition action={ActionConstants.ACTION_INIT} target={StateConstants.STATE_INIT}/>
				</state>
				<state name={StateConstants.STATE_SETUP} changed={StateConstants.STATE_SETUP}>
					<transition action={ActionConstants.ACTION_PROCESS} target={StateConstants.STATE_PROCESS}/>
				</state>
				<state name={StateConstants.STATE_PROCESS} changed={StateConstants.STATE_PROCESS}>
					<transition action={ActionConstants.ACTION_UPLOAD} target={StateConstants.STATE_UPLOAD}/>
				</state>
				<state name={StateConstants.STATE_UPLOAD} changed={StateConstants.STATE_UPLOAD}>
					<transition action={ActionConstants.ACTION_CHECK} target={StateConstants.STATE_CHECK}/>
				</state>
				<state name={StateConstants.STATE_CHECK} changed={StateConstants.STATE_CHECK}>
				  <transition action={ActionConstants.ACTION_SETUP} target={StateConstants.STATE_SETUP}/>
				  <transition action={ActionConstants.ACTION_LOGIN} target={StateConstants.STATE_LOGIN}/>
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
