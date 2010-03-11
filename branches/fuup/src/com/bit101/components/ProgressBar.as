package com.bit101.components
{
	import flash.display.DisplayObjectContainer;
	import flash.display.Sprite;
	
	public class ProgressBar extends Component
	{
		private var _back:Sprite;
		private var _bar:Sprite;
		private var _value:Number = 0;
		private var _max:Number = 1;

		/**
		 * Constructor
		 * @param parent The parent DisplayObjectContainer on which to add this ProgressBar.
		 * @param xpos The x position to place this component.
		 * @param ypos The y position to place this component.
		 */
		public function ProgressBar(parent:DisplayObjectContainer = null, xpos:Number = 0, ypos:Number =  0)
		{
			super(parent, xpos, ypos);
		}
		
		
		/**
		 * Initializes the component.
		 */
		override protected function init():void
		{
			super.init();
			setSize(100, 10);
		}
		
		/**
		 * Creates and adds the child display objects of this component.
		 */
		override protected function addChildren():void
		{
			_back = new Sprite();
			_back.filters = [getShadow(2, true)];
			addChild(_back);
			
			_bar = new Sprite();
			_bar.x = 1;
			_bar.y = 1;
			_bar.filters = [getShadow(1)];
			addChild(_bar);
			
			_label = new Label(this,0,0,'');
		}
		
		/**
		 * Updates the size of the progress bar based on the current value.
		 */
		protected function update():void
		{
			_bar.scaleX = _value / _max;
		}

		
		
		
		///////////////////////////////////
		// public methods
		///////////////////////////////////
		
		/**
		 * Draws the visual ui of the component.
		 */
		override public function draw():void
		{
			super.draw();
			_back.graphics.clear();
			_back.graphics.beginFill(Style.BACKGROUND);
			_back.graphics.drawRect(0, 0, _width, _height);
			_back.graphics.endFill();
			
			_bar.graphics.clear();
			_bar.graphics.beginFill(Style.PROGRESS_BAR);
			_bar.graphics.drawRect(0, 0, _width - 2, _height - 2);
			_bar.scaleX = 0;
			_bar.graphics.endFill();
			
			_label.autoSize = true;
			_label.move(_width / 2 - _label.width / 2, _height / 2 - _label.height / 2);
		}
		
		
		
		
		///////////////////////////////////
		// event handlers
		///////////////////////////////////
		
		///////////////////////////////////
		// getter/setters
		///////////////////////////////////
		
		/**
		 * Gets / sets the maximum value of the ProgressBar.
		 */
		public function set maximum(m:Number):void
		{
			_max = m;
			_value = Math.min(_value, _max);
			update();
		}
		public function get maximum():Number
		{
			return _max;
		}
		
		/**
		 * Gets / sets the current value of the ProgressBar.
		 */
		public function set value(v:Number):void
		{
			_value = Math.min(v, _max);
			update();
		}
		public function get value():Number
		{
			return _value;
		}
		
		private var _label:Label;
		public function set label(l:String):void {
			_label.text = l;
			_label.draw();
			_label.move((_width-_label.width)/2, (_height-_label.height)/2);
			this.invalidate();
		}
		
	}
}