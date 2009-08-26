package net.fundekave.fuup.model.vo
{
	public class LoginVO {
		public var username:String;
		public var password:String;
		public var passwordHash:String;
		
		public var id:Number;
		public var access:Number;
		public var token:String;
		
		function LoginVO(username:String, password:String):void {
			this.username = username;
			this.password = password;
		}
		
	}
}