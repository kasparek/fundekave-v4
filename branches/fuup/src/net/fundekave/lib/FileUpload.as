package net.fundekave.lib
{
	import com.dynamicflash.util.Base64;
	
	import flash.events.ErrorEvent;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.events.SecurityErrorEvent;
	import flash.net.FileReference;
	import flash.net.URLVariables;
	import flash.utils.ByteArray;
	import flash.utils.setTimeout;
		
	public class FileUpload extends EventDispatcher
	{
		public static const COMPLETE:String = 'complete';
		public static const ERROR:String = 'error';
		public static const PROGRESS:String = 'progress';
		
		public var serviceURL:String;
		public var filename:String;
		public var chunkSize:int = 0;
		public var uploadLimit:int = 1;
		
		private var chunks:Array;
		private var numChunks:int = 0;
		private var currentChunk:int = 0;
        private var chunksUploading:int = 0
		
		public function FileUpload(url:String,filename:String, chunkSize:int=0, uploadLimit:int=1)
		{
			this.serviceURL = url;
			this.filename = filename;
			this.chunkSize = chunkSize;
			this.uploadLimit = uploadLimit;
		}
		public function uploadReference( ref:FileReference ):void {
			ref.addEventListener(Event.COMPLETE, onRefLoad);
			ref.load();
		}
		private function onRefLoad(e:Event):void {
			var ref:FileReference = e.target as FileReference;
			ref.addEventListener(Event.COMPLETE, onRefLoad);
			this.uploadBytes( ref.data );
			setTimeout( ref.data.clear, 100 );
		}
		public function uploadBytes( bytes:ByteArray ):void {
        	var encodedStr:String = Base64.encodeByteArray( bytes );
        	//---prepare all chunks
        	if( chunkSize > 0) {
	        	numChunks = Math.ceil( encodedStr.length / chunkSize );
	        } else {
	        	numChunks = 1;
	        	chunkSize = encodedStr.length+1;
	        }
	        chunks = [];
        	for(var i:int=0;i < numChunks; i++) {
        		chunks.push( {filename:filename ,seq:i,total:numChunks,data:encodedStr.slice( i*chunkSize, (i*chunkSize)+chunkSize )} );	
        	}
        	encodedStr = null;
        	
        	upload();
		}
		
		public function upload():void        
        {
        	if(chunks.length > 0 && chunksUploading < uploadLimit) {
        		
        		//---prepare service
	        	var service:Service = new Service();
	        	service.addEventListener(Event.COMPLETE, onServiceComplete);
	        	service.addEventListener(IOErrorEvent.IO_ERROR, onServiceError ,false,0,true );
	        	service.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError ,false,0,true );
	        	service.addEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError ,false,0,true );
        		
        		var vars:URLVariables = new URLVariables();
        		var dataObj:Object = chunks.shift();
     			vars.data = dataObj.data;  
     			vars.seq = dataObj.seq;
     			vars.total = dataObj.total;
     			vars.filename = dataObj.filename;
        		
        		service.url = serviceURL;
        		service.variables = vars; 
				service.send();
				
				chunksUploading++;
				
				trace('CHUNK::UPLOADING::file::'+String(dataObj.filename)+'::chunk::'+String(currentChunk)+'/'+String(numChunks));
				
				//---start more chunks if uploadLimit
				if(chunks.length>0) setTimeout( upload, 500 );
        	}
        }
        
        private function onServiceComplete(e:Event):void
        {   
        	
        	var service:Service = e.target as Service;
	        
	        if(service.data != '1') {
	        	trace('SERVICE RETURN ERROR::ANOTHER ATTEMPT');
	        	service.failed();
	        	return;
	        }
	        
	        service.removeEventListener(Event.COMPLETE, onServiceComplete );
	        service.removeEventListener(IOErrorEvent.IO_ERROR, onServiceError );
	        service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError );
	        service.removeEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError );
        	
			chunksUploading--;
			currentChunk++;
			
			trace('CHUNK::DONE::chunk::'+String(currentChunk)+'/'+String(numChunks));
			
			if(chunksUploading < uploadLimit && chunks.length > 0) {
				upload();
			}
			
			//---dispatch event progress
			dispatchEvent( new ProgressEvent( PROGRESS, false, false, currentChunk, numChunks));
			
			if(chunksUploading == 0 && chunks.length == 0) {
				//---upload done
        		trace('FILE COMPLETE');
        		dispatchEvent( new Event( COMPLETE ));
			}
			
        }
   
        private function onServiceError(e:Event):void
        {
        	var service:Service = e.target as Service;
        	service.failed();
        	trace('Connection Error::another attempt');

        }
        
        private function onServiceTotalError(e:Event):void {
        	trace('TOTAL SERVICE ERROR');
        	
        	var service:Service = e.target as Service;
        	service.removeEventListener(Event.COMPLETE, onServiceComplete );
	        service.removeEventListener(IOErrorEvent.IO_ERROR, onServiceError );
	        service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError );
	        service.removeEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError );
        	
        	dispatchEvent( new ErrorEvent( ERROR ));

        }
        
	}
}