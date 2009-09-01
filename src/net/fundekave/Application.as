package net.fundekave
{
    import com.bit101.components.Component;

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
    }
}