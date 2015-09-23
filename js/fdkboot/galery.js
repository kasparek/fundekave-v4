/** GALERY NEXT WITH PRELOADING */
/** RESIZE HANDLER */
(function(o){
	o.t = 0;
	o.cw = 0;
	o.ch = 0;
	o.currentIndex = 0;
	o.numCell = 0;
	o.buttonTriggerScroll=false;
	o.timeout = null;
	o.unveilInit = function() {
		$(".gallery-cell-image").unveil('lazy',$(window).height(),true);
	};
	o.getItemId = function(){
		return $($("div.gallery-cell")[o.currentIndex]).data('itemid');
	};
	o.init = function(){
		if(galleryComments) {
			Comments.init(galleryComments);
			Comments.update(o.getItemId());
		}
		if(o.numCell===0) o.numCell = $("div.gallery-cell").length;
		$(window).on('resize',o.on).trigger('resize');
		$('.gallery img').load(function(){
			imgResizeToFit($(this), $('#fullscreenBox').width(), $(window).height(), Fullscreen.isFullscreen);
		}).error(function(){
			var stamp = new Date().getTime();
			var url = $(this).attr('src')+'?'+stamp;
			$(this).attr('src',url);
			console.log('Error Loading Image '+url);
		});
		console.log('galery init ismobil = ' +isMobile);
		if(isMobile) {
			$("#galleryNext").remove();
			$("#galleryPrev").remove();
			$("#aSlideshow").remove();
			$(".aFullscreen").remove();
		}
		if($('img.gallery-cell-image').length<2) {
			$("#galleryNext").remove();
			$("#galleryPrev").remove();
			$(".aFullscreen").remove();
			$("#aSlideshow").remove();
			return;
		}
		var keyupHandler = function(e){
			var i;
			switch(e.keyCode) {
				case 39: //right arrow
				case 40: //down arrow
				case 34: //page down
				case 32: //spacebar 
					i = o.currentIndex+1;
				break;
				case 33: //page up
				case 38: //up arrow
				case 37: //left arrow
					i = o.currentIndex-1;
				break;
				default:
				return false;
			}
			clearTimeout(o.timeout);
			o.buttonTriggerScroll = true;
			console.log('Resize:keyupHandler: index before '+o.currentIndex+' i='+i);
			if(i>=o.numCell) i = Fullscreen.isFullscreen? 0 : o.numCell-1;
			else if(i < 0) i = Fullscreen.isFullscreen ? o.numCell-1 : 0;
		    if(i != o.currentIndex) {
		    	o.currentIndex = i;
		    	console.log('Resize:keyupHandler: result '+o.currentIndex);
		    	Resize.handleNextPrev(null,true);
		    }
		};
		$(window).on('keyup',keyupHandler).on('scroll',function(){
			clearTimeout(o.timeout);
		    o.timeout = setTimeout(function() {
		    	if(o.buttonTriggerScroll) {
		    		o.buttonTriggerScroll=false;
		    		return;
		    	}
		    	if(Fullscreen.isFullscreen) return;
		    	var index=0,onScreenMax=0,newIndex=0,$newImg=null;
		        $(".gallery-cell").each(function(){
		        	var $i = $(this), $w = $(window), wt = $w.scrollTop(), wb = wt + $w.height(), et = $i.offset().top, eb = et + $i.height();
					if(eb >= wt && et <= wb) {
						var onScreen = $i.height() - (et<wt ? wt-et : 0) - (eb>wb ? eb-wb : 0);
						if(onScreen >= onScreenMax) {
							onScreenMax = onScreen;
							newIndex = index;
							$newImg = $i;
						}
					}
					index++;
		        });
		        if(newIndex != o.currentIndex) {
		        	o.currentIndex = newIndex;
		        	var itemId = $newImg.data('itemid');
					History.pushState({action:'item-show',eid:itemId,i:itemId}, "Loading ...", "?i="+itemId);
					Comments.update(itemId);
					o.timeout = setTimeout(function(){o.buttonTriggerScroll=true;Resize.handleNextPrev(null,true);},500);
		        }
		    }, 500);
		});
		$("#galleryNext").on("click",o.handleNextPrev);
		$("#galleryPrev").on("click",o.handleNextPrev);

		var firstImg = $('img.gallery-cell-image:first')[0];
		if(firstImg.complete) {
			o.unveilInit();
		} else {
			$(firstImg).on('load',function(){o.unveilInit();});
		}
	};
	o.handleNextPrev = function(e,isAnimate){
		var next = true;
		if(e) {
			if('galleryNext' == $(this).attr('id')) {
				o.currentIndex++;
			} else {
				o.currentIndex--;
				next = false;
			}
		} else {
			next = false;
		}
		if(o.currentIndex>=o.numCell) o.currentIndex = 0;
		else if(o.currentIndex < 0) o.currentIndex = o.numCell-1;
		var i = $($('div.gallery-cell')[o.currentIndex]);
		if(Fullscreen.isFullscreen) {
			if(e || isAnimate) $('.gallery').animate({top:(-i.position().top)},600);
			else $('.gallery').css('top',(-i.position().top)+'px');
		} else {
			if(e) o.buttonTriggerScroll = true;
			if(e || isAnimate) {
				$("html, body").animate({ scrollTop: i.offset().top - (($(window).height() - i.height()) / 2) }, 600);
			} else $("html, body").scrollTop( i.offset().top - (($(window).height() - i.height()) / 2));
		}
		if(e) {
			$(window).trigger('scroll');
			var itemId = i.data('itemid');
			History.pushState({action:'item-show',eid:itemId,i:itemId}, "Loading ...", "?i="+itemId);
			Comments.update(itemId);
		}
	};
	o.force = function () {
		o.cw = o.ch = 0;
		o.on();
	};
	o.on = function(){
		var w = $(window), ww = w.width(), wh = w.height();
		if(ww != o.cw || wh != o.ch) {
			o.cw = ww;
			o.ch = wh;
			$('.gallery img').each(function(index) {
				var lazy = $(this).data('lazy');
				if(!lazy) {
					imgResizeToFit($(this), $('#fullscreenBox').width(), $(window).height(), Fullscreen.isFullscreen);
				}
			});
		}
	};
}(window.Resize = {}));
/** SLIDESHOW */
(function(o){
	o.on = false;
	o.t = 0;
	o.s = 5;
	o.f = function(){
		if(o.on) {
			$("#galleryNext").click();
			o.next();
		}
	};
	o.toggle = function(){
		o.on = !o.on;
		o.next();
	};
	o.next = function(){
		clearTimeout(o.t);
		if(o.on) o.t = setTimeout(o.f, o.s * 1000);
	};
}(window.Slideshow = {}));
/** FULLSCREEN */
(function(o){
	o.el = null;
	o.state = null;
	o.isFullscreen = false;
	o.d = $(document.documentElement);
	o.w = $(window);
	o.key = function(e){
		if(e.keyCode == 27) //esc to exit fullscreen
			o.toggle();
	};
	o.init = function(){
		o.el = $('#fullscreenBox');
		if(o.el.length===0)return;
		listen('aFullscreen', 'click', o.toggle);
		$("#fullscreenLeave").click(o.toggle);
		$("#aSlideshow").click(function(){
			if(Slideshow.on) $("#aSlideshow span").addClass('glyphicon-play').removeClass('glyphicon-stop');
			else $("#aSlideshow span").addClass('glyphicon-stop').removeClass('glyphicon-play');
			Slideshow.toggle();
			return false;
		});
		Resize.init();
	};
	o.toggle = function(){
		if(!o.isFullscreen)
			o.onEnter();
		else
			o.onExit();
		return false;
	};
	o.onEnter = function() {
if(screenfull.enabled){screenfull.request();}
		o.isFullscreen = true;
		o.state = {
			y : o.w.scrollTop()
		};
		o.d.bind('keyup', o.key);
		o.el.addClass('fullscreen');
		o.w.scrollTop(0).scrollLeft(0);
		$('body').css('overflow', 'hidden');
		Resize.force();
		$('.gallery').css('position','absolute').css('width','100%');
		Resize.handleNextPrev();
	};
	o.onExit = function(){
		o.isFullscreen = false;
		o.d.unbind('keyup', o.key);
		o.el.removeClass('fullscreen');
		$('body').css('overflow', 'auto');
		if(o.state.y>0) o.w.scrollTop(o.state.y);
		o.state = null;
		Resize.force();
		$('.gallery').css('position','inherit');
		Resize.handleNextPrev();
	};
}(window.Fullscreen = {}));

/** IMAGE RESIZE TO FIT */
function imgResizeToFit(img, hw, hh, isFullscreen,upScale){
	img.removeAttr('height').removeAttr('width').css('width', 'auto').css('height', 'auto').css('top','').css('left','').css('position','').css('margin-top','');
	var iw = img.width(), ih = img.height(), r = Math.min(hw / iw, hh / ih),tw = Math.round(r*iw),th = Math.round(r*ih);
	if(!upScale && (tw>iw || th>ih)) {tw=iw;th=ih;}
	if(!upScale && isFullscreen && hh>ih) img.css('margin-top', (hh - ih) / 2);
	img.css('height', Math.round(th)).css('width', Math.round(tw));
}