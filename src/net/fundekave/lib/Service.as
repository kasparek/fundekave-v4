package net.fundekave.lib
{
	import flash.events.ErrorEvent;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	import flash.net.URLVariables;
	
	public class Service extends URLLoader
	{
		public static const ATTEMPTS_ERROR:String = 'attemptsError';
		
		public var attemptsLimit:int = 3;
		public var attempts:int = 0;
		
		public var url:String;
		public var variables:URLVariables;
		public var request:URLRequest;
		
		public function Service(request:URLRequest=null)
		{
			if( request ) this.request = request;
			super(request);
		}
		
		public function retry():void {
			this.send();
		}
		
		public function send(request:URLRequest=null):void {
			if( request ) this.request = request;
			else {
				this.request = new URLRequest( this.url );
				this.request.method = 'POST';
				this.request.data = this.variables;
			}
			
			super.load( this.request );
		}
		
		public function failed():void {
			
			if( this.attempts < this.attemptsLimit ) {
				this.send();
			} else {
				dispatchEvent( new ErrorEvent( ATTEMPTS_ERROR ));
			}
			
			this.attempts++;
			
		}
	}
}