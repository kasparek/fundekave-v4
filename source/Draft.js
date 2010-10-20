/** DRAFT - temporary textarea data storing **/
function Draft() {
timer:3000,
li={},
ta:function(id){
	this.id=id;
	this.old=null;
	this.t=0;
	this.text=function(){return $.trim($('#'+this.id).val());};
	this.backup=function(){this.old=this.text;};
	this.restore=function(){if(this.old)$('#'+id).val(this.old);this.old=null;};
	this.check=function(){
		this.backup();
		$(this.id).attr('disabled','disabled');
 		XMLReq.add('result', this.id);
		sendAjax('draft-check');
	};
	this.setT=function(f,t){this.t=setTimeout(f,t);};
	this.clearT=function(){if(this.t)clearTimeout(this.t);this.t=null;};
},
init:function(){
	var o=Draft,l=[],f;
	setListeners('submit','click',o.submit);
	setListeners('draftable', 'keyup',o.key);
	if(window.location.hash=='#dd' || gup('dd',window.location)==1){
		o.dropAll(true);
		window.location.hash='';
	} 
	$('.draftable').each(function(){
		var id=$(this).attr('id');
		if(!o.li[id])o.li[id]=new o.ta(id);
		o.li[id].check();
	});
	$('.draftable').each(function(){f=this.form;l.push($(this).attr('id'));});
	if(l.length>0) {
		$("#draftablesList").remove();
		$(f).append('<input id="draftablesList" type="hidden" name="draftable" value="'+l.join(',')+'" />');
	}
}
,backup:function(id){
	var o=Draft;
	if(!o.li[id])o.li[id]=new o.ta(id);
	o.li[id].backup(); 
},
restore:function(id){
	var o=Draft;
	if(!o.li[id])o.li[id].restore(); 
}
,hasdropAll:false
,dropAll:function(override){
	var o=Draft;
	if(override)o.hasDropAll=true; 
	if(!o.hasDropAll)return;
	o.hasDropAll=false;
	var l=[];
	$('.draftable').each( function() {
		var id=$(this).attr('id');
		$('#draftdrop'+id).remove();
 		l.push(id);
 	});
 	if(l.length>0) {
 		XMLReq.add('result', l.join(','));
		sendAjax('draft-drop');
	}
}
,dropClick:function(e){
	var o=Draft,id=gup('ta',$(e.currentTarget).attr('href'));
	if(o.li[id])o.li[id].restore();
  XMLReq.add('result',id);
	sendAjax('draft-drop');
  $(e.currentTarget).remove();
  return false;
}
,unused:function(id){
	if($('#draftdrop'+id).length>0) {
		XMLReq.add('result',id);
		sendAjax('draft-drop');
		$('#draftdrop'+TAid).remove();
	}
}
,dropBackHandler:function(){
	Draft.hasDropAll=false;
	$('.draftable').each( function(){$('#draftdrop'+$(this).attr('id')).remove();});
}
,submit:function(){
	$.each(Draft.li,function(i,n){n.clearT}); 
}
,save:function(){
	$.each(Draft.li,function(i,n){
		text=n.text;
		if (text != n.old && text.length > 0) {
			XMLReq.add('place',i);
			XMLReq.add('text',text);
			XMLReq.add('call', 'Draft.saveHandler;'+i);
			sendAjax('draft-save');
			n.old=text;
		}
	});
}
,saveHandler:function(id){
	$("#"+id).removeClass('draftNotSave').addClass('draftSave');
}
,key:function(){
	var o=Draft,id=$(this).attr('id'); 
	o.unused(id);
	if (o.li[id].text!=o.li[id].old)$("#"+id).removeClass('draftSave').addClass('draftNotSave');
	o.li[id].clearT();
	o.li[id].setT(o.save,o.timer); 
}
}