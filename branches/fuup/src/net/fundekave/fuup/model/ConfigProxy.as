package net.fundekave.fuup.model
{
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	
	import net.fundekave.fuup.ApplicationFacade;
	
	import org.puremvc.as3.multicore.interfaces.IProxy;
	import org.puremvc.as3.multicore.patterns.proxy.Proxy;

	public class ConfigProxy extends Proxy implements IProxy
	{
		public static const NAME:String = 'ConfigDataProxy';
		
		private var dataXML:XML;
		
		public function ConfigProxy()
		{
			super( NAME );
		}
		
		//---
		public function getService(ident:String):XML {
			return XML( dataXML..Service.(@name==ident) );
		}

		public function load() :void {
			sendNotification( ApplicationFacade.CONFIG_LOADING );
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
				sendNotification( ApplicationFacade.CONFIG_FAILED );
				return;
			}
			if(!dataXML) {
				sendNotification( ApplicationFacade.CONFIG_FAILED );
				return;
			}
			
			dataXML.ignoreWhitespace = true;
			sendNotification( ApplicationFacade.CONFIG_LOADED );
		}

		private function errorHandler(e:IOErrorEvent):void {
			sendNotification( ApplicationFacade.CONFIG_FAILED );
		}
		
	}
}