package net.fundekave
{
    import com.bit101.components.Component;

    public class Application extends Container
    {
        public function Application()
        {
            super();
            Component.initStage( stage );
        }
    }
}