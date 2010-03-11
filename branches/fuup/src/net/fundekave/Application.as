package net.fundekave
{
    import com.bit101.components.Component;
    import com.dncompute.canvas.BrowserCanvas;
    
    import flash.events.Event;
	
	
    public class Application extends Container
    {
    	private static var instance:Application;
		
		public var outterId:String;
    			
        public function Application()
        {
            super();
			this.addEventListener(Event.ADDED_TO_STAGE, onStage);
			
			var params:Object = this.loaderInfo.parameters;
			var configUrl:String; 
			if(params.hasOwnProperty('containerId')) {
				outterId = params.containerId;
			}
            instance = this;
        }
		
		private function onStage(e:Event):void {
			this.removeEventListener(Event.ADDED_TO_STAGE, onStage);
			Component.initStage( stage );
		}
        
        public static function get application():Application {
        	return instance;
        }
		
		override public function set width(w:Number):void {
			var resizeCanvas:BrowserCanvas = BrowserCanvas.getInstance();
			resizeCanvas.width = String( (w==-1)?('100%'):(w) );
			if(w==-1) {
				super.width = stage.width<Fuup.WIDTH ? Fuup.WIDTH : stage.width;
			} else {
				super.width = w;
			}
			trace("WIDTH::"+this.width);
		}
		
		private var _prevHeight:Number;
		override public function set height(h:Number):void {
			_prevHeight = this.height;
			var resizeCanvas:BrowserCanvas = BrowserCanvas.getInstance();
			resizeCanvas.height = String( (h==-1)?('100%'):(h) );
			if(h==-1) {
				super.height = stage.height<Fuup.HEIGHT ? Fuup.HEIGHT : stage.height;
			} else {
				super.height = h;
			}
			trace("HEIGHT::"+this.height);
		}
		
		public function restoreHeight():void {
			if(_prevHeight > 0) {
				this.height = _prevHeight;
				_prevHeight = 0;
			}
		}
    }
}