/**
 *
 * Image resizer and uploader
 * Author: Frantisek Kaspar fundekave.net 
 * 
 */
package
{
	import flash.events.Event;
	
	import net.fundekave.Application;
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.view.components.FilesView;
	import net.fundekave.fuup.view.RondaFont;
	
	import flash.system.Security;
	
	public class Fuup extends Application
	{
		public static const NAME:String = "fudeup";
		public static const WIDTH:int = 400;
		public static const HEIGHT:int = 32;
		
		private var facade:ApplicationFacade = ApplicationFacade.getInstance( NAME );
		
		public var filesView:FilesView;
		
		public function Fuup()
		{
			this.addEventListener(Event.ADDED_TO_STAGE, onStage );
			Security.allowDomain("*");
		}

		private function onStage(e:Event):void {
			this.removeEventListener(Event.ADDED_TO_STAGE, onStage );
			stage.scaleMode = "noScale";
			facade.startup(this);
		}
	}
}