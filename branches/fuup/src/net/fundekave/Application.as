package net.fundekave
{
    import com.bit101.components.Component;
    import com.dncompute.canvas.BrowserCanvas;
	
	
    public class Application extends Container
    {
    	private static var instance:Application;
    			
        public function Application()
        {
            super();
            Component.initStage( stage );
            instance = this;
        }
        
        public static function get application():Application {
        	return instance;
        }
		
		private var _prevHeight:Number;
		
		override public function set height(h:Number):void {
			_prevHeight = this.height;
			super.height = h;
			var resizeCanvas:BrowserCanvas = BrowserCanvas.getInstance();
			resizeCanvas.height = String( h ); 
			resizeCanvas.width = '100%';
		}
		
		public function restoreHeight():void {
			if(_prevHeight > 0) {
				this.height = _prevHeight;
				_prevHeight = 0;
			}
		}
    }
}