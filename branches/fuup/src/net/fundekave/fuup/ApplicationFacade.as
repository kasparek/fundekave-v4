package net.fundekave.fuup
{
	import net.fundekave.fuup.common.constants.StateConstants;
	import net.fundekave.fuup.controller.*;
	
	import org.puremvc.as3.multicore.patterns.facade.Facade;
	import org.puremvc.as3.multicore.utilities.startupmanager.controller.StartupResourceFailedCommand;
	import org.puremvc.as3.multicore.utilities.startupmanager.controller.StartupResourceLoadedCommand;

	public class ApplicationFacade extends Facade
	{
		public static const SERVICE_CONFIG_URL:String = 'http://localhost/fudebu/config.xml';

		public static const STARTUP:String = 'startup';
				
		// State constants	
		public static const INJECTED:String 	    = 'injected';		
		public static const REGISTER_STATE:String 	= 'register_state';
		
		//---http service
		public static const SERVICE_ERROR:String = 'serviceError';
		
		//---resources
		public static const LOAD_RESOURCES:String			= 'loadResources';
		public static const CONFIG_DATA_LOADING:String 	    = 'congifDataLoading';
		public static const CONFIG_DATA_LOADED:String 	    = 'configDataLoaded';
		public static const CONFIG_DATA_FAILED:String 	    = 'configDataFailed';
		
		//---logion
		public static const LOGIN:String 	    = 'login';
		public static const LOGIN_SUCCESS:String 	    = 'loginSuccess';
		public static const LOGIN_FAILED:String 	    = 'loginFailed';
		
		//---processing
		public static const PROCESS_PROGRESS:String		= 'processProgress';
		 
	 	public function ApplicationFacade( key:String )
	 	{
	 		super(key);	
	 	}
	 	
        public static function getInstance( key:String ) : ApplicationFacade 
        {
            if ( instanceMap[ key ] == null ) instanceMap[ key ]  = new ApplicationFacade( key );
            return instanceMap[ key ] as ApplicationFacade;
        }
	
		public function startup ( app:FuUp ) : void
		{
			sendNotification( STARTUP, app );
		}
		
		/**
		 * register application commands
		 * */
		override protected function initializeController () : void
		{
			super.initializeController();
			registerCommand( STARTUP, StartupCommand );
			
			registerCommand( REGISTER_STATE,   	 RegisterStateCommand );
			
			registerCommand( LOAD_RESOURCES, LoadResourcesCommand );
			registerCommand( CONFIG_DATA_LOADED, StartupResourceLoadedCommand );
			registerCommand( CONFIG_DATA_FAILED, StartupResourceFailedCommand );
			
			registerCommand( LOGIN, LoginCommand);
			
			registerCommand( StateConstants.STATE_PROCESS, ProcessFilesCommand );
			registerCommand( StateConstants.STATE_UPLOAD, UploadFilesCommand );
		}
	}
}