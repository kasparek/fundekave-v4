package
{
	import flash.display.Loader;
	import flash.display.Sprite;
	import flash.display.StageAlign;
	import flash.display.StageScaleMode;
	import flash.events.Event;
	import flash.events.ProgressEvent;
	import flash.net.URLRequest;
	
	public class PreloaderBar extends Sprite
	{
		
		private var loader:Loader;
		private var barHolder:Sprite;
		private var bar:Sprite;
		
		public function PreloaderBar()
		{
			stage.scaleMode = StageScaleMode.NO_SCALE;
			stage.align = StageAlign.TOP_LEFT;
			
			this.addEventListener( Event.ADDED_TO_STAGE, onStage );
			this.addEventListener( Event.RESIZE, onResize );
		}
		
		private function onStage(e:Event):void {
			this.removeEventListener( Event.ADDED_TO_STAGE, onStage );
			
			bar = new Sprite();
			barHolder = new Sprite();
			barHolder.graphics.lineStyle(1,0x666666);
			barHolder.graphics.beginFill(0xeeeeee);
			barHolder.graphics.drawRect(0,0,104,12);
			barHolder.graphics.endFill();
			bar.x = 2;
			bar.y = 2;
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
			loader.load( new URLRequest( url ));
			
		}
		
		private function onProgress(e:ProgressEvent):void {
			var percent:int = Math.round( 100 * (e.bytesLoaded/e.bytesTotal));
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
			barHolder.x = (this.stage.stageWidth - 110)/2;
			barHolder.y = (this.stage.stageHeight - 20)/2;
		}
	}
}