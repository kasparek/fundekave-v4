package net.fundekave.fuup.model
{    

      import com.adobe.images.JPGEncoder;
      
      import flash.display.BitmapData;
      import flash.events.Event;
      import flash.geom.Matrix;
      import flash.utils.setTimeout;
      
      import mx.controls.Alert;
      import mx.controls.Image;
      import mx.core.Application;
      import mx.rpc.events.FaultEvent;
      import mx.rpc.events.ResultEvent;
      import mx.rpc.http.HTTPService;
      
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.model.vo.*;
      import net.fundekave.lib.BitmapDataProcess;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;  
        
      public class FileProxy extends Proxy implements IProxy
      {
		public static const NAME:String = 'fileProxy';
        private var httpService:HTTPService;
        
        //---global settings
        [Bindable]
        public var widthMax:Number = 700;
        [Bindable]
        public var heightMax:Number = 700;
        [Bindable]
        public var outputQuality:Number = 80;
        
        public var fileList:Array = new Array();
        
        public function FileProxy( )
        {
			super( NAME );
			httpService  = new HTTPService();
			httpService.method = "POST";
			httpService.addEventListener(ResultEvent.RESULT, httpResult);
            httpService.addEventListener(FaultEvent.FAULT, httpFault);
      
        }
        
        public function updateFiles():void {
        	var i:int;
        	var len:int = fileList.length;
        	if(len > 0) {
        		for(i=0;i<len;i++) {
        			var fileVO:FileVO = fileList[i] as FileVO;
        			updateFile( fileVO );
        		}
        	}
        }
        public function updateFile( fileVO:FileVO ):void {
        	if(fileVO.sizeInheritance == true) {
        		fileVO.widthMax = widthMax
        		fileVO.heightMax = heightMax
        	}
        	fileVO.outputQuality = outputQuality
        	
        	var scaled:Object = BitmapDataProcess.scaleCalc(fileVO.widthOriginal,fileVO.heightOriginal,fileVO.widthMax,fileVO.heightMax);
        	fileVO.widthNew = scaled.width;
        	fileVO.heightNew = scaled.height;
        }
        
        private var currentFile:int = 0;
        
        public function processFiles():void {
        	currentFile = 0;
        	processFile();
        }
        
        private function processFile():void {
        	var len:int = fileList.length;
        	if(currentFile < len) {
        		var fileVO:FileVO = fileList[currentFile] as FileVO;
        		
        		var image:Image = new Image();
        		image.source = fileVO.file.data;
        		image.addEventListener(Event.COMPLETE, onImageReady );
        		Application.application.addChild( image ); 
        		
        	} else {
        		//---processing done
        	}
        }
        
        private function onImageReady(e:Event):void {
        	var image:Image = e.target as Image;
        	image.removeEventListener(Event.COMPLETE, onImageReady );
        	
        	var fileVO:FileVO = fileList[currentFile] as FileVO;
        	
        	var ratio:Number = fileVO.widthNew / fileVO.widthOriginal;
        	var matrix:Matrix = new Matrix();
  			matrix.scale( ratio, ratio );
 
  			var bmpd:BitmapData = new BitmapData( fileVO.widthNew, fileVO.heightNew );	
  			bmpd.draw( image, matrix, null, null, null, true );

        	var jpgEnc:JPGEncoder = new JPGEncoder( fileVO.outputQuality );
        	fileVO.encodedJPG = jpgEnc.encode( bmpd );
        	
        	var i:Image = new Image();
        	i.source = fileVO.encodedJPG;
        	fileVO.renderer.addChild( i );
        	
        	
        	
        	image.parent.removeChild( image );
        	
        	currentFile++;
        	//---send progress
        	sendNotification( ApplicationFacade.PROCESS_PROGRESS, {processed:currentFile,total:fileList.length} );
        	
        	setTimeout(processFile, 100);
        }
        
        
        public function upload():void        
        {
        	 
			httpService.send( {} );
			        
        }
        public virtual function httpResult(event:ResultEvent):void
        {         
			
        }
   
        public function httpFault (event:FaultEvent):void
        {
        	Alert.show('Connection Error','Error');
  			sendNotification( ApplicationFacade.SERVICE_ERROR, event.fault.faultString );
        }       
	}
}