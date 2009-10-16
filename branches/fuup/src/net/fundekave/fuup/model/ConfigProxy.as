package net.fundekave.fuup.model
{
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	
	import net.fundekave.Application;
	import net.fundekave.fuup.ApplicationFacade;
	
	import org.puremvc.as3.multicore.interfaces.IProxy;
	import org.puremvc.as3.multicore.patterns.proxy.Proxy;

	public class ConfigProxy extends Proxy implements IProxy
	{
		public static const NAME:String = 'ConfigDataProxy';
		
		private var dataXML:XML;
		
		public var filters:XMLList
		public var lang:Object;
		
		public function ConfigProxy()
		{
			super( NAME );
		}
		
		//---
		public function getService(ident:String):XML {
			return XML( dataXML..Service.(@name==ident) );
		}
		
		public function getValue(ident:String):XML {
			return XML( dataXML..Item.(@name==ident) );
		}

		public function load() :void {
			//---try to get confug url
			var params:Object = Application.application.loaderInfo.parameters;
			var configUrl:String; 
			if(params.hasOwnProperty('config')) {
				configUrl = params.config;
			}
			
			sendNotification( ApplicationFacade.CONFIG_LOADING );
			var request:URLRequest = new URLRequest(((configUrl)?(configUrl):(ApplicationFacade.SERVICE_CONFIG_URL)) + '?r='+Math.random());
			var loader:URLLoader = new URLLoader();
			loader.addEventListener(IOErrorEvent.IO_ERROR, errorHandler);
			loader.addEventListener(Event.COMPLETE, loaderCompleteHandler);
			loader.load(request);
		}
		
		private function loaderCompleteHandler(event:Event):void {
			try {
				dataXML = new XML( event.target.data );
				
				filters = dataXML..Filter.(@enabled==1);
				
				lang = {};
				for each(var l:XML in dataXML..Lang.Item) {
					lang[l.@name] = String(l);
				}
				
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