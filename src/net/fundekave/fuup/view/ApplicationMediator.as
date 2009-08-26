package net.fundekave.fuup.view
{
	import mx.core.Application;
	
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.ActionConstants;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
	import org.puremvc.as3.multicore.utilities.startupmanager.model.StartupMonitorProxy;
	import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;

	public class ApplicationMediator extends Mediator
	{
		public static const NAME:String	= "ApplicationMediator";
	 
		public function ApplicationMediator( viewComponent:FuUp ) 
		{
			super( NAME, viewComponent );
		}
		
		override public function onRegister():void {
			
			//---register main display mediator
			facade.registerMediator(new MainDisplayMediator(app.mainDisplay));
			
		}
		
		override public function listNotificationInterests():Array
		{
			return [ 
					StartupMonitorProxy.RETRYING_LOAD_RESOURCE,
					StartupMonitorProxy.LOADING_PROGRESS,
					StartupMonitorProxy.LOAD_RESOURCE_TIMED_OUT,
					StartupMonitorProxy.LOADING_COMPLETE,
					StartupMonitorProxy.LOADING_FINISHED_INCOMPLETE,
					StartupMonitorProxy.CALL_OUT_OF_SYNC_IGNORED,
					
					ApplicationFacade.INJECTED,
					
					ApplicationFacade.LOGIN_SUCCESS,
					
					ApplicationFacade.PROCESS_PROGRESS
					];
		}
		
		override public function handleNotification(note:INotification):void
		{
			
			switch ( note.getName() )
			{
				case ApplicationFacade.PROCESS_PROGRESS:
					var progressObj:Object = note.getBody() as Object;
					trace('Processing ' + progressObj.processed + '/' + progressObj.total);
					break;
				case ApplicationFacade.LOGIN_SUCCESS:
					trace( "LOGIN SUCCESSFUL" );
					sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
					break;
				case StartupMonitorProxy.RETRYING_LOAD_RESOURCE:
					trace( "Retrying to load resource:" );
                    break;
                case StartupMonitorProxy.CALL_OUT_OF_SYNC_IGNORED:
                    trace( "Abnormal State, Abort" );
                    break;
                case StartupMonitorProxy.LOADING_PROGRESS:
					var perc:Number = note.getBody() as Number;
					
                    trace( "Loading Progress: " + perc + "%" );
                    break;
                case StartupMonitorProxy.LOADING_COMPLETE:
                    trace( ">>> Loading Complete" );
					sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_LOGIN );
                    break;
                case StartupMonitorProxy.LOADING_FINISHED_INCOMPLETE:
                    trace( "Loading Finished Incomplete" );
                    break;
				
				case ApplicationFacade.INJECTED:
					sendNotification( StateMachine.ACTION, null, ActionConstants.ACTION_SETUP );
					break;	
			}
		}
		
		public function get app():FuUp
		{ 
			return viewComponent as FuUp; 
		}
	}
}