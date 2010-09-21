package net.fundekave.fuup.controller
{
		
	import net.fundekave.Application;
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.ConfigProxy;
	import net.fundekave.fuup.model.ExtInterfaceProxy;
	import net.fundekave.fuup.model.FileProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	
	public class LoadedConfigCommand extends SimpleCommand implements ICommand
	{
		
		
		override public function execute(notification:INotification):void
		{
			var configProxy: ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
			var fileProxy: FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			var extProxy:ExtInterfaceProxy = facade.retrieveProxy(ExtInterfaceProxy.NAME) as ExtInterfaceProxy;
			
			fileProxy.widthMax = Number( configProxy.getValue( 'imageWidthMax' ) );
			fileProxy.heightMax = Number( configProxy.getValue( 'imageHeightMax' ) );
			fileProxy.outputQuality = Number( configProxy.getValue( 'imageQuality' ) );
			fileProxy.crop = Number( configProxy.getValue( 'crop' ) ) === 1 ? true : false;
			fileProxy.displayContent = Number( configProxy.getValue("displayContent") )==1 ? true : false;
			fileProxy.filtersList = configProxy.filters;
			
			fileProxy.serviceURL = String( configProxy.getService('files') );
			fileProxy.authToken = String( configProxy.getService('auth') );
			fileProxy.maxSize = Number( configProxy.getValue('maxSize') );
	        fileProxy.chunkSize = Number( configProxy.getValue('chunkSize') );
	        fileProxy.uploadLimit = Number( configProxy.getValue('chunkLimit') );
			
			extProxy.callbackLoaded = String( configProxy.getValue( 'onLoaded' ) );
			extProxy.callbackUploadOneComplete = String( configProxy.getValue( 'onUploadOneComplete' ) );
			extProxy.callbackUploadComplete = String( configProxy.getValue( 'onUploadComplete' ) );
			
			Application.application.width = Number( configProxy.getValue('embedWidth') );
			Application.application.height = Number( configProxy.getValue('embedHeight') );
			
			sendNotification( ApplicationFacade.CALLBACK, ExtInterfaceProxy.LOADED );
		}
		
		
	}
}