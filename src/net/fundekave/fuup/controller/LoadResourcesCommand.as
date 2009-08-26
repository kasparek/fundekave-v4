package net.fundekave.fuup.controller
{
		
	import net.fundekave.fuup.model.ConfigDataProxy;
	
	import org.puremvc.as3.multicore.interfaces.ICommand;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.command.SimpleCommand;
	import org.puremvc.as3.multicore.utilities.startupmanager.interfaces.IStartupProxy;
	import org.puremvc.as3.multicore.utilities.startupmanager.model.StartupMonitorProxy;
	import org.puremvc.as3.multicore.utilities.startupmanager.model.StartupResourceProxy;

	public class LoadResourcesCommand extends SimpleCommand implements ICommand
	{
		
		private var _monitor:StartupMonitorProxy;
		
		override public function execute(notification:INotification):void
		{
			
			facade.registerProxy( new StartupMonitorProxy() );
			_monitor = facade.retrieveProxy( StartupMonitorProxy.NAME ) as StartupMonitorProxy;
			
			var configDataProxy:IStartupProxy = new ConfigDataProxy()
			facade.registerProxy( configDataProxy );
			
			var rConfigDataProxy:StartupResourceProxy = makeAndRegisterStartupResource( ConfigDataProxy.SRNAME, configDataProxy );
			
			_monitor.loadResources();

		}
		
		private function makeAndRegisterStartupResource( proxyName:String, appResourceProxy:IStartupProxy ):StartupResourceProxy
		{
			var r:StartupResourceProxy = new StartupResourceProxy( proxyName, appResourceProxy );
			facade.registerProxy( r );
			_monitor.addResource( r );
			return r;
		}

		
		
	}
}