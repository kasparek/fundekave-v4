package net.fundekave.fuup.controller
{
	//import net.fundekave.fuup.model.ConfigProxy;
	//import net.fundekave.fuup.model.LoginProxy;
	//import net.fundekave.fuup.model.vo.LoginVO;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;

	public class LoginCommand extends SimpleCommand
	{
		override public function execute ( note:INotification ) : void
		{
			/*
			var configProxy:ConfigProxy = facade.retrieveProxy( ConfigProxy.NAME ) as ConfigProxy;
			var proxy:LoginProxy = facade.retrieveProxy( LoginProxy.NAME ) as LoginProxy;
			var serviceXML:XML = configProxy.getService( 'login' );
			proxy.serviceUrl = String(serviceXML);
			proxy.login( note.getBody() as LoginVO );
			*/
		}
	}
}
