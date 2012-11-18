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
	import flash.utils.ByteArray;
	import flash.utils.setTimeout;
	
	import nochump.util.zip.CRC32;
	
	public class FileUpload extends EventDispatcher
	{
		public static const COMPLETE:String = 'complete';
		public static const ERROR:String = 'error';
		public static const PROGRESS:String = 'progress';
		
		public var isMultipart:Boolean = true;
		
		public var serviceURL:String;
		public var filename:String;
		public var chunkSize:int = 0;
		public var uploadLimit:int = 1;
		
		private var chunks:Array;
		private var numChunks:int = 0;
		private var currentChunk:int = 0;
		private var chunksUploading:int = 0;
		
		public var extraVars:Object = {};
		
		private var isStoped:Boolean = false;
		private var serviceList:Vector.<Service>;
		
		public function FileUpload(url:String, filename:String, chunkSize:int = 0, uploadLimit:int = 1)
		{
			serviceList = new Vector.<Service>;
			this.serviceURL = url;
			this.filename = filename;
			this.chunkSize = chunkSize;
			this.uploadLimit = uploadLimit;
		}
		
		public function uploadReference(ref:FileReference):void
		{
			ref.addEventListener(Event.COMPLETE, onRefLoad);
			ref.load();
		}
		
		private function onRefLoad(e:Event):void
		{
			var ref:FileReference = e.target as FileReference;
			ref.removeEventListener(Event.COMPLETE, onRefLoad);
			this.uploadBytes(ref.data);
		}
		
		private function stringChunks(bytes:ByteArray):void
		{
			var encodedStr:String = Base64.encodeByteArray(bytes);
			if (chunkSize > 0)
			{
				numChunks = Math.ceil(encodedStr.length / chunkSize);
			}
			else
			{
				numChunks = 1;
				chunkSize = encodedStr.length + 1;
			}
			chunks = [];
			for (var i:int = 0; i < numChunks; i++)
			{
				chunks.push({filename: filename, seq: i, total: numChunks, data: encodedStr.slice(i * chunkSize, (i * chunkSize) + chunkSize)});
			}
			encodedStr = null;
		}
		
		private function byteChunks(bytes:ByteArray):void
		{
			if (chunkSize > 0)
			{
				numChunks = Math.ceil(bytes.length / chunkSize);
			}
			else
			{
				numChunks = 1;
				chunkSize = bytes.length + 1;
			}
			chunks = [];
			for (var i:int = 0; i < numChunks; i++)
			{
				var chunk:ByteArray = new ByteArray();
				var length:Number = bytes.length < (i * chunkSize) + chunkSize ? bytes.length - i * chunkSize : chunkSize;
				chunk.writeBytes(bytes, i * chunkSize, length);
				chunks.push({filename: filename, seq: i, total: numChunks, data: chunk});
			}
		}
		
		public function uploadBytes(bytes:ByteArray):void
		{
			isStoped = false;
			
			var crc32:CRC32 = new CRC32();
			crc32.update(bytes);
			extraVars['crcTotal'] = crc32.getValue();
			
			if (isMultipart === true)
			{
				byteChunks(bytes);
			}
			else
			{
				stringChunks(bytes);
			}
			upload();
		}
		
		public function stop():void {
			isStoped = true;
			for each(var service:Service in serviceList) service.stop();
			serviceList = new Vector.<Service>;
		}
		
		private function upload():void
		{
			if (isStoped) return;
			
			if (chunks.length > 0 && chunksUploading < uploadLimit)
			{
				//---prepare service
				var service:Service = new Service();
				serviceList.push(service);
				service.addEventListener(Event.COMPLETE, onServiceComplete);
				service.addEventListener(IOErrorEvent.IO_ERROR, onServiceError, false, 0, true);
				service.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError, false, 0, true);
				service.addEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError, false, 0, true);
				service.isMultipart = isMultipart;
				service.url = serviceURL;
				service.variables = chunks.shift();
				trace('FileUpload::upload - data size: '+(service.variables.data as ByteArray).length);
				for (var name:String in extraVars)
				{
					service.variables[name] = extraVars[name];
				}
				service.send();
				
				chunksUploading++;
				
				trace('CHUNK::UPLOADING::file::' + String(service.variables.filename) + '::chunk::' + String(currentChunk) + '/' + String(numChunks));
				
				//---start more chunks if uploadLimit
				if (chunks.length > 0)
					setTimeout(upload, 500);
			}
		}
		
		private function onServiceComplete(e:Event):void
		{
			
			var service:Service = e.target as Service;
			
			if (service.data != '1')
			{
				trace('SERVICE RETURN ERROR::ANOTHER ATTEMPT');
				service.failed();
				return;
			}
			
			serviceList.splice(serviceList.indexOf(service), 1);
			
			service.removeEventListener(Event.COMPLETE, onServiceComplete);
			service.removeEventListener(IOErrorEvent.IO_ERROR, onServiceError);
			service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError);
			service.removeEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError);
			
			if (isMultipart)
			{
				(service.request.data as ByteArray).clear();
			}
			
			service.close();
			service = null;
			
			if (isStoped) return;
			
			chunksUploading--;
			currentChunk++;
			
			trace('CHUNK::DONE::chunk::' + String(currentChunk) + '/' + String(numChunks));
			
			if (chunksUploading < uploadLimit && chunks.length > 0)
			{
				setTimeout(upload, 100);
			}
			
			//---dispatch event progress
			dispatchEvent(new ProgressEvent(PROGRESS, false, false, currentChunk, numChunks));
			
			if (chunksUploading == 0 && chunks.length == 0)
			{
				//---upload done
				trace('FILE COMPLETE');
				dispatchEvent(new Event(COMPLETE));
			}
		
		}
		
		private function onServiceError(e:Event):void
		{
			if (isStoped) return;
			var service:Service = e.target as Service;
			service.failed();
			trace('Connection Error::another attempt');
		}
		
		private function onServiceTotalError(e:Event):void
		{
			if (isStoped) return;
			
			trace('TOTAL SERVICE ERROR');
			var service:Service = e.target as Service;
			serviceList.splice(serviceList.indexOf(service), 1);
			service.removeEventListener(Event.COMPLETE, onServiceComplete);
			service.removeEventListener(IOErrorEvent.IO_ERROR, onServiceError);
			service.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, onServiceError);
			service.removeEventListener(Service.ATTEMPTS_ERROR, onServiceTotalError);
			dispatchEvent(new ErrorEvent(ERROR));
		}
	
	}
}