package net.fundekave.lib
{
	import flash.events.ErrorEvent;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLLoaderDataFormat;
	import flash.net.URLRequest;
	import flash.net.URLRequestHeader;
	import flash.net.URLRequestMethod;
	import flash.net.URLVariables;
	import nochump.util.zip.CRC32;
	
	public class Service extends URLLoader
	{
		public static const ATTEMPTS_ERROR:String = 'attemptsError';
		
		public var isMultipart:Boolean = false;
		
		public var attemptsLimit:int = 3;
		public var attempts:int = 0;
		
		public var url:String;
		public var variables:Object;
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
				this.request.method = URLRequestMethod.POST;
			}
			
			if(isMultipart===true) {
				var strippedVars:Object = {};
				for(var name:String in variables) {
					if(name != 'filename' && name != 'data') {
						strippedVars[name] = variables[name];
					}
				}
				var crc32:CRC32 = new CRC32();
				crc32.update(variables.data);
				strippedVars['crc'] = crc32.getValue();
				this.request.data = UploadPostHelper.getPostData( variables.filename, variables.data, strippedVars);
				this.request.requestHeaders.push( new URLRequestHeader( 'Cache-Control', 'no-cache' ) );
				this.request.requestHeaders.push( new URLRequestHeader('Content-type', 'multipart/form-data; boundary=' + UploadPostHelper.getBoundary()) );
				this.dataFormat = URLLoaderDataFormat.BINARY;
			} else {
				var vars:URLVariables = new URLVariables();
				vars.data = variables.data;  
				vars.seq = variables.seq;
				vars.total = variables.total;
				vars.filename = variables.filename;
				this.request.data = vars;
				this.dataFormat = URLLoaderDataFormat.TEXT;
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