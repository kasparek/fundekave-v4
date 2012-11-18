package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.view.ApplicationMediator;
	import net.fundekave.fuup.model.*;
	import net.fundekave.fuup.model.vo.FileVO;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	
	public class FileDelete extends SimpleCommand
	{
		override public function execute(notification:INotification):void
		{
			var fileVO:FileVO = notification.getBody() as FileVO;
			var proxy:FileProxy = facade.retrieveProxy(FileProxy.NAME) as FileProxy;
			if (fileVO)
			{
				//---remove renderer
				fileVO.renderer.removeView();
				sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.IMAGE_REMOVED, fileVO.filename);
				//---remove data
				var ind:int = proxy.fileList.indexOf(fileVO);
				proxy.fileList.splice(ind, 1);
			}
			else
			{
				var appMed:ApplicationMediator = facade.retrieveMediator(ApplicationMediator.NAME) as ApplicationMediator;
				if (appMed.state != ApplicationFacade.STATE_SELECTING) return;
				while (proxy.fileList.length>0)
					sendNotification(ApplicationFacade.FILE_DELETE, proxy.fileList[proxy.fileList.length-1]);
			}
			sendNotification(ApplicationFacade.CALLBACK, ExtInterfaceProxy.IMAGE_NUM, String(proxy.fileList.length));
		}
	}
}