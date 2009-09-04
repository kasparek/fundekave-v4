package net.fundekave
{
    import com.bit101.components.Component;
    
    import flash.display.Sprite;

    public class Application extends Container
    {
    	private static var instance:Application;
    	
    	public var thumbHolder:Sprite;
    	
        public function Application()
        {
            super();
            Component.initStage( stage );
            
            thumbHolder = new Sprite();
            this.addChild( thumbHolder );
            
            instance = this;
        }
        
        public static function get application():Application {
        	return instance;
        }
    }
}