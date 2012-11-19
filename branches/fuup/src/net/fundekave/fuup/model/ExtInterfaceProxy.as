package net.fundekave.fuup.model
{
	import flash.external.ExternalInterface;
	
	import net.fundekave.fuup.ApplicationFacade;
	import org.puremvc.as3.multicore.interfaces.IProxy;
	import org.puremvc.as3.multicore.patterns.proxy.Proxy;
	
	public class ExtInterfaceProxy extends Proxy implements IProxy
	{
		public static const NAME:String = 'ExtInterfaceProxy';
		
		public static const IMAGE_LOADED:String = 'imageLoaded';
		public static const IMAGE_REMOVED:String = 'imageRemoved';
		public static const IMAGE_UPLOADED:String = 'imageUploaded';
		public static const IMAGE_NUM:String = 'imageNum';
		
		public static const PROGRESS:String = 'progress';
		
		public static const COMPLETE:String = 'complete';
		
		public static const ERROR:String = 'error';
		public static const ERROR_UPLOAD:String = 'errorUpload';
		public static const ERROR_FILE_NUM_LIMIT:String = 'errorFileNumLimit';
		
		public static const STATUS:String = 'status';
		public static const STATUS_BUSY:String = 'statusBusy';
		public static const STATUS_READY:String = 'statusReady';
		
		public static const TRACE:String = 'trace';
		
		public var callback:String;
		
		public function ExtInterfaceProxy()
		{
			super(NAME);
			if (ExternalInterface.available)
				ExternalInterface.addCallback("fuupGateIn", jsReceived);
		}
		
		public function call(key:String,value:String):void
		{
			if (ExternalInterface.available)
			{
				ExternalInterface.call(callback, key, value);
			}
		}
		
		private function jsReceived(value:String):void {
			trace("ExtInterfaceProxy::jsReceived - " + value);
			switch (value) {
				case 'upload':
					sendNotification(ApplicationFacade.ACTION_UPLOAD);
					break;
				case 'cancel':
					sendNotification(ApplicationFacade.ACTION_CANCEL);
					break;
				case 'removeAll':
					sendNotification(ApplicationFacade.ACTION_REMOVEALL);
					break;
			}
		}
	}
}