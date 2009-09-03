package net.fundekave.fuup.model.vo
{
	import com.clevr.graphics.Histogram;
	
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
		
		[Bindable]
		public var rotation:Number = 0;
		
		[Bindable]
		public var outputQuality:Number = 80;
		
		public var histogram:Histogram;
		
		function FileVO(filename:String):void {
			
			this.filename = filename;
			this.filenameOriginal = String( this.filename );
			
		}
		
	}
}