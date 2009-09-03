package net.fundekave.lib
{
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.filters.BitmapFilter;
	import flash.filters.BlurFilter;
	import flash.filters.ColorMatrixFilter;
	import flash.filters.ConvolutionFilter;
	import flash.geom.Point;
	import flash.geom.Rectangle;

	public class ImageFiltering
	{
		
		public var filterList:XMLList;
		
		public function ImageFiltering( list:XMLList=null )
		{
			if(list) filterList = list;
		}
		
		public function applyAllFilters(bmpData:BitmapData):BitmapData {
			var totalFiltersEnabled:Number = filterList.length()
			if(totalFiltersEnabled>0) {
				var x:int = 1;
				for (var z:int = 0; z < filterList.length();z++) {
					var filter:XML = filterList[z];
					if(filter.@enabled == '1') {
						//--aplly filter
						xmlApplyFilter(filter,bmpData);
					}
				}
			}
			return bmpData;
		}
		
		private function xmlApplyFilter(filterXML:XML,bmp:BitmapData):void {
			var filter:BitmapFilter;
			var localFilterArr:Array = new Array();
			var z:int;
			
			localFilterArr.push(filterXML);
			
			for(var x:int=0;x<localFilterArr.length;x++) {
				filterXML = localFilterArr[x];
				if(filterXML.@type == 'convol' || filterXML.@type == 'color') {
					var matrix:Array;
					if(filterXML.hasOwnProperty('matrix')) {
						matrix = String(filterXML.matrix).split(',');
						for (z = 0;z<matrix.length;z++) matrix[z] = Number(matrix[z]);
					} else {
						matrix = new Array();
						for (z = 1;z<=Number(filterXML.matrixlength);z++) {
							matrix.push(Number(filterXML['matrix'+z]));
						}
					}
				}
				if(filterXML.@type == 'convol') {
					var squareSide:Number = Math.sqrt(matrix.length);
					filter = new ConvolutionFilter(squareSide,squareSide,matrix,parseInt(String(filterXML.divisor)),parseInt(String(filterXML.bias)));
				} else if (filterXML.@type == 'color') {
					filter = new ColorMatrixFilter(matrix);
				} else if (filterXML.@type == 'blur') {
					filter = new BlurFilter(parseInt(String(filterXML.blurX)),parseInt(String(filterXML.blurY)),parseInt(String(filterXML.quality)));
				}
				
				if(filter) {
					//trace('Filter on: '+String(filterXML.name));
					bmp.applyFilter(bmp, new Rectangle(0,0, bmp.width, bmp.height), new Point(0, 0), filter);
					
				}
			}
		}
		
	}
	
}