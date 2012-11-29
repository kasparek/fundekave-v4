package net.fundekave.lib
{
	import flash.events.ErrorEvent;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.SecurityErrorEvent;
	import flash.events.ProgressEvent;
	import flash.net.URLLoader;
	import flash.net.URLLoaderDataFormat;
	import flash.net.URLRequest;
	import flash.net.URLRequestHeader;
	import flash.net.URLRequestMethod;
	import flash.net.URLVariables;
	import flash.utils.ByteArray;
	import flash.utils.Timer;
	import flash.events.TimerEvent;
	
	public class Service extends URLLoader
	{
		public static const ATTEMPTS_ERROR:String = 'attemptsError';
		public static const TIMEOUT:String = 'loaderTimeout';
		
		public var isMultipart:Boolean = false;
		
		public var attemptsLimit:int = 5;
		public var attempts:int = 0;
		
		private var _timeoutTimer:Timer;
		private var _timeout:Number = 0;
        public function set timeout(v:Number):void {
			if (_timeout == v) return;
			_timeout = v * 1000;
			if (_timeoutTimer) killTimer();
			if (_timeout > 0) _timeoutTimer = new Timer(_timeout,1);	
		}
		public function get timeout():Number {
			return _timeout;
		}
        		
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
				
		public function retry():void
		{
			load(request);
		}
		
		public function send():void {
			if(!request) {
				request = new URLRequest(url);
				request.method = URLRequestMethod.POST;
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
			
			load(request);
		}
		
		override public function load(request:URLRequest):void
		{
			if (isStoped) return;
			if(timeout > 0) {	
				addEventListener(IOErrorEvent.IO_ERROR, handleLoadActivity,false,0,true);
				addEventListener(SecurityErrorEvent.SECURITY_ERROR, handleLoadActivity,false,0,true);
				addEventListener(Event.COMPLETE, handleLoadActivity,false,0,true);
				addEventListener(ProgressEvent.PROGRESS, handleLoadActivity,false,0,true);
				addEventListener(Event.OPEN, handleLoadActivity, false, 0, true);
				_timeoutTimer.addEventListener(TimerEvent.TIMER, handleTimeout);
				_timeoutTimer.start();
			}
			super.load(request);
		}
				
		public function failed():void
		{
			killTimer();
			if(isStoped) return;
			if(this.attempts < this.attemptsLimit)
			{
				load(request);
			}
			else
			{
				dispatchEvent(new ErrorEvent(ATTEMPTS_ERROR));
			}
			this.attempts++;
		}
		
		override public function close():void
		{
			isStoped = true;
			killTimer();
			super.close();
		}
		
		private function handleLoadActivity(event:Event):void
		{
			killTimer();
		}
		
		private function handleTimeout(event:TimerEvent):void
		{
			trace("Service::handleTimeout");
			super.dispatchEvent(new Event(TIMEOUT, true));
			failed();
		}
		
		private function killTimer(event:Event = null):void
		{
			if (!_timeoutTimer) return;
			
			removeEventListener(IOErrorEvent.IO_ERROR, handleLoadActivity);
			removeEventListener(SecurityErrorEvent.SECURITY_ERROR, handleLoadActivity);
			removeEventListener(Event.COMPLETE, handleLoadActivity);
			removeEventListener(ProgressEvent.PROGRESS, handleLoadActivity);
			removeEventListener(Event.OPEN, handleLoadActivity);

			_timeoutTimer.reset();
			_timeoutTimer.stop();
			_timeoutTimer.removeEventListener(TimerEvent.TIMER, handleTimeout);
		}
	}
}