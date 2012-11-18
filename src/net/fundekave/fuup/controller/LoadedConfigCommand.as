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
			
			fileProxy.widthMax = Number( configProxy.config.settings.image.width );
			fileProxy.heightMax = Number( configProxy.config.settings.image.height );
			fileProxy.outputQuality = Number( configProxy.config.settings.image.quality );
			
			fileProxy.showImages = Number( configProxy.config.settings.showImages ) == 1 ? true : false;
			
			fileProxy.serviceURL = String( configProxy.config.service.url );
			fileProxy.serviceVars = configProxy.config.service.vars;
	        
			fileProxy.chunkSize = Number( configProxy.config.settings.chunkSize );
	        fileProxy.uploadLimit = Number( configProxy.config.settings.chunkLimit );
			
			extProxy.callback = String( configProxy.config.settings.callback );
			
			Application.application.width = Number( configProxy.config.settings.appSize.width );
			Application.application.height = Number( configProxy.config.settings.appSize.height );
		}
	}
}