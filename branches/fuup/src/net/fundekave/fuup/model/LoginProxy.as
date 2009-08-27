package net.fundekave.fuup.model
{    

      import com.adobe.crypto.MD5;
      
      import flash.events.Event;
      import flash.events.IOErrorEvent;
      import flash.net.URLLoader;
      import flash.net.URLRequest;
      
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.model.vo.*;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;
        
      public class LoginProxy extends Proxy implements IProxy
      {
		public static const NAME:String = 'loginProxy';
        
        public var serviceUrl:String;
                
        public var loginVO:LoginVO;
        
        public function LoginProxy( )
        {
			super( NAME );
        }
        
        public function login( loginVO:LoginVO ):void        
        {
        	var passHash:String = MD5.hash( loginVO.password );
        	var dataXML:XML = <Fudeup><Login><User>{loginVO.username}<Name></Name><Pass>{passHash}</Pass></User></Login></Fudeup>;
        	
        	loginVO.passwordHash = passHash; 
        	this.loginVO = loginVO;
        	var service:URLLoader = new URLLoader();
        	var req:URLRequest = new URLRequest(serviceUrl);
        	req.method = 'POST';
        	req.data = {data:dataXML.toString()};
        	service.addEventListener( Event.COMPLETE, httpResult);
        	service.addEventListener( IOErrorEvent.IO_ERROR, httpFault);
        	service.load(req);
        }
        public virtual function httpResult(e:Event):void
        {   
        	var service:URLLoader = e.target as URLLoader;
        	service.removeEventListener( Event.COMPLETE, httpResult);
        	service.removeEventListener( IOErrorEvent.IO_ERROR, httpFault);
			try {
				var resultXML:XML = XML((e.target as URLLoader).data);
			} catch (e:Error) {
				trace(e.toString());
				sendNotification( ApplicationFacade.SERVICE_ERROR );
				return;
			}
     		if(!resultXML) {
     			sendNotification( ApplicationFacade.SERVICE_ERROR );
     			return;
     		}
        	
        	loginVO.id = Number( resultXML.Login.User.@id );
			loginVO.access = Number( resultXML.Login.User.@access );
			loginVO.token = String( resultXML.Login.User.@token );
			
			if( loginVO.access > 0 ) {
				//---login OK
				sendNotification( ApplicationFacade.LOGIN_SUCCESS );
			} else {
				//---login FAILED
				sendNotification( ApplicationFacade.LOGIN_FAILED );	
			}
        }
   
        public function httpFault (e:Event):void
        {
        	var service:URLLoader = e.target as URLLoader;
        	service.removeEventListener( Event.COMPLETE, httpResult);
        	service.removeEventListener( IOErrorEvent.IO_ERROR, httpFault);
        	trace('Connection Error');
  			sendNotification( ApplicationFacade.SERVICE_ERROR, 'Connection Error' );
        }       
	}
}