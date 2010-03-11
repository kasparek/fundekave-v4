package net.fundekave.fuup.view
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.ActionConstants;
	import net.fundekave.fuup.common.constants.StateConstants;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
	import org.puremvc.as3.multicore.utilities.statemachine.State;
	import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;

	public class ApplicationMediator extends Mediator
	{
		public static const NAME:String	= "ApplicationMediator";
	 
		public function ApplicationMediator( viewComponent:Fuup ) 
		{
			super( NAME, viewComponent );
		}
		
		override public function onRegister():void {
			
		}
		
		override public function listNotificationInterests():Array
		{
			return [ApplicationFacade.INJECTED,
					ApplicationFacade.LOGIN_SUCCESS,
					ApplicationFacade.CONFIG_LOADED,
					StateMachine.CHANGED,
					ApplicationFacade.SERVICE_ERROR
					];
		}
		
		override public function handleNotification(note:INotification):void
		{
			
			switch ( note.getName() )
			{
				case ApplicationFacade.SERVICE_ERROR:
					sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
					break;
                case ApplicationFacade.CONFIG_LOADED:
                    trace( "CONFIG >>> Loading Complete" );
					sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
                    break;
				
				case ApplicationFacade.INJECTED:
					sendNotification( ApplicationFacade.CONFIG_LOAD );
					break;
					
				case StateMachine.CHANGED:
					var stateName:String = State( note.getBody() ).name;
            		trace(stateName);
            		switch(stateName) {
						case StateConstants.STATE_SETUPING:
							app.setup();
							facade.registerMediator( new FilesViewMediator( app.filesView ) );
							break;
            			case StateConstants.STATE_PROCESSING:
            				sendNotification( ApplicationFacade.IMAGES_PROCESS );
            			break;
            			case StateConstants.STATE_UPLOADING:
            				sendNotification( ApplicationFacade.IMAGES_UPLOAD );
            			break;
            		}
                    break;
			}
		}
		
		public function get app():Fuup
		{ 
			return viewComponent as Fuup; 
		}
	}
}