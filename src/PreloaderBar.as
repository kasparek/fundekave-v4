package
{
	import flash.display.Loader;
	import flash.display.Sprite;
	import flash.display.StageAlign;
	import flash.display.StageScaleMode;
	import flash.events.Event;
	import flash.events.ProgressEvent;
	import flash.net.URLRequest;
	import flash.system.LoaderContext;
	import flash.system.Security;
	import flash.system.ApplicationDomain;
	import flash.system.SecurityDomain;
	
	public class PreloaderBar extends Sprite
	{
		
		private var loader:Loader;
		private var barHolder:Sprite;
		private var barHolderWidth:Number;
		private var bar:Sprite;
		
		public function PreloaderBar()
		{
			stage.scaleMode = StageScaleMode.NO_SCALE;
			stage.align = StageAlign.TOP_LEFT;
			
			this.addEventListener( Event.ADDED_TO_STAGE, onStage );
			this.addEventListener( Event.RESIZE, onResize );
			
			Security.allowDomain("*");
		}
		
		private function onStage(e:Event):void {
			this.removeEventListener( Event.ADDED_TO_STAGE, onStage );
			
			bar = new Sprite();
			barHolder = new Sprite();
			barHolder.x = 0;
			barHolder.y = 0;
			barHolderWidth = (stage.stageWidth?stage.stageWidth:100) - 20;
			if (barHolderWidth > 200) barHolderWidth = 200;
			barHolder.graphics.lineStyle(1,0x666666);
			barHolder.graphics.beginFill(0xeeeeee);
			barHolder.graphics.drawRect(2,2,barHolderWidth,12);
			barHolder.graphics.endFill();
			bar.x = 4;
			bar.y = 4;
			barHolder.addChild( bar );
			this.addChild( barHolder );
			onResize( null );
			
			var url:String = this.loaderInfo.parameters.file + '?';
			var params:Object = this.loaderInfo.parameters;
			var arr:Array = [];
			for( var item:String in params ) {
				arr.push(item+'='+params[item]);
			}
			url += arr.join('&');
			
			loader = new Loader();
			loader.visible = false;
			this.addChild(loader);
			loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onLoaderComplete );
			loader.contentLoaderInfo.addEventListener( ProgressEvent.PROGRESS, onProgress );
			
			
			var context:LoaderContext; 
			context = new LoaderContext(true, ApplicationDomain.currentDomain, SecurityDomain.currentDomain);
			loader.load( new URLRequest( url ), context);
			
		}
		
		private function onProgress(e:ProgressEvent):void {
			var percent:int = Math.round( (barHolderWidth-4) * (e.bytesLoaded/e.bytesTotal));
			bar.graphics.clear();
			bar.graphics.beginFill(0x000000,1);
			bar.graphics.drawRect(0,0,percent,8);
			bar.graphics.endFill();
		}
		
		private function onLoaderComplete(e:Event):void {
			this.removeEventListener( Event.RESIZE, onResize );
			this.removeChild( barHolder );
			loader.visible = true;
		}
		
		private function onResize(e:Event):void {
			barHolder.x = (stage.stageWidth - barHolderWidth)/2;
			barHolder.y = (stage.stageHeight - 20)/2;
		}
	}
}