/**
 * CUSTOM AJAX REQUEST BUILDER/HANDLER * send and process ajax request - if
 * problems with %26 use encodeURIComponent
 */
var Fajax = new function(){
	var o = this;
	o.xhrList = {};
	o.top = 0;
	o.formStop = false;
	o.formSent = null;
	o.init = function(){
		if($(".fajaxform").length > 0){
			listen('btn', 'click', Fajax.form);
		}
		if($(".fajaxpager").length > 0)
			listen('fajaxpager', 'click', o.pager);
		listen('fajaxa', 'click', o.a);
	};
	o.pager = function(){
		//TODO: fix hash Hash.set('post-page/p:' + gup('p', this.href) + '/fpost');
		return false;
	};
	o.a = function(e){
		var t = $(e.currentTarget), href = t.attr('href');
		o.top = null;
		if(t.hasClass('confirm')){
			if(!confirm(t.attr("title")))
				return false;
		}
		var k = gup('k', href), id = t.attr("id"), m = gup('m', href);
		
		if(!k)
			k = 0;
		var action = m + '/' + gup('d', href) + '/' + k;
		
		if(id)
			action += '/' + id;
		if(t.hasClass('keepScroll'))
			o.top = $(window).scrollTop();
		if(t.hasClass('progress')){
			var bar = $(".showProgress"), h = bar.height();
			bar.addClass('lbLoading').css('height', (h > 0 ? h : $(window).height()) + 'px');
		}
		o.action(action);
		return false;
	};
	o.action = function(action){
		var l = action.split('/'), m = l[0], d = l[1], k = l[2], id = l[3], res = false, prop = false;
		if(k == 0)
			k = null;
		if(d){
			l = d.split(';');
			while(l.length > 0){
				var row = l.shift().split(':');
				o.add(row[0], row[1]);
				if(row[0] == 'result')
					res = true;
				if(row[0] == 'resultProperty')
					prop = true;
			}
		}
		if(id){
			if(!res)
				o.add('result', id);
			if(!prop)
				o.add('resultProperty', '$html');
		}
		o.send(m, k);
		return false;
	};
	o.form = function(e){
		var t = e.currentTarget, jt = $(t);
		if(jt.hasClass('noFajax'))
			return;
		e.preventDefault();
		if(o.formStop == true){
			o.formStop = false;
			return false;
		}
		if(jt.hasClass('confirm'))
			if(!confirm(jt.attr("title")))
				return false;
		o.formSent = t.form;
		var arr = $(o.formSent).serializeArray(), action, res = false, prop = false;
		while(arr.length > 0){
			var v = arr.shift();
			if(v.name == 'm')
				action = v.value;
			else
				o.add(v.name, v.value);
			if(v.name == 'result')
				res = true;
			if(v.name == 'resultProperty')
				prop = true;
		}
		if(!res)
			o.add('result', $(o.formSent).attr("id"));
		if(!prop)
			o.add('resultProperty', '$html');
		o.add('action', t.name);
		o.add('k', gup('k', o.formSent.action));
		o.send(!action ? gup('m', o.formSent.action) : action, gup('k', o.formSent.action));
		$('.btn').attr("disabled", "disabled");
		return false;
	};
	o.XMLReq = new function(){
		var x = this;
		x.a = [];
		x.s = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
		x.reset = function(){
			o.XMLReq.a = [];
		};
		x.add = function(k, v){
			x.a.push(x.s.replace('{KEY}', k).replace('{DATA}', v));
		};
		x.get = function(){
			var s = '<FXajax><Request>' + x.a.join('') + '</Request></FXajax>';
			x.a = [];
			return s;
		}
	};
	o.add = function(k, v){
		o.XMLReq.add(k, v)
	};
	o.cancel = function(action) {
		Fajax.xhrList[action].abort();
	};
	o.send = function(action, k, silent){
		var data = o.XMLReq.get();
		if(k == 0)
			k = null;
		if(!k)
			k = _fdk.cfg.page;
		if(k == -1)
			k = '';
		$.ajaxSetup({
			scriptCharset : "utf-8",
			contentType : "text/xml; charset=utf-8"
		});
		o.xhrList[action] = $.ajax({
			type : "POST",
			url : "?m=" + action + "-x" + ((k) ? ("&k=" + k) : ('')),
			dataType : 'xml',
			processData : false,
			cache : false,
			data : data,
			complete : function(a, s){
				o.xhrList[action] = null;
				if(o.formSent){
					$('.btn').removeAttr('disabled');
					o.formSent = null;
				}
				if(s != 'success' && s != 'abort'){
					if(!silent)
						msg('danger', _fdk.lng.ajax.error);
					return;
				}
				$(a.responseXML).find("Item").each(function(){
					var item = $(this), c = '', target = item.attr('target'), prop = item.attr('property'), text = item.text();
					switch(target) {
					case 'document':
						window[target][prop]=text;
						break;
					case 'call':
						var l = prop.split('.'),ns = window;
						while(l.length > 0) ns = ns[l.shift()];
						ns.apply(this,text.split(','));
						break;
					default:
						switch(prop) {
						case 'void':
							break;
						default:
							if(prop[0] == '$'){
								$("#" + target)[prop.replace('$', '')](text);
							}else{
								if(prop=='value') $("#" + target).val(text);
								else $("#" + target).attr(prop,text);
							}
						}
					}
				})
			}
		});
	}
};