package net.fundekave.font
{
	import flash.text.Font;
 
	public class RondaFont extends Font 
	{
		[Embed(source="/assets/pf_ronda_seven.ttf", fontFamily="Ronda")]
		private var _ronda_font:String;
 
		public static const NAME:String = "Ronda";
 
		public function RondaFont() 
		{
			super();
			_ronda_font;
		}
	}
}