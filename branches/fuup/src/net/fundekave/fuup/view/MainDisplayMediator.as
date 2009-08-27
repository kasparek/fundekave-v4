package net.fundekave.fuup.view
{
        
    import flash.events.Event;
    
    import net.fundekave.fuup.ApplicationFacade;
    import net.fundekave.fuup.common.constants.ActionConstants;
    import net.fundekave.fuup.common.constants.StateConstants;
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
        	
            facade.registerMediator( new FilesViewMediator( mainDisplay.filesView ) );
        	        	
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
       				//mainDisplay.loaderView.setState(LoaderView.STATE_NAME_SERVICEERROR);
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
                      
        protected function onServiceRetry( e:Event ):void {
        	sendNotification( ApplicationFacade.LOAD_RESOURCES );
        } 
       	

        protected function get mainDisplay():MainDisplay
        {
            return viewComponent as MainDisplay;
        }
    }
}