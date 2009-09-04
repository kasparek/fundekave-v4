package net.fundekave.fuup
{
	import net.fundekave.fuup.controller.*;
	
	import org.puremvc.as3.multicore.patterns.facade.Facade;

	public class ApplicationFacade extends Facade
	{
		public static const SERVICE_CONFIG_URL:String = 'config.xml';

		public static const STARTUP:String = 'startup';
				
		// State constants	
		public static const INJECTED:String 	    = 'injected';		
		
		//---http service
		public static const SERVICE_ERROR:String = 'serviceError';
		
		//---resources
		public static const CONFIG_LOAD:String			= 'loadResources';
		public static const CONFIG_LOADING:String 	    = 'congifDataLoading';
		public static const CONFIG_LOADED:String 	    = 'configDataLoaded';
		public static const CONFIG_FAILED:String 	    = 'configDataFailed';
		
		//---logion
		public static const LOGIN:String 	    = 'login';
		public static const LOGIN_SUCCESS:String 	    = 'loginSuccess';
		public static const LOGIN_FAILED:String 	    = 'loginFailed';
		
		//---global progress
		public static const GLOBAL_PROGRESS_INIT:String = 'globalProgressInit';
		
		//---file management
		public static const FILE_CHECK_EXISTS:String		= 'fileCheckExists';
		public static const FILE_CHECK_FAIL:String		= 'fileCheckFail';
		public static const FILE_CHECK_OK:String		= 'fileCheckOk';
		public static const FILE_DELETE:String			= 'fileDelete';
		
		//---processing
		public static const PROCESS_PROGRESS:String		= 'processProgress';
		
		public static const IMAGES_CHECK_FOR_PROCESSING:String = 'imagesCheckForProcessing';
		public static const IMAGES_PROCESS:String		= 'imagesProcess';
		
		public static const IMAGES_CHECK_FOR_UPLOADING:String = 'imagesCheckForUploading';
		public static const IMAGES_UPLOAD:String		= 'imagesUpload'; 
		 
	 	public function ApplicationFacade( key:String )
	 	{
	 		super(key);	
	 	}
	 	
        public static function getInstance( key:String ) : ApplicationFacade 
        {
            if ( instanceMap[ key ] == null ) instanceMap[ key ]  = new ApplicationFacade( key );
            return instanceMap[ key ] as ApplicationFacade;
        }
	
		public function startup ( app:Fuup ) : void
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
			
			registerCommand( CONFIG_LOAD, LoadConfigCommand );
			
			registerCommand( LOGIN, LoginCommand);
			
			registerCommand( FILE_CHECK_EXISTS, FileCheckExists );
			registerCommand( FILE_DELETE, FileDelete );
			
			registerCommand( IMAGES_CHECK_FOR_PROCESSING, ImagesCheckForProcessingCommand );
			registerCommand( IMAGES_PROCESS, ImagesProcessFilesCommand );
			
			registerCommand( IMAGES_CHECK_FOR_UPLOADING, ImagesCheckForUploading );
			registerCommand( IMAGES_UPLOAD, UploadFilesCommand );
		}
	}
}