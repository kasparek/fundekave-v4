package net.fundekave.fuup
{
	import net.fundekave.fuup.controller.*;
	
	import org.puremvc.as3.multicore.patterns.facade.Facade;
	
	public class ApplicationFacade extends Facade
	{
		public static const STARTUP:String = 'startup';
		public static const PROGRESS:String = 'progress';
		
		//---http service
		public static const SERVICE_ERROR:String = 'serviceError';
		//---js service
		public static const CALLBACK:String = 'callback';
		
		//---resources
		public static const CONFIG_LOAD:String = 'loadResources';
		public static const CONFIG_LOADED:String = 'configDataLoaded';
		
		//---file management
		public static const FILE_CHECK_EXISTS:String = 'fileCheckExists';
		public static const FILE_CHECK_FAIL:String = 'fileCheckFail';
		public static const FILE_CHECK_OK:String = 'fileCheckOk';
		public static const FILE_DELETE:String = 'fileDelete';
		
		//application states
		public static const STATE:String = 'state';
		
		public static const STATE_INITING:String = Fuup.NAME + '/states/initing';
		public static const STATE_SELECTING:String = Fuup.NAME + '/states/selecting';
		public static const STATE_LOADING:String = Fuup.NAME + '/states/loading';
		public static const STATE_UPLOADING:String = Fuup.NAME + '/states/uploading';
		
		public static const ACTION_SELECT:String = Fuup.NAME + "/actions/select";
		public static const ACTION_LOAD:String = Fuup.NAME + "/actions/load";
		public static const ACTION_UPLOAD:String = Fuup.NAME + "/actions/upload";
		public static const ACTION_CANCEL:String = Fuup.NAME + "/actions/cancel";
		public static const ACTION_REMOVEALL:String = Fuup.NAME + "/actions/removeAll";
		
		public var state:String;
		
		public function ApplicationFacade(key:String)
		{
			super(key);
		}
		
		public static function getInstance(key:String):ApplicationFacade
		{
			if (instanceMap[key] == null)
				instanceMap[key] = new ApplicationFacade(key);
			return instanceMap[key] as ApplicationFacade;
		}
		
		public function startup(app:Fuup):void
		{
			sendNotification(STARTUP, app);
		}
		
		/**
		 * register application commands
		 * */
		override protected function initializeController():void
		{
			super.initializeController();
			registerCommand(STARTUP, StartupCommand);
			registerCommand(CALLBACK, CallbackCommand);
			
			registerCommand(CONFIG_LOAD, LoadConfigCommand);
			registerCommand(CONFIG_LOADED, LoadedConfigCommand);
			
			registerCommand(FILE_CHECK_EXISTS, FileCheckExists);
			registerCommand(FILE_DELETE, FileDelete);
			
			registerCommand(ACTION_SELECT, SelectFilesCommand);
			registerCommand(ACTION_LOAD, LoadFilesCommand);
			registerCommand(ACTION_UPLOAD, UploadFilesCommand);
			registerCommand(ACTION_CANCEL, CancelCommand);
			registerCommand(ACTION_REMOVEALL, FileDelete);
		}
	}
}