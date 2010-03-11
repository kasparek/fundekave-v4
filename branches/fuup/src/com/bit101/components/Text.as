/**
 * Label.as
 * Keith Peters
 * version 0.97
 * 
 * A Text component for displaying multiple lines of text.
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
	import flash.events.Event;
	import flash.text.AntiAliasType;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFieldType;
	import flash.text.TextFormat;
	
	import flashx.textLayout.controls.TLFTextField;
	
	public class Text extends Component
	{
		private var _tf:TLFTextField;
		private var _text:String = "";
		private var _editable:Boolean = false;
		
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
		private var _size:uint = Style.SIZE_TEXT;
		public function set size(v:uint):void {
			_size = v;
			recreate = true;
			this.invalidate();
		}
		public function get size():uint {
			return _size;
		}
		private var _font:String = Style.FONT_TEXT;
		public function set font(v:String):void {
			_font = v;
			recreate = true;
			this.invalidate();
		}
		public function get font():String {
			return _font;
		}
		
		private var _leading:int;
		public function set leading(v:int):void {
			_leading = v;
			recreate = true;
			this.invalidate();
		}
		public function get leading():int {
			return _leading;
		}
		
		private var _textAlign:String;
		public function set textAlign(v:String):void {
			_textAlign = v;
			recreate = true;
			this.invalidate();
		}
		public function get textAlign():String {
			return _textAlign;
		}
		
		private var _align:String = TextFieldAutoSize.LEFT;
		public function set align(v:String):void {
			_align = v;
			recreate = true;
			this.invalidate();
		}
		public function get align():String {
			return _align;
		}
		
		
		override public function set width(v:Number):void {
			super.width = v
			recreate = true;
			this.invalidate();
		}
		
		/**
		 * Constructor
		 * @param parent The parent DisplayObjectContainer on which to add this Label.
		 * @param xpos The x position to place this component.
		 * @param ypos The y position to place this component.
		 * @param text The initial text to display in this component.
		 */
		public function Text(parent:DisplayObjectContainer = null, xpos:Number = 0, ypos:Number =  0, text:String = "", color:uint=0, size:uint=0,font:String=null,align:String=null)
		{
			_text = text;
			if(color>0) this.color = color;
			if(size>0) this.size = size;
			if(font) this.font = font;
			if(align) this.align = align;
			super(parent, xpos, ypos);
			//setSize(200, 100);
		}
		
		/**
		 * Initializes the component.
		 */
		override protected function init():void
		{
			super.init();
		}
		
		/**
		 * Creates and adds the child display objects of this component.
		 */
		override protected function addChildren():void
		{
			_tf = new TLFTextField();
			_tf.width  = _width;
			_tf.embedFonts = true;
			_tf.selectable = false;
			_tf.antiAliasType = AntiAliasType.ADVANCED;
			_tf.mouseEnabled = false;
			_tf.multiline = true;
			_tf.wordWrap = true;
			//_tf.type = TextFieldType.INPUT;
			//_tf.addEventListener(Event.CHANGE, onChange,false,0,true );
			var tformat:TextFormat = new TextFormat( this.font, this.size,  this.color );
			if(textAlign) tformat.align = textAlign;
			if(leading) tformat.leading = leading;
			
			_tf.setTextFormat( tformat );
			_tf.text = _text;
			_tf.autoSize = this.align;
			addChild(_tf);
			
			draw();
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
			_tf.autoSize = this.align;
			
			if(_editable)
			{
				_tf.mouseEnabled = true;
				_tf.selectable = true;
				_tf.type = TextFieldType.INPUT;
			}
			else
			{
				_tf.mouseEnabled = false;
				_tf.selectable = false;
				_tf.type = TextFieldType.DYNAMIC;
			}
			
			_height = _tf.height;
		}
		
		
		
		
		///////////////////////////////////
		// event handlers
		///////////////////////////////////
		
		protected function onChange(event:Event):void
		{
			_text = _tf.text;
			dispatchEvent(event);
		}
		
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
		 * Gets / sets whether or not this text component will be editable.
		 */
		public function set editable(b:Boolean):void
		{
			_editable = b;
			invalidate();
		}
		public function get editable():Boolean
		{
			return _editable;
		}
	}
}