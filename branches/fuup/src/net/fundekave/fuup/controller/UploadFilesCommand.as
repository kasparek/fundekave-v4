package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.FileProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class UploadFilesCommand extends SimpleCommand implements ICommand
	{
		
		
		override public function execute(notification:INotification):void
		{
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			if(proxy.fileList.length > 0) {
				proxy.uploadFiles();
			}
		}
		
		
	}
}