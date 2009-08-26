package net.fundekave.fuup.view
{
        
    import flash.events.Event;
    
    import mx.core.Application;
    
    import net.fundekave.fuup.ApplicationFacade;
    import net.fundekave.fuup.common.constants.ActionConstants;
    import net.fundekave.fuup.common.constants.StateConstants;
    import net.fundekave.fuup.view.components.LoaderView;
    import net.fundekave.fuup.view.components.MainDisplay;
    
    import org.puremvc.as3.multicore.interfaces.INotification;
    import org.puremvc.as3.multicore.patterns.mediator.Mediator;
    import org.puremvc.as3.multicore.utilities.startupmanager.model.StartupMonitorProxy;
    import org.puremvc.as3.multicore.utilities.statemachine.State;
    import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;
    
    public class MainDisplayMediator extends Mediator
    {
        public static const NAME:String = 'MainDisplayMediator';
        
        private var stateName:String;
        
        public function MainDisplayMediator( viewComponent:Object )
        {
            super( NAME, viewComponent );   
        }

        override public function onRegister():void
        {
        	
            // Listen for the view's creationComplete event
            mainDisplay.addEventListener( MainDisplay.STATE_COMPLETE,	registerState );
            mainDisplay.addEventListener( LoaderView.SERVICE_RETRY, onServiceRetry );
            // Bind data from the HandleLoginProxy to variable/component in view
            //BindingUtils.bindProperty( app.log_txt, 'text', lgnPrx, ["username"]);
            //BindingUtils.bindProperty( app, "armed", lgnPrx, ["login_success"]);
        	        	
        }
                       
		override public function listNotificationInterests():Array
		{
			return [
				StateMachine.CHANGED,
				StateConstants.STATE_LOGIN,
				StateConstants.STATE_SETUP,
				ApplicationFacade.LOGIN_SUCCESS,
				StateConstants.STATE_INIT,
				StartupMonitorProxy.LOADING_FINISHED_INCOMPLETE,
				ApplicationFacade.SERVICE_ERROR
			];
		}
       
       	override public function handleNotification(note:INotification):void
       	{
       		switch (note.getName())
       		{
       			case ApplicationFacade.SERVICE_ERROR:
       			case StartupMonitorProxy.LOADING_FINISHED_INCOMPLETE:
       				sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_INIT );
       				mainDisplay.loaderView.setState(LoaderView.STATE_NAME_SERVICEERROR);
       				break;
				case StateConstants.STATE_INIT:
					mainDisplay.setState( MainDisplay.STATE_NAME_LOADER );
					break;
       			case StateConstants.STATE_LOGIN:
       				mainDisplay.setState( MainDisplay.STATE_NAME_LOGIN );
       				break;
       			case StateConstants.STATE_SETUP:
       				mainDisplay.setState( MainDisplay.STATE_NAME_FILES );
       				break;
       			case StateMachine.CHANGED:
            		stateName = State( note.getBody() ).name;
            		trace('STATE::'+stateName);
                    break;
       		}
       	}
       	
       	/**
         * registerState must only be done when the
         * view has fired its creationComplete Event.
         * 
         * Send a notification with the currently loaded
         * view and state name as params.  REGISTER_STATE
         * has a registered command RegisterStateCommand 
         * which will check to see if the view's mediator
         * is registered with the Facade and if not will
         * register it.
         */
        protected function registerState( e:Event ):void
        {
        	sendNotification( ApplicationFacade.REGISTER_STATE, mainDisplay.mainStack.selectedChild, stateName );
        }
        
        protected function onServiceRetry( e:Event ):void {
        	sendNotification( ApplicationFacade.LOAD_RESOURCES );
        } 
       	

        protected function get mainDisplay():MainDisplay
        {
            return viewComponent as MainDisplay;
        }
    }
}