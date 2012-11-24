package net.fundekave.lib
{
	import flash.events.ErrorEvent;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.SecurityErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLLoaderDataFormat;
	import flash.net.URLRequest;
	import flash.net.URLRequestHeader;
	import flash.net.URLRequestMethod;
	import flash.net.URLVariables;
	import flash.utils.ByteArray;
	import nochump.util.zip.CRC32;
	import flash.utils.setTimeout;
	import flash.utils.clearTimeout;
	
	public class Service extends URLLoader
	{
		public static const ATTEMPTS_ERROR:String = 'attemptsError';
		
		public var isMultipart:Boolean = false;
		
		public var attemptsLimit:int = 5;
		public var attempts:int = 0;
		public var timeout:int = 0;
		private var timeoutId:uint;
		
		public var url:String;
		public var variables:Object;
		public var request:URLRequest;
		
		private var isStoped:Boolean = false;
		private var isListeners:Boolean = false;
		
		public function Service(request:URLRequest = null)
		{
			if (request)
				this.request = request;
			
			super(request);
		}
		
		public function stop():void {
			isStoped = true;
		}
		
		public function retry():void
		{
			if (isStoped) return;
			this.send();
		}
		
		public function send(request:URLRequest = null):void
		{
			if (isStoped) return;
			if (request)
				this.request = request;
			else
			{
				this.request = new URLRequest(this.url);
				this.request.method = URLRequestMethod.POST;
			}
			
			if (!isListeners) {
				isListeners = true;
				this.addEventListener(Event.COMPLETE, cancelTimeout, false, 0, true);
				this.addEventListener(IOErrorEvent.IO_ERROR, cancelTimeout, false, 0, true);
				this.addEventListener(SecurityErrorEvent.SECURITY_ERROR, cancelTimeout, false, 0, true);
			}
			
			if (isMultipart === true)
			{
				var strippedVars:Object = {};
				for (var name:String in variables)
				{
					if (name != 'filename' && name != 'data')
					{
						strippedVars[name] = variables[name];
					}
				}
				if ((variables.data as ByteArray).length == 0) {
					trace('Zero size data, attempt num:' + attempts);
				}
				var crc32:CRC32 = new CRC32();
				crc32.update(variables.data);
				strippedVars['crc'] = crc32.getValue();
				this.request.data = UploadPostHelper.getPostData(variables.filename, variables.data, strippedVars);
				this.request.requestHeaders.push(new URLRequestHeader('Cache-Control', 'no-cache'));
				this.request.requestHeaders.push(new URLRequestHeader('Content-type', 'multipart/form-data; boundary=' + UploadPostHelper.getBoundary()));
				this.dataFormat = URLLoaderDataFormat.BINARY;
			}
			else
			{
				var vars:URLVariables = new URLVariables();
				vars.data = variables.data;
				vars.seq = variables.seq;
				vars.total = variables.total;
				vars.filename = variables.filename;
				this.request.data = vars;
				this.dataFormat = URLLoaderDataFormat.TEXT;
			}
			if(timeoutId) clearTimeout(timeoutId);
			if(timeout) timeoutId = setTimeout(onTimeout, timeout);
			super.load(this.request);
		}
		
		private function onTimeout():void {
			failed();
		}
		
		private function cancelTimeout(e:Event):void {
			if(timeoutId) clearTimeout(timeoutId);
		}
		
		public function failed():void
		{
			if(timeoutId) clearTimeout(timeoutId);
			if (isStoped) return;
			if (this.attempts < this.attemptsLimit)
			{
				if(timeout) timeoutId = setTimeout(onTimeout, timeout);
				super.load(this.request);
			}
			else
			{
				dispatchEvent(new ErrorEvent(ATTEMPTS_ERROR));
			}
			this.attempts++;
		}
	}
}