package net.fundekave.fuup.controller
{
	import net.fundekave.fuup.model.ConfigProxy;
	import net.fundekave.fuup.model.FileProxy;
	import net.fundekave.fuup.model.vo.FileVO;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;

	public class ImagesCheckForUploading extends SimpleCommand implements ICommand
	{
		
		override public function execute(notification:INotification):void
		{
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			var sendCancel:Boolean = false;
			for each(var fileVO:FileVO in proxy.fileList) {
				
				if( !fileVO.encodedJPG ) {
					
					//---check bytesize of original
					if(fileVO.file.size > proxy.maxSize) {
						var configProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
						fileVO.renderer.updateStatus( String(configProxy.lang.toobig).replace('{LIMITSIZE}', String(Math.round(proxy.maxSize/1024)) ),false,1);
						sendCancel = true;
					}
					
				}
				
				
			}
			if(sendCancel === true) {
				sendNotification( StateMachine.CANCEL );
			}
		}
		
	}
}