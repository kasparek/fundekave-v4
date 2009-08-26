package net.fundekave.fuup.model
{
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	
	import net.fundekave.fuup.ApplicationFacade;
	
	import org.puremvc.as3.multicore.utilities.startupmanager.interfaces.IStartupProxy;

	public class ConfigDataProxy extends EntityProxy implements IStartupProxy
	{
		public static const NAME:String = 'ConfigDataProxy';
		public static const SRNAME:String = 'ConfigDataSRProxy';
		
		private var dataXML:XML;
		
		public function ConfigDataProxy()
		{
			super( NAME );
		}
		
		//---
		public function getService(ident:String):XML {
			return XML( dataXML..Service.(@name==ident) );
		}

		public function load() :void {
			sendNotification( ApplicationFacade.CONFIG_DATA_LOADING );
			var request:URLRequest = new URLRequest(ApplicationFacade.SERVICE_CONFIG_URL);
			var loader:URLLoader = new URLLoader();
			loader.addEventListener(IOErrorEvent.IO_ERROR, errorHandler);
			loader.addEventListener(Event.COMPLETE, loaderCompleteHandler);
			loader.load(request);
		}
		
		private function loaderCompleteHandler(event:Event):void {
			try {
				dataXML = new XML( event.target.data );
			} catch (e:Error) {
				trace(e.toString());
				sendLoadedNotification( ApplicationFacade.CONFIG_DATA_FAILED, NAME, SRNAME );
				return;
			}
			if(!dataXML) {
				sendLoadedNotification( ApplicationFacade.CONFIG_DATA_FAILED, NAME, SRNAME );
				return;
			}
			
			dataXML.ignoreWhitespace = true;
			sendLoadedNotification( ApplicationFacade.CONFIG_DATA_LOADED, NAME, SRNAME );
		}

		private function errorHandler(e:IOErrorEvent):void {
			sendLoadedNotification( ApplicationFacade.CONFIG_DATA_FAILED, NAME, SRNAME );
		}


		
	}
}