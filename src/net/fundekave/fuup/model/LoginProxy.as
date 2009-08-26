package net.fundekave.fuup.model
{    

      import com.adobe.crypto.MD5;
      
      import mx.controls.Alert;
      import mx.rpc.events.FaultEvent;
      import mx.rpc.events.ResultEvent;
      import mx.rpc.http.HTTPService;
      
      import net.fundekave.fuup.ApplicationFacade;
      import net.fundekave.fuup.model.vo.*;
      
      import org.puremvc.as3.multicore.interfaces.IProxy;
      import org.puremvc.as3.multicore.patterns.proxy.Proxy;  
        
      public class LoginProxy extends Proxy implements IProxy
      {
		public static const NAME:String = 'loginProxy';
        
        public var serviceUrl:String;
        
        private var httpService:HTTPService;
        
        public var loginVO:LoginVO;
        
        public function LoginProxy( )
        {
			super( NAME );
			httpService  = new HTTPService();
			httpService.method = "POST";
			httpService.addEventListener(ResultEvent.RESULT, httpResult);
            httpService.addEventListener(FaultEvent.FAULT, httpFault);
      
        }
        
        public function login( loginVO:LoginVO ):void        
        {
        	var passHash:String = MD5.hash( loginVO.password );
        	var dataXML:XML = <Fudeup><Login><User>{loginVO.username}<Name></Name><Pass>{passHash}</Pass></User></Login></Fudeup>;
        	
        	loginVO.passwordHash = passHash; 
        	this.loginVO = loginVO;
        	httpService.resultFormat = HTTPService.RESULT_FORMAT_E4X;
        	httpService.url = serviceUrl;
			httpService.send( {data:dataXML.toString()} );
			        
        }
        public virtual function httpResult(event:ResultEvent):void
        {   
			try {
				var resultXML:XML = XML(event.result);
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
   
        public function httpFault (event:FaultEvent):void
        {
        	Alert.show('Connection Error','Error');
  			sendNotification( ApplicationFacade.SERVICE_ERROR, event.fault.faultString );
        }       
	}
}