/** INITIALIZATION ON DOM */
function boot(){
	//load language
	var defaultLang = 'cs';
	Lazy.load([_fdk.cfg.jsUrl+'i18n/_fdk.lng.'+(_fdk.cfg.lang ? _fdk.cfg.lang : defaultLang)+'.js'],boot);
	
	gaLoad();
	buttonInit();
	if($("#errormsgJS").is(':empty'))
		$("#errormsgJS").hide(0);
	if($("#okmsgJS").is(':empty'))
		$("#okmsgJS").hide(0);
	$("#errormsgJS").css('padding', '1em');
	$("#okmsgJS").css('padding', '1em');
	var w = $(window).width();
	if($("#sidebar").length == 0)
		$('body').addClass('bodySidebarOff');
	$(".expand").autogrow();
	$(".opacity").bind('mouseenter', function(){
		$(this).fadeTo("fast", 1);
	}).bind('mouseleave', function(){
		$(this).fadeTo("fast", 0.2);
	});
	fajaxInit();
	fconfirmInit();
	switchOpen();
	Resize.init();

	$(".mapThumbLink").bind('click', gooMapiThumbClick);
	if($(".geoInput").length > 0 || $(".mapLarge").length > 0)
		gooMapiInit();

	if($(".hash").length > 0)
		Hash.init();
	slimboxInit();
	Fullscreen.init();
	tabsInit();
	fuupInit();
	datePickerInit();
	if(parseInt(_fdk.cfg.user) > 0){
		Richta.map();
		$("#recipient").change(avatarfrominput);
		$('#ppinput').hide();
		$("#saction").change(function(evt){
			if($("#saction option:selected").attr('value') == 'setpp')
				$('#ppinput').show();
			else
				$('#ppinput').hide();
		});
		$("#recipientList").change(function(evt){
			var str = "", combo = $("#recipientList");
			if(combo.attr("selectedIndex") > 0)
				$("#recipientList option:selected").each(function(){
					str += $(this).text() + " ";
				});
			$("#recipient").attr("value", str);
			combo.attr("selectedIndex", 0);
			avatarfrominput();
		});
		galeryEditInit();
		if(parseInt(_fdk.cfg.msgTi) > 0)
			Msg.check();
		var perm = $("#accessSel");
		if(perm.length > 0)
			perm.change(function(){
				var v = $(this).val();
				if(v == 0)
					$("#rule1").show();
				else
					$("#rule1").hide();
			}).change();
	}
};

function recaptchaStart(){
	if(!Lazy.load([
		'http://www.google.com/recaptcha/api/js/recaptcha_ajax.js'
	], recaptchaStart))
		return;
	Recaptcha.create("6LexXNkSAAAAAE_BDWQHhapdx-XPHItdWgBvDTSm", 'recaptchaBox', {
		tabindex : 3
	});
}

/** INIT jQuery UI and everything possibly needed for ajax forms and items */
function jUIInit(){
	if(!Lazy.load(_fdk.load.ui, jUIInit))
		return;
	buttonInit();
	tabsInit();
	datePickerInit();
	Richta.map();
	fajaxInit();
	fconfirmInit();
	gooMapiInit();
	fuupInit();
	slimboxInit();
	$(".expand").autogrow();
};

function gooMapiInit(){
	if(!Lazy.load(_fdk.load.goomapi, gooMapiInit))
		return;
	GooMapi.init(_fdk.cfg,_fdk.lng.goomapi);
};

var gooMapiThumbClickElement = null;
function gooMapiThumbClick(){
	$(this).unbind('click', gooMapiThumbClick);
	if(!gooMapiThumbClickElement)
		gooMapiThumbClickElement = this;
	if(!Lazy.load(_fdk.load.goomapi, gooMapiInit))
		return;
	$(gooMapiThumbClickElement).click();
	gooMapiThumbClickElement = null;
}

function galeryEditInit(){
	if(!Lazy.load(_fdk.load.galeryedit, galeryEditInit))
		return;
	GaleryEdit.init(_fdk.cfg,_fdk.lng.galery);
};

function juilater(){
	$(".expand").change();
}
function datePickerInit(){
	if($(".datepicker").length > 0){
		if(!Lazy.load(_fdk.load.ui, datePickerInit))
			return;
		$.datepicker.setDefaults($.extend({
			showMonthAfterYear : false
		}, $.datepicker.regional['cs']));
		$(".datepicker").datepicker();
	}
};
function slimboxInit(){
	if(!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)){
		if($("a[rel^='lightbox']").length > 0){
			if(!Lazy.load(_fdk.load.slim, slimboxInit))
				return;
			$("a[rel^='lightbox']").slimbox({
				overlayFadeDuration : 100,
				resizeDuration : 100,
				imageFadeDuration : 100,
				captionAnimationDuration : 100
			}, null, function(el){
				return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));
			});
		}
	}
};
function fuupInit(){
	if($(".fuup").length > 0){
		if(!Lazy.load(_fdk.load.swf, fuupInit))
			return;
		$(".fuup").each(function(i){
			var id = $(this).attr('id'), fuupConf = $.base64.encode(JSON.stringify(_fdk.fuup[id]));
			swfobject.embedSWF(_fdk.cfg.assUrl + "load.swf", id, "100", "25", "10.0.12", _fdk.cfg.assUrl + "expressInstall.swf", {
				file : _fdk.cfg.assUrl + "Fuup.swf",
				config : fuupConf,
				containerId : id
			}, {
				menu : "false",
				scale : "noScale",
				allowFullscreen : "true",
				allowScriptAccess : "always",
				bgcolor : "",
				wmode : "transparent"
			});
		});
		$("#uploadTip").removeClass('hidden');
	}
}
function tabsInit(){
	if($("#tabs").length > 0){
		if(!Lazy.load(_fdk.load.ui, tabsInit))
			return;
		$("#tabs").tabs({
			select : function(event, ui){
				window.location.hash = '';
			}
		});
		juilater();
	}
};
function buttonInit(){
	if($('.uibutton').length > 0){
		if(!Lazy.load(_fdk.load.ui, buttonInit))
			return;
		$('.uibutton').button();
	}
}
/** request init */
function friendRequestInit(text){
	$('#friendrequest').remove();
	$("#menu-secondary-holder").after(text);
	$('#friendrequest').removeClass('hidden').show('slow');
	fajaxInit();
	$('#cancel-request').unbind('click', Fajax.form).bind('click', function(event){
		remove('friendrequest');
		event.preventDefault();
		return false;
	});
};
/** ajax link init */
function fajaxInit(){
	Fajax.init();
	listen('galerynext', 'click', ImgNext.click);
};
function fconfirmInit(event){
	$('.confirm').each(function(){
		var pf = false;
		if(this.form)
			pf = $(this.form).hasClass('fajaxform');
		if(!$(this).hasClass('fajaxa') && !pf){
			$(this).bind('click', onConfirm);
		}
	});
};
function onConfirm(e){
	if(!confirm($(e.currentTarget).attr("title"))){
		preventAjax = true;
		e.preventDefault();
	}
};
/** simple functions */
function shiftTo(y){
	if(!y)
		y = 0;
	$(window).scrollTop(y);
}
function enable(id){
	$('#' + id).removeAttr('disabled');
};
function remove(id){
	$('#' + id).remove();
};
function switchOpen(){
	$('.switchOpen').click(function(){
		$('#' + this.rel).toggleClass('hidden');
		return false;
	});
};
function listen(c, e, f){
	$("." + c).unbind(e, f).bind(e, f);
};
function gup(n, url){
	n = n.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
	var r = new RegExp("[\\?&|]" + n + "=([^&#|]*)"), res = r.exec(url);
	return res === null ? 0 : res[1];
};

var msgOkTime = 0, msgErrorTime = 0;
function msg(type, text){
	if(type == 'ok'){
		clearTimeout(msgOkTime);
		msgOkTime = setTimeout(function(){
			$("#okmsgJS").hide('slow')
		}, 5000);
	}else{
		clearTimeout(msgErrorTime);
		msgErrorTime = setTimeout(function(){
			$("#errormsgJS").hide('slow')
		}, 10000);
	}
	$("#" + type + "msgJS").hide(0).html(text).show();
};

function redirect(dir){
	window.location.replace(dir);
};

/** AVATAR FROM input IN fpost */
function avatarfrominput(evt){
	Fajax.add('username', $("#recipient").attr("value"));
	Fajax.add('call', 'fajaxInit');
	Fajax.send('post-avatarfrominput', '');
}

/** IMAGE UPLOADING TOOL HANDLERS - FUUP */
function fuupUploadComplete(k, v){
	if(k == 'error')
		alert(_fdk.lng.galery[v]);
	if(k != 'complete')
		return;
	var i = $('#i').attr('value');
	if(i > 0)
		Fajax.add('i', i);
	Fajax.add('call', 'jUIInit');
	Fajax.send('item-image', _fdk.cfg.page);
}

function tempStoreDeleteInit(){
	$("#tempStoreButt").click(function(e){
		$("#imageHolder").html('');
		Fajax.send('item-tempStoreFlush', _fdk.cfg.page);
		e.preventDefault();
		return false;
	});
}

/** RESIZE HANDLER-CLIENT INFO TO SERVER */
var Resize = new function(){
	var o = this;
	o.t = 0;
	o.init = function(){
		$(window).resize(o.on).resize();
	};
	o.on = function(){
		clearTimeout(o.t);
		o.t = setTimeout(o.send, 500);
	};
	o.send = function(){
		var w = $(window), ww = w.width(), wh = w.height(), cw = parseInt(_fdk.cfg.cw) * 1, ch = parseInt(_fdk.cfg.ch) * 1;
		if(w != cw || h != ch){
			Fajax.add('size', ww + 'x' + wh);
			Fajax.send('user-clientInfo', -1, true);
		}
	}
};

/** MARKITUP SETUP - rich textarea */
var Richta = new function(){
	var o = this;
	o.w = null;
	o.init = function(ta){
		if(ta)
			o.w = ta;
		if(!Lazy.load(_fdk.load.richta, o.init))
			return;
		if(!o.w)
			o.w = $('.markitup');
		o.w.markItUp(markitupSettings);
		o.w = null;
	};
	o.map = function(){
		$('.textAreaResize').remove();
		$('.markitup').each(function(){
			$(this).before('<span class="textAreaResize"><a href="?textid=' + $(this).attr('id') + '" class="toggleToolSize"></a></span>');
		});
		listen('toggleToolSize', 'click', o.click);
	};
	o.click = function(e){
		var id = gup("textid", e.target.href), ta = $("#" + id);
		if(ta.hasClass('markItUpEditor'))
			ta.markItUpRemove();
		else if(!o.w)
			o.init(ta);
		$("#" + id).autogrow();
		return false;
	}
};

/** MSG CHAT FUNCTIONS */
var Msg = new function(){
	var o = this;
	o.t = 0;
	o.check = function(){
		var p = Hash.data('p'), l = [];
		$(".hentry.unread.sent").each(function(){
			l.push($(this).attr('id').replace('mess', ''));
		});
		if(l.length > 0)
			Fajax.add('unreadedSent', l.join(','));
		if(p)
			Fajax.add('p', p);
		Fajax.send('post-hasNewMessage', _fdk.cfg.page == 'fpost' ? 'fpost' : -1, true);
	};
	o.sentReaded = function(p){
		var l = p.split(',');
		for( var i in l){
			$("#mess" + l[i]).removeClass('unread');
			$("#unreadedLabel" + l[i]).remove();
		}
	};
	o.checkHandler = function(num, name){
		var d = $("#messageNew"), p = parseInt(_fdk.cfg.msgTi);
		if(num > 0){
			d.removeClass('hidden');
			$("#numMsg").text(num);
			$("#recentSender").text(name);
		}else if(!d.hasClass('hidden'))
			d.addClass('hidden');
		if(p > 0){
			clearTimeout(o.t);
			o.t = setTimeout(o.check, p);
		}
	}
};

/* autogrow */
(function($){
	$.fn.autogrow = function(options){
		this.filter('textarea').each(function(){
			var $this = $(this), minHeight = $this.height(), u = function(){
				var h = $this.height(), sh = $this.attr('scrollHeight');
				if(sh > h + 5)
					$this.css('height', sh + 60);
			};
			$this.unbind('change', u).unbind('keydown', u).change(u).keydown(u).change();
		});
		return this;
	}
})(jQuery);

function indexOf(arr, obj, start){
	for( var i = (start || 0); i < arr.length; i++)
		if(arr[i] == obj)
			return i;
	return -1;
}

/* lazy google analytics load */
function gaLoad(){
	var ga = document.createElement('script');
	ga.type = 'text/javascript';
	ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(ga, s);
}