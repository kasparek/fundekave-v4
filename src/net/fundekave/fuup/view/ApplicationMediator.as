package net.fundekave.fuup.view
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.ActionConstants;
	import net.fundekave.fuup.common.constants.StateConstants;
	import net.fundekave.fuup.model.ConfigProxy;
	
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
					];
		}
		
		override public function handleNotification(note:INotification):void
		{
			
			switch ( note.getName() )
			{
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
						case StateConstants.STATE_INITING:
							
							break;
						case StateConstants.STATE_SETUPING:
							if(!app.filesView) {
								var cnfProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
								app.setup( cnfProxy.lang );
								facade.registerMediator( new FilesViewMediator( app.filesView ) );
							}
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