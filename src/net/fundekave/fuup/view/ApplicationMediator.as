package net.fundekave.fuup.view
{
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.ConfigProxy;
	import net.fundekave.fuup.view.components.FilesView;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
	
	public class ApplicationMediator extends Mediator
	{
		public static const NAME:String = "ApplicationMediator";
		
		public var state:String;
		
		public function ApplicationMediator(viewComponent:Fuup)
		{
			super(NAME, viewComponent);
		}
		
		override public function onRegister():void
		{
		
		}
		
		override public function listNotificationInterests():Array
		{
			return [ApplicationFacade.STATE, ApplicationFacade.CONFIG_LOADED];
		}
		
		override public function handleNotification(note:INotification):void
		{
			
			switch (note.getName())
			{
				case ApplicationFacade.CONFIG_LOADED: 
					app.filesView = new FilesView(app);
					app.filesView.width = Fuup.WIDTH;
					app.filesView.height = Fuup.HEIGHT;
					facade.registerMediator(new FilesViewMediator(app.filesView));
					sendNotification(ApplicationFacade.ACTION_SELECT);
					break;
				case ApplicationFacade.STATE: 
					state = note.getType();
					trace('ApplicationMediator::state = ' + state);
					break;
			}
		}
		
		public function get app():Fuup
		{
			return viewComponent as Fuup;
		}
	}
}