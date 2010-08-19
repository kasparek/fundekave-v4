/**
 * Window.as
 * Keith Peters
 * version 0.97
 * 
 * A draggable window. Can be used as a container for other components.
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
	import com.greensock.TweenLite;
	
	import flash.display.DisplayObjectContainer;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.MouseEvent;
	
	public class CloseWindow extends Window
	{
		public var closeTween:Object;
		public var closeTweenDuration:Number = 0.5;
		
		/**
		 * Constructor
		 * @param parent The parent DisplayObjectContainer on which to add this Panel.
		 * @param xpos The x position to place this component.
		 * @param ypos The y position to place this component.
		 * @param title The string to display in the title bar.
		 */
		public function CloseWindow(parent:DisplayObjectContainer=null, xpos:Number=0, ypos:Number=0, title:String="Window")
		{
			super(parent,xpos,ypos,title);
		}
		
		/**
		 * Creates and adds the child display objects of this component.
		 */
		override protected function addChildren():void
		{
			super.addChildren();
			
			_closeButton = new Sprite();
			_closeButton.graphics.beginFill(0, 0);
			_closeButton.graphics.drawRect(-5, -5, 10, 10);
			_closeButton.graphics.endFill();
			_closeButton.graphics.beginFill(0, .35);
			_closeButton.graphics.moveTo(-3, -5);
			_closeButton.graphics.lineTo(-5, -3);
			_closeButton.graphics.lineTo(-1.5, 0);
			_closeButton.graphics.lineTo(-5, 3);
			_closeButton.graphics.lineTo(-3, 5);
			_closeButton.graphics.lineTo(0, 1.5);
			_closeButton.graphics.lineTo(3, 5);
			_closeButton.graphics.lineTo(5, 3);
			_closeButton.graphics.lineTo(1.5, 0);
			_closeButton.graphics.lineTo(5, -3);
			_closeButton.graphics.lineTo(3, -5);
			_closeButton.graphics.lineTo(0, -1.5);
			_closeButton.graphics.endFill();
			_closeButton.y = 10;
			_closeButton.useHandCursor = true;
			_closeButton.buttonMode = true;
			_closeButton.addEventListener(MouseEvent.CLICK, onClose,false,0,true );
		}
		
		
		
		
		///////////////////////////////////
		// public methods
		///////////////////////////////////
		public function close():void {
			onClose(null);
		}
		
		///////////////////////////////////
		// event handlers
		///////////////////////////////////
		override protected function onClose(event:MouseEvent):void
		{
			if(closeTween) {
				closeTween.onComplete = this.onCloseTween;
				TweenLite.to( this, closeTweenDuration, closeTween );
			} else {
				onCloseTween();
			}
			
		}
		private function onCloseTween():void {
			dispatchEvent( new Event( Event.CLOSE ));
			while(numChildren>0) removeChildAt(0);
			this.parent.removeChild( this );
		}
		
	}
}