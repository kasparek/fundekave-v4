package net.fundekave.fuup.controller
{
	import flash.utils.setTimeout;
	
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.common.constants.StateConstants;
	import net.fundekave.fuup.model.FileProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class ImagesProcessFilesCommand extends SimpleCommand implements ICommand
	{
		
		override public function execute(notification:INotification):void
		{
			//---send notification to - processing start
			sendNotification( ApplicationFacade.GLOBAL_PROGRESS_INIT, StateConstants.STATE_PROCESSING );
			
			var proxy:FileProxy = facade.retrieveProxy( FileProxy.NAME ) as FileProxy;
			if(proxy.fileList.length > 0) {
				setTimeout(proxy.processFiles,200);
			}
		}
		
	}
}