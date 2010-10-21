var Fajax={
xhrList:{}
,top:0
,formStop=false
,formSent=null

,init:function(){
if($(".fajaxform").length>0)listen('button','click',Fajax.form);
listen('fajaxa','click',Fajax.a);
}

,pager:function(){
	Hash.set('post-page/p:'+gup('p',this.href)+'/fpost');
	return false;
}

,a:function(e) {
	var o=Fajax;
	o.top=null
	if($(e.currentTarget).hasClass('confirm')){if(!confirm($(e.currentTarget).attr("title")))return false;}
	var k=gup('k',this.href),id=$(this).attr("id"),m=gup('m',this.href);
	if(!k)k=0;
	var action=m+'/'+gup('d',this.href)+'/'+k;
	if(id)action+='/'+id; 
	if($(this).hasClass('keepScroll'))o.top=$(window).scrollTop();
	if($(this).hasClass('progress')){var bar=$(".showProgress"),h=bar.height();bar.addClass('lbLoading').css('height',(h>0?h:$(window).height())+'px').children().hide();}
	if($(this).hasClass('hash')){Hash.set(action);return false;}
	o.action(action);
	return false;
}

,action:function(action){//action = m/d/k|0/linkElId
	actionList=action.split('/');
	var m=actionList[0],d=actionList[1],k=actionList[2],id=actionList[3],d=false,dp=false;
	if(k==0) k=null;
	if(d){
		var arr = d.split(';');
		while (arr.length > 0) {
			var rowStr = arr.shift();
			var row = rowStr.split(':');
			Fajax.add(row[0], row[1]);
			if(row[0]=='result')d=true;
			if(row[0]=='resultProperty')dp=true;
		}
	}
	if(id){
		if(!d)Fajax.add('result',id);
		if(!dp)Fajax.add('resultProperty','$html');
	}
	Fajax.send(m,k);
	return false;
}

,form:function(e) {
	var o=Fajax;
	e.preventDefault();
	if (o.formStop==true){o.formStop=false;return false;}
	if($(e.currentTarget).hasClass('confirm'))if(!confirm($(e.currentTarget).attr("title")))return false;
	if($(e.currentTarget).hasClass('draftdrop'))Draft.hasDropAll=true;
	$('.errormsg').hide('slow',function(){$(this).html('');});
	$('.okmsg').hide('slow',function(){$(this).html('');});
	o.formSent=e.currentTarget.form;
	$('.button',o.formSent).attr('disabled',true);
	var arr=$(o.formSent).formToArray(false),action,d=false,dp=false;
	while(arr.length>0) {
		var obj=arr.shift();
		if(obj.name=='m')action=obj.value;
		else o.add(obj.name,obj.value);
		if (obj.name=='result')d=true;
		if (obj.name=='resultProperty')dp=true;
	}
	if(!d)o.add('result',$(o.formSent).attr("id"));
	if(!dp)o.add('resultProperty','$html');
	o.add('action',e.currentTarget.name);
	o.add('k',gup('k',o.formSent.action));
	o.send(!action?gup('m',o.formSent.action):action,gup('k',o.formSent.action));
	return false;
}

,XMLReq:{a:[],s:'<Item name="{KEY}"><![CDATA[{DATA}]]></Item>',reset:function(){XMLReq.a=[];},add:function(k,v){XMLReq.a.push(XMLReq.s.replace('{KEY}',k).replace('{DATA}',v));},get:function(){var s='<FXajax><Request>'+XMLReq.a.join('')+'</Request></FXajax>';XMLReq.a=[];return s;}}
,add:function(k,v){Fajax.Fajax.add(k,v)}
,send:function(action,k){var data=Fajax.XMLReq.get();if(k==0)k=null;if(!k)k=gup('k',document.location);if(k==-1)k='';$.ajaxSetup({scriptCharset:"utf-8",contentType:"text/xml; charset=utf-8"});Fajax.xhrList[action]=$.ajax({type:"POST",url:"index.php?m="+action+"-x"+((k)?("&k="+k):('')),dataType:'xml',processData:false,cache:false,data:data,complete:function(ajaxRequest,textStatus){Fajax.xhrList[action]=null;$(ajaxRequest.responseXML).find("Item").each(function() {var item = $(this),command = '',target=item.attr('target'),property = item.attr('property'),text=item.text();switch (target) {case 'document': command =  target + '.' + property + ' = "'+text+'"'; break;case 'call':command = property + "("+(text.length>0 ? "'" + text.split(',').join("','") + "'" : "")+");"; break;default: var arr=text.split(';'),part0=arr[0],callback=(arr[1]?arr[1]:null);switch (property) {case 'void': break;case 'css':case 'getScript':Lazy.load([part0],callback);break;case 'body': $("body").append(part0); break;default: if(property[0]=='$') {command = '$("#' + target + '").' + property.replace('$','') + '( text );'} else {command = '$("#' + target + '").attr("' + property + '", text);';}};};if(command.length>0){eval(command);}if(Fajax.formSent){$('.button',Fajax.formSent).removeAttr('disabled');Fajax.formSent=null;Draft.dropAll();}})}});}
};