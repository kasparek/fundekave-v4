package net.fundekave.fuup.model
{
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	
	import net.fundekave.Application;
	import net.fundekave.fuup.ApplicationFacade;
	
	import com.adobe.serialization.json.JSON;
	
	import org.puremvc.as3.multicore.interfaces.IProxy;
	import org.puremvc.as3.multicore.patterns.proxy.Proxy;
	
	public class ConfigProxy extends Proxy implements IProxy
	{
		public static const NAME:String = 'ConfigDataProxy';
		
		private var configDefault:String = '{"settings":{"autoUpload":"0","showControls":"1","showImages":"1","multi":"1","chunkSize":"32768","chunkLimit":"8","fileLimit":"40","image":{"width":"2048","height":"2048","quality":"90","type":"jpg,jpeg,gif,png"},"appSize":{"width":"-1","height":"200"},"callback":"fuupGateOut"},"service":{"url":"files.php","vars":{}}}';
		
		public var config:Object;
		
		public function ConfigProxy()
		{
			super(NAME);
		}
		
		public function load():void
		{
			var params:Object = Application.application.loaderInfo.parameters;
			
			if (params.hasOwnProperty('config'))
			{
				if (String(params.config).length > 0)
				{
					config = JSON.decode(params.config);
					if (config)
					{
						sendNotification(ApplicationFacade.CONFIG_LOADED);
						return;
					}
				}
			}
			config = JSON.decode(configDefault);
			sendNotification(ApplicationFacade.CONFIG_LOADED);
		}
	}
}