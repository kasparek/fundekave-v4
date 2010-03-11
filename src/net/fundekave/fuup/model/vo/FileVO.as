package net.fundekave.fuup.model.vo
{
	import flash.display.BitmapData;
	import flash.net.FileReference;
	import flash.utils.ByteArray;
	
	import net.fundekave.fuup.view.components.FileView;
	
	public class FileVO {
		
		public var file:FileReference;
		public var processedBitmapData:BitmapData;
		
		public var renderer:FileView;
		public var encodedJPG:ByteArray;
		
		
		private var _filename:String;
		
		public function get filename():String { return _filename; }
		public function set filename(s:String):void {
			this._filename = s;
			this.filenameOriginal = String( s );
		}
		public var filenameOriginal:String;
		
		
		public var widthOriginal:Number;
		public var heightOriginal:Number;
		
		public var sizeInheritance:Boolean = true;
		public var widthMax:Number;
		public var heightMax:Number;
		
		public var widthNew:Number;
		public var heightNew:Number;
		
		public var rotationCurrent:Number = 0;
		public var rotationFromOriginal:Number = 0;
		public var rotation:Number = 0;
		
		public var outputQuality:Number = 100;
		
		public var crop:Boolean = false;
		public var showThumb:Boolean = true;
		
		function FileVO(filename:String=null):void {
			if(filename) {
				this.filename = filename;
			}
		}
		
		public function destroy():void {
			if(file.data) file.data.clear();
			file = null;
			if(processedBitmapData) processedBitmapData.dispose();
			processedBitmapData = null;
			if(encodedJPG) encodedJPG.clear();
			encodedJPG = null;
		}
		
	}
}