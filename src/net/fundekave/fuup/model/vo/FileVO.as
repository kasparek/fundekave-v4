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
		
		[Bindable]
		public var filename:String;
		public var filenameOriginal:String;
		
		[Bindable]
		public var widthOriginal:Number;
		[Bindable]
		public var heightOriginal:Number;
		
		[Bindable]
		public var sizeInheritance:Boolean = true;
		[Bindable]
		public var widthMax:Number;
		[Bindable]
		public var heightMax:Number;
		
		[Bindable]
		public var widthNew:Number;
		[Bindable]
		public var heightNew:Number;
		
		public var rotationCurrent:Number = 0;
		public var rotationFromOriginal:Number = 0;
		[Bindable]
		public var rotation:Number = 0;
		
		[Bindable]
		public var outputQuality:Number = 100;
		
		public var crop:Boolean = false;
		
		function FileVO(filename:String):void {
			
			this.filename = filename;
			this.filenameOriginal = String( this.filename );
			
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