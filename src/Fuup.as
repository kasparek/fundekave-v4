package
{
	import flash.events.Event;
	
	import net.fundekave.Application;
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.view.components.FileView;
	import net.fundekave.fuup.view.components.FilesView;
	
	public class Fuup extends Application
	{
		
		public static const NAME:String = "fudeup";
		public static const WIDTH:int = 400;
		public static const HEIGHT:int = 35;
		
		private var facade:ApplicationFacade = ApplicationFacade.getInstance( NAME );
		
		public var filesView:FilesView;
		
		public function Fuup()
		{
			this.addEventListener(Event.ADDED_TO_STAGE, onStage );
		}
		
		private function onStage(e:Event):void {
			this.removeEventListener(Event.ADDED_TO_STAGE, onStage );
			facade.startup(this);
		}
		
		public function setup(lang:Object):void {
			filesView = new FilesView(this,0,0,lang);
			filesView.width = WIDTH;
			filesView.height = HEIGHT;
		}
	}
}