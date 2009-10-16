package net.fundekave
{
    import com.bit101.components.Component;
    
    import flash.display.DisplayObject;
    import flash.display.DisplayObjectContainer;
    import flash.display.Sprite;
    import flash.events.Event;

    [DefaultProperty( "children" )]
    public class Container extends Component
    {
        private var _children:Vector.<DisplayObject>;
        private var childrenChanged:Boolean = false;
        
        public var backgroundColor:Number;
        public var backgroundAlpha:Number=1;
        public var masked:Boolean = false;
        public var border:int = 0;
        
        /**
         * Array of DisplayObject instances to be added as children
         */
        public function get children():Vector.<DisplayObject>
        {
            return _children;
        }
        
        public function set children( value:Vector.<DisplayObject> ):void
        {
            if ( _children != value )
            {
                _children = value;
                childrenChanged = true;
                invalidate();
            }
        }
        
        public function Container(parent:DisplayObjectContainer = null, xpos:Number = 0, ypos:Number =  0)
        {
            super(parent, xpos, ypos);
        }
        
        override protected function onInvalidate(event:Event) : void {
            if ( childrenChanged ) {
                while ( numChildren > 0 ) {
                    removeChildAt( 0 );
                }
                for each ( var child:DisplayObject in children ) {
                    addChild( child );
                }
                childrenChanged = false;
            }
            
            //---create mask
        	if(masked === true) {
				mask = new Sprite();
				(mask as Sprite).graphics.beginFill(0x000000);
				(mask as Sprite).graphics.drawRect(0,0,this.width+border, this.height+border);
				(mask as Sprite).graphics.endFill();
				this.addChild( mask );
        	}
			
			this.graphics.clear();
			
        	if(!isNaN(backgroundColor)) {
        		this.graphics.beginFill(backgroundColor,backgroundAlpha);
        		this.graphics.drawRect(0,0,this.width,this.height);
        		this.graphics.endFill();
        	}
        	if(border>0) {
        		this.graphics.lineStyle(border,0x000000);
        		this.graphics.drawRect(0,0,this.width,this.height);
        	}
            
            super.onInvalidate(event);
        }
    }
}