/**
 * Label.as
 * Keith Peters
 * version 0.97
 * 
 * A Label component for displaying a single line of text.
 * 
 * Copyright (c) 2009 Keith Peters
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
 
package com.bit101.components
{
	import flash.display.DisplayObjectContainer;
	import flash.text.AntiAliasType;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;
	import flash.text.engine.TextLine;
	
	import flashx.textLayout.controls.TLFTextField;
	
	public class Label extends Component
	{
		
		private static const HEIGHT:int = 18;
		
		private var _autoSize:Boolean = true;
		private var _text:String = "";
		private var _tf:TLFTextField;
		
		private var recreate:Boolean = false;
		private var _color:uint = Style.LABEL_TEXT;
		public function set color(v:uint):void {
			_color = v;
			recreate = true;
			this.invalidate();
		}
		public function get color():uint {
			return _color;
		}
				
		/**
		 * Constructor
		 * @param parent The parent DisplayObjectContainer on which to add this Label.
		 * @param xpos The x position to place this component.
		 * @param ypos The y position to place this component.
		 * @param text The string to use as the initial text in this component.
		 */
		public function Label(parent:DisplayObjectContainer = null, xpos:Number = 0, ypos:Number =  0, text:String = "", color:uint=0)
		{
			_text = text;
			if(color>0) this.color = color;
			super(parent, xpos, ypos);
			trace('LABEL::color::'+String( color ));
		}
		
		/**
		 * Initializes the component.
		 */
		override protected function init():void
		{
			super.init();
			mouseEnabled = false;
			mouseChildren = false;
		}
		
		/**
		 * Creates and adds the child display objects of this component.
		 */
		override protected function addChildren():void
		{
			_height = HEIGHT;
			
			_tf = new TLFTextField();
			_tf.height = _height;
			_tf.embedFonts = true;
			_tf.selectable = false;
			_tf.antiAliasType = AntiAliasType.ADVANCED;
			_tf.mouseEnabled = false;
						
			_tf.setTextFormat( new TextFormat( "PF Ronda Seven", 8,  this.color ));
			_tf.text = _text;
			_tf.y = -2;
			addChild(_tf);
			
			draw();
		}
		
		private function addTextLineToContainer(textLine:flash.text.engine.TextLine):void
		{
			this.addChild(textLine);
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
			
			if(recreate===true) {
				recreate = false;
				this.removeChild( _tf );
				this.addChildren();
			}
			
			_tf.text = _text;
			
			if(_autoSize)
			{
				_tf.autoSize = TextFieldAutoSize.LEFT;
				_width = _tf.width;
			}
			else
			{
				_tf.autoSize = TextFieldAutoSize.NONE;
				_tf.width = _width;
			}
			
			_height = _tf.height = HEIGHT;
		}
		
		///////////////////////////////////
		// event handlers
		///////////////////////////////////
		
		///////////////////////////////////
		// getter/setters
		///////////////////////////////////
		
		/**
		 * Gets / sets the text of this Label.
		 */
		public function set text(t:String):void
		{
			_text = t;
			invalidate();
		}
		public function get text():String
		{
			return _text;
		}
		
		/**
		 * Gets / sets whether or not this Label will autosize.
		 */
		public function set autoSize(b:Boolean):void
		{
			_autoSize = b;
		}
		public function get autoSize():Boolean
		{
			return _autoSize;
		}
	}
}