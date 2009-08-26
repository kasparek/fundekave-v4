package net.fundekave.fuup.view
{
	import flash.events.Event;
	
	import net.fundekave.fuup.ApplicationFacade;
	import net.fundekave.fuup.model.vo.LoginVO;
	import net.fundekave.fuup.view.components.LoginView;
	
	import org.puremvc.as3.multicore.interfaces.IMediator;
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;

	public class LoginViewMediator extends Mediator implements IMediator
	{
		public static const NAME:String = 'LoginViewMediator';
		
		public function LoginViewMediator( viewComponent:Object )
		{
			super( NAME, viewComponent );
		}
		
		override public function onRegister():void
		{
			loginView.addEventListener( LoginView.SUBMIT, handleSubmit );
		}
		
		override public function listNotificationInterests():Array
		{
			return [
				ApplicationFacade.LOGIN_SUCCESS
			];
		}
       
       	override public function handleNotification(note:INotification):void
       	{
       		switch (note.getName())
       		{
       			case ApplicationFacade.LOGIN_SUCCESS:
       				reset();
       			break;
       		}
       	}
		
		private function reset():void
		{
			loginView.loginUsername = '';
			loginView.loginPassword = '';
		}
		
		protected function handleSubmit( e:Event ):void
		{
			sendNotification( ApplicationFacade.LOGIN, new LoginVO(loginView.loginUsername, loginView.loginPassword) );
		}
		
		protected function get loginView():LoginView
		{
			return viewComponent as LoginView;
		}		
	}
}