package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.ConfigProxy;
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
			
			fileProxy.widthMax = Number( configProxy.getValue( 'imageWidthMax' ) );
			fileProxy.heightMax = Number( configProxy.getValue( 'imageHeightMax' ) );
			fileProxy.outputQuality = Number( configProxy.getValue( 'imageQuality' ) );
			fileProxy.filtersList = configProxy.filters;
			
			fileProxy.serviceURL = String( configProxy.getService('files') );
			fileProxy.maxSize = Number( configProxy.getValue('maxSize') );
	        fileProxy.chunkSize = Number( configProxy.getValue('chunkSize') );
	        fileProxy.uploadLimit = Number( configProxy.getValue('chunkLimit') );
		}
		
		
	}
}