/**
 * Style.as
 * Keith Peters
 * version 0.9.5
 * 
 * A collection of style variables used by the components.
 * If you want to customize the colors of your components, change these values BEFORE instantiating any components.
 * 
 * Copyright (c) 2010 Keith Peters
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
	public class Style
	{
	
	// NOTE: Flex 4 introduces DefineFont4, which is used by default and does not work in native text fields.
		// Use the embedAsCFF="false" param to switch back to DefineFont4. In earlier Flex 4 SDKs this was cff="false".
		// So if you are using the Flex 3.x sdk compiler, switch the embed statment below to expose the correct version.
		
		// Flex 4.x sdk:
		[Embed(source="/assets/pf_ronda_seven.ttf", embedAsCFF="false", fontName="PF Ronda Seven", mimeType="application/x-font")]
		// Flex 3.x sdk:
//		[Embed(source="/assets/pf_ronda_seven.ttf", fontName="PF Ronda Seven", mimeType="application/x-font")]
		protected var Ronda:Class;
	
		public static var BACKGROUND:uint = 0xCCCCCC;
		public static var BUTTON_FACE:uint = 0xFFFFFF;
		public static var INPUT_TEXT:uint = 0x333333;
		public static var LABEL_TEXT:uint = 0x666666;
		public static var DROPSHADOW:uint = 0x000000;
		public static var PANEL:uint = 0xF3F3F3;
		public static var PROGRESS_BAR:uint = 0xFFFFFF;
		
		public static var embedFonts:Boolean = true;
		public static var fontName:String = "PF Ronda Seven";
		public static var fontSize:Number = 8;
	}
}