package net.fundekave.fuup.view
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.ActionConstants;
	
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
			
			//---register main display mediator
			facade.registerMediator(new MainDisplayMediator(app.mainDisplay));
			
		}
		
		override public function listNotificationInterests():Array
		{
			return [ApplicationFacade.INJECTED,
					ApplicationFacade.LOGIN_SUCCESS,
					ApplicationFacade.PROCESS_PROGRESS,
					ApplicationFacade.CONFIG_LOADED,
					StateMachine.CHANGED
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
                    break;
                case ApplicationFacade.PROCESS_PROGRESS:
                	var obj:Object = note.getBody() as Object;
                	trace('IMAGE PROCESSED::'+obj.processed+'/'+obj.total);
                	break;
			}
		}
		
		public function get app():Fuup
		{ 
			return viewComponent as Fuup; 
		}
	}
}