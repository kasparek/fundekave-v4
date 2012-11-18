package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.vo.FileVO;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	
	public class FileCheckExists extends SimpleCommand implements ICommand
	{
		
		override public function execute(notification:INotification):void
		{
			var filename:String = notification.getBody() as String;
			var proxy:FileProxy = facade.retrieveProxy(FileProxy.NAME) as FileProxy;
			
			for each (var fileVO:FileVO in proxy.fileList)
			{
				if (fileVO.filenameOriginal == filename)
				{
					//---send notification file already exists
					sendNotification(ApplicationFacade.FILE_CHECK_FAIL);
					return;
				}
			}
			
			//---send notification file is OK to add
			sendNotification(ApplicationFacade.FILE_CHECK_OK);
		}
	
	}
}