package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.vo.FileVO;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	
	public class FileDelete extends SimpleCommand
	{
		override public function execute(notification:INotification):void {
			var fileVO:FileVO = notification.getBody() as FileVO; 
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			//---remove renderer
			fileVO.renderer.removeView();
			//---remove data
			var ind:int = proxy.fileList.indexOf( fileVO );
			proxy.fileList.splice(ind, 1);
			trace("FILEREMOVED"+String(proxy.fileList.length));
		}
	}
}