/** GALERY NEXT WITH PRELOADING */
var ImgNext = new function(){
	var o = this;
	o.r = false;
	o.i = null;
	o.il = null;
	o.p = null;
	o.next = null;
	o.top = 0;
	o.xhr = null;
	o.init = function(){
		if(!o.r){
			o.r = true;
			o.i = $("#detailFoto");
			o.i.bind('load', o.loaded);
			o.p = $(".showProgress");
			$("body").append('<img id="imgNextLoader" class="noscreen" />');
			o.il = $("#imgNextLoader").load(o.preloaded);
		}
	};
	o.click = function(e){
		var m = gup('m', this.href);
		if(Fajax.xhrList[m])
			return false;
		o.top = $(window).scrollTop();
		o.init();
		o.i.show();
		var h = o.p.height();
		o.p.css('height', (h > 0 ? h : $(window).height()) + 'px');
		if(o.next && $(this).attr('id') == 'nextButt'){
			o.i.attr('src', o.next);
			o.next = null;
		}else{
			o.i.css('height', '0px');
		}
		Fajax.a(e);
		return false;
	};
	o.loaded = function(){
		o.init();
		o.i.show();
		imgResizeToFit(o.i, $('#fullscreenBox'));
		Slideshow.next();
		if(o.top)
			$(window).scrollTop(o.top);
	};
	o.xhrHand = function(currentUrl, nextUrl){
		o.init();
		if(currentUrl && currentUrl != o.i.attr('src'))
			o.i.attr('src', currentUrl);
		if(nextUrl)
			o.il.attr('src', nextUrl);
	};
	o.preloaded = function(e){
		o.next = o.il.attr('src');
	}
};
/** SLIDESHOW */
var Slideshow = new function(){
	var o = this;
	o.on = false;
	o.t = 0;
	o.s = 5;
	o.f = function(){
		if(o.on)
			$("#nextButt").click();
	};
	o.toggle = function(){
		o.on = !o.on;
		o.next();
	};
	o.next = function(){
		clearTimeout(o.t);
		if(o.on)
			o.t = setTimeout(o.f, o.s * 1000);
	};
};
/** FULLSCREEN */
var Fullscreen = new function(){
	var o = this;
	o.el = null;
	o.tool = null;
	o.state = null;
	o.isFullscreen = false;
	o.d = $(document.documentElement);
	o.w = $(window);
	o.key = function(e){
		if(e.keyCode == 27)
			o.toggle();
		if(e.keyCode == 32)
			$("#nextButt").click();
	}
	o.getHeight = function(){
		var height = o.el.width() * 0.6, heightMax = Math.round($(window).height() - 150 - o.el.offset().top);
		return height > heightMax ? heightMax : height;
	};
	o.init = function(){
		o.el = $('#fullscreenBox');
		if(o.el.length==0)return;
		o.el.css('height', o.getHeight(o.el) + 'px');
		imgResizeToFit($("#detailFoto"), o.el);
		listen('galeryFullSwitch', 'click', o.toggle);
		$("#fullscreenLeave").click(o.toggle);
		$("#fullscreenPrevious").click(function(){
			$("#prevButt").click();
			return false;
		});
		$("#fullscreenNext").click(function(){
			$("#nextButt").click();
			return false;
		});
		o.tool = $("#fullscreenToolbar");
		o.tool.hover(function(){
			$(this).fadeTo("slow", 1.0);
		}, function(){
			$(this).fadeTo("slow", 0.2);
		});
		var fs = $("#fullscreenSlideshow");
		if(Slideshow.on)
			fs.addClass('fullscreenSlideshowOn');
		else
			fs.removeClass('fullscreenSlideshowOn');
		fs.click(function(){
			$(this).toggleClass('fullscreenSlideshowOn');
			Slideshow.toggle();
			return false;
		});
		NativeFullscreen.init(o.onEnter, o.onExit, o.el[0]);
	};
	o.toggle = function(){
		if(!o.isFullscreen)
			NativeFullscreen.enter();
		else
			NativeFullscreen.exit();
		return false;
	};
	o.onEnter = function(){
		o.isFullscreen = true;
		o.state = {
			el : o.el,
			height : o.el.height(),
			parent : o.el.parent(),
			index : o.el.parent().children().index(o.el),
			x : o.w.scrollLeft(),
			y : o.w.scrollTop()
		};
		o.d.bind('keyup', o.key);
		o.w.bind('resize', ImgNext.loaded).resize();
		o.el.addClass('fullscreen');
		if(!NativeFullscreen.support()){
			o.w.scrollTop(0).scrollLeft(0);
			$('body').append(o.el).css('overflow', 'hidden');
		}
		o.tool.removeClass('hidden').delay(100).fadeTo("fast", 1).fadeTo("slow", 0.3);
		$("#fullscreenHint").removeClass('hidden').css('top', ((o.w.height() - $("#fullscreenHint").height()) / 2)).show().delay(1000).fadeOut('slow');
		o.el.css('height', '100%').css('width', '100%');
		imgResizeToFit($("#detailFoto"), o.el);
	};
	o.onExit = function(){
		o.isFullscreen = false;
		Slideshow.on = false;
		o.w.unbind('resize', ImgNext.loaded);
		o.d.unbind('keyup', o.key);
		o.el.removeClass('fullscreen');
		if(!NativeFullscreen.support()){
			$('body').css('overflow', 'auto');
			if(o.state.index >= o.state.parent.children().length)
				o.state.parent.append(o.el);
			else
				o.el.insertBefore(o.state.parent.children().get(o.state.index));
			o.w.scrollTop(o.state.x).scrollLeft(o.state.y);
		}
		o.el.css('height', o.state.height + 'px');
		o.tool.addClass('hidden');
		o.state = null;
		imgResizeToFit($("#detailFoto"), o.el);
	};
};
var NativeFullscreen = new function(){
	var doc = window.document, IFRAME = window.parent !== window.self, F = function(){
	}, o = this;
	o.el = null;
	o.enterCallback = F;
	o.exitCallback = F;
	o.isFullscreen = false;
	o.isListening = false;
	o.support = function(){
		var html = doc.documentElement;
		return !IFRAME && (html.requestFullscreen || html.mozRequestFullScreen || html.webkitRequestFullScreen);
	};
	o.enter = function(){
		if(!this.support()){
			o.enterCallback.call();
			return;
		}
		if(o.el.requestFullscreen){
			o.el.requestFullscreen();
		}else if(o.el.mozRequestFullScreen){
			o.el.mozRequestFullScreen();
		}else if(o.el.webkitRequestFullScreen){
			o.el.webkitRequestFullScreen();
		}
	};
	o.exit = function(){
		if(!o.support()){
			o.exitCallback.call();
			return;
		}
		if(doc.exitFullscreen){
			doc.exitFullscreen();
		}else if(doc.mozCancelFullScreen){
			doc.mozCancelFullScreen();
		}else if(doc.webkitCancelFullScreen){
			doc.webkitCancelFullScreen();
		}
	};
	o.handler = function(){
		if(!o.support())
			return;
		if(doc.fullscreen || doc.mozFullScreen || doc.webkitIsFullScreen){
			o.enterCallback.call();
		}else{
			o.exitCallback.call();
		}
	};
	o.init = function(enterCallback, exitCallback, el){
		o.el = el || doc.documentElement;
		o.enterCallback = enterCallback || F;
		o.exitCallback = exitCallback || F;
		if(!o.support())
			return;
		if(o.isListening)
			return;
		doc.addEventListener('fullscreenchange', o.handler, false);
		doc.addEventListener('mozfullscreenchange', o.handler, false);
		doc.addEventListener('webkitfullscreenchange', o.handler, false);
		o.isListening = true;
	};
}
/** IMAGE RESIZE TO FIT */
function imgResizeToFit(img, fitTo){
	img.removeAttr('height').removeAttr('width').css('width', '').css('height', '');
	var iw = img.width(), ih = img.height(), hw = fitTo.width(), hh = fitTo.height(), r = Math.min(hw / iw, hh / ih);
	img.css('height', Math.round(ih * r)).css('width', Math.round(iw * r)).css('margin-top', (hh - img.height()) / 2);
}