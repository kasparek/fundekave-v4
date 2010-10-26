var markitupSettings={	
	onShiftEnter:  	{keepDefault:false, replaceWith:'<br />\n'},
	onCtrlEnter:  	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>'},
	onTab:    		{keepDefault:false, replaceWith:'    '},
	markupSet:  [
		{name:'Heading', key:'H', openWith:'(!(<h2>|!|<h1>)!)', closeWith:'(!(</h2>|!|</h1>)!)' }, 	
		{name:'Bold', key:'B', openWith:'(!(<strong>|!|<b>)!)', closeWith:'(!(</strong>|!|</b>)!)' },
		{name:'Italic', key:'I', openWith:'(!(<em>|!|<i>)!)', closeWith:'(!(</em>|!|</i>)!)'  },
		{name:'Stroke through', key:'S', openWith:'<del>', closeWith:'</del>' },
		{name:'Align left', openWith:'<div class="leftbox">', closeWith:'</div>' },
		{name:'Align center', openWith:'<div class="centerbox">', closeWith:'</div>' },
		{name:'Align right', openWith:'<div class="rightbox">', closeWith:'</div>' },
		{separator:'---------------' },
		{name:'Picture', key:'P', openWith:'<img src="', closeWith:'" />' },
		{name:'Link', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', closeWith:'</a>', placeHolder:'Your text to link...' }
	]
}