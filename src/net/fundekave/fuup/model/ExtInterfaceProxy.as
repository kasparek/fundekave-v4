package net.fundekave.fuup.model
{
	import flash.external.ExternalInterface;
	
	import org.puremvc.as3.multicore.interfaces.IProxy;
	import org.puremvc.as3.multicore.patterns.proxy.Proxy;

	public class ExtInterfaceProxy extends Proxy implements IProxy
	{
		public static const NAME:String = 'ExtInterfaceProxy';
		
		public static const LOADED:String = 'loaded';
		public static const UPLOAD_ONE_COMPLETE:String = 'uploadOneComplete';
		public static const UPLOAD_COMPLETE:String = 'uploadComplete';
		
		public var callbackLoaded:String;
		public var callbackUploadOneComplete:String;
		public var callbackUploadComplete:String;
		
		public function ExtInterfaceProxy()
		{
			super( NAME );
		}
		
		public function call(key:String):void {
			var functionName:String;
			
			switch(key) {
				case LOADED:
					functionName = callbackLoaded;
				break;
				case UPLOAD_ONE_COMPLETE:
					functionName = callbackUploadOneComplete;
				break;
				case UPLOAD_COMPLETE:
					functionName = callbackUploadComplete;
				break;
			}
			
			if(functionName) {
				if(functionName.length>0) {
					if(ExternalInterface.available) {
						ExternalInterface.call( functionName );
					}
				}
			}
			
		}
	}
}