//TODO: parametrize lang localization
//TODO: fix google callback
var GooMapi = new function(){
	var o = this;
	o.icons = {
		'sail' : 'http://fundekave.net/css/skin/sail/img/sailing.png',
		'blue' : 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
		'red' : 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
	};
	o.uid = function(){
		o.uidc++;
		return 'goomapi' + o.uidc.toString(16)
	};
	o.uidc = 0;
	o.li = {};
	o.poLi = {};
	o.popid = null;
	o.info = null;
	o.unitR = 3440;
	o.unit = 'NM';
	o.units = [
			{
				'id' : 'nm',
				'n' : 'NM',
				'R' : 3440
			}, {
				'id' : 'km',
				'n' : 'Km',
				'R' : 6371
			}
	];
	o.setUnitHandler = function(e){
		for( var i = 0; i < o.units.length; i++){
			if($(this).attr('rel') == o.units[i].id){
				o.setUnit(o.units[i].n, o.units[i].R, this.data.id);
				return false;
			}
		}
	};
	o.setUnit = function(n, R, id){
		o.unitR = R;
		o.unit = n;
		if(id)
			o.editorData(id).updateDistance();
	};
	o.distance = function(lat1, lon1, lat2, lon2){
		var pr = Math.PI / 180, dLat = (lat2 - lat1) * pr, dLon = (lon2 - lon1) * pr, a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1 * pr) * Math.cos(lat2 * pr) * Math.sin(dLon / 2) * Math.sin(dLon / 2), c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)), d = o.unitR * c;
		return d;
	};
	/*
	 * degrees, mins, secs to decimal degrees - possible format 20.5468,15.1568
	 * or 20 10 30 N,15 23 40 W
	 */
	o.posFormat = function(p){
		var n = parseFloat(p);
		if(isNaN(n) || n == 0)
			return 0;
		p = $.trim(p);
		var dir = p.charAt(p.length - 1).toUpperCase();
		if(dir == 'W' || dir == 'E' || dir == 'N' || dir == 'S'){
			var posArr = p.substr(0, p.length - 1).split(' '), d = posArr[0] - 0, m = posArr.length > 1 ? posArr[1] - 0 : 0, s = posArr.length > 2 ? posArr[2] - 0 : 0, sign = (dir == 'W' || dir == 'S') ? -1 : 1;
			p = sign * (d + ((m + (s / 60)) / 60));
		}
		return p - 0;
	};
	o.parsePos = function(v){
		v = $.trim(v);
		var r = [], l;
		if(!v)
			return r;
		if(v.length > 0){
			l = v.split("\n");
			for(i = 0; i < l.length; i++){
				l[i] = l[i].split(',');
				if(l[i].length == 2){
					l[i][0] = o.posFormat(l[i][0]);
					l[i][1] = o.posFormat(l[i][1]);
					if(l[i][0] == 0 && l[i][1] == 0)
						l[i] = false;
				}else{
					l[i] = false;
				}
			}
			for(i = 0; i < l.length; i++){
				if(l[i] !== false)
					r.push(l[i]);
			}
		}
		return r;
	};
	o.cfg = {};
	o.lng = {};
	o.loading = false;
	o.loaded = false;
	o.call = [];
	o.load = function(f){
		if(o.loaded)
			return true;
		if(indexOf(o.call, f) == -1)
			o.call.push(f);
		if(o.loading)
			return;
		o.loading = true;
		LazyLoad.js('http://maps.google.com/maps/api/js?v=3&sensor=false&libraries=geometry&callback=GooMapi.c');
	};
	o.c = function(){
		o.loading = false;
		o.loaded = true;
		while(o.call.length > 0){
			var f = o.call.shift();
			f();
		}
	};
	o.sourceTOut = null;
	o.sourceT = 1000;
	o.sourceTF = function(el){
		$('.' + $(el).attr('class').replace("ThumbA", "") + 'Source').show();
	};
	o.sourceOver = function(e){
		o.sourceTOut = setTimeout(o.sourceTF, o.sourceT, this);
	};
	o.sourceOut = function(){
		clearTimeout(o.sourceTOut);
	};
	o.init = function(cfg,lng){
		o.cfg = cfg;
		o.lng = lng;
		$(".geoInput").each(function(){
			var id = $(this).attr('id'), w = $(this).width(), h = $(this).height();
			if(!id){
				id = o.uid();
				$(this).attr('id', id);
			}
			if($(this).is("textarea")){
				if($('.' + id + 'Thumb').length == 0){
					$(this).change(o.staticSel).hide().after('<a href="#" class="' + id + 'ThumbA" title="Show route"><img class="' + id + 'Thumb" src="" width="' + w + '" height="' + h + '" alt="Google Maps" /></a><a href="#" class="' + id + 'Source" title="Source waypoints"><img src="' + o.cfg.skinUrl + '/img/source.png" alt="Waypoints source" /></a>');
					$('.' + id + 'Source').click(function(){
						clearTimeout(o.sourceTOut);
						$('#' + $(this).attr('class').replace('Source', '')).toggle().change();
						return false;
					}).hide();
					$('.' + id + 'Thumb').click(function(){
						o.geoInputClick(null, $(this).attr('class').replace('Thumb', ''));
					});
					$('.' + id + 'ThumbA').hover(o.sourceOver, o.sourceOut).click(function(){
						return false;
					});
				}
			}else
				$(this).unbind('click', o.geoInputClick).click(o.geoInputClick);
		}).change();
		$(".mapLarge").each(function(){
			var id = $(this).attr('id');
			if(!id){
				id = o.uid();
				$(this).attr('id', id);
			}
			if(!$(this).hasClass('hidden') && $("map" + id + "holder", this).length == 0)
				if($(this).hasClass('editor')){
					var d = o.editorData(id, this), rel = $(this).attr('rel');
					if(rel)
						d.dataEl = document.getElementById(rel);
					d.parent.pop = false;
					o.mapEditor(id);
				}else
					o.show(id);
		});
		listen('mapThumbLink', 'click', o.thumbClick);
	};
	o.thumbClick = function(){
		var id = $(this).attr('id').replace('Thumb', '');
		$(this).remove();
		$('#' + id).removeClass('hidden');
		o.show(id);
		return false;
	};
	o.geoInputClick = function(e, id){
		if(e)
			id = $(this).attr('id');
		if(o.popid)
			o.close();
		var d = o.editorData();
		d.dataEl = null;
		if(id){
			var el = document.getElementById(id);
			if(el){
				if($(el).is('textarea'))
					d.dataEl = el;
			}
		}
		if(!d.dataEl)
			$(d.parent.eli.mapSaveB).hide();
		else
			$(d.parent.eli.mapSaveB).show();
		o.mapEditor('Editor');
		return false;
	};
	o.staticSel = function(e){
		var p = o.parsePos($(this).val()), id = $(this).attr('id'), w = $(this).width(), h = $(this).height();
		var u = 'http://maps.google.com/maps/api/staticmap?size=' + w + 'x' + h + (p.length > 0 ? '&markers=' + p[p.length - 1] : '&zoom=3&center=51.477222,0&maptype=terrain') + '&sensor=false' + (p.length > 1 ? '&path=' + p.join('|') : '');
		$('.' + id + 'Thumb').css("width", (p.length > 0 ? w : 64) + "px").css("height", (p.length > 0 ? h : (h / w) * 64) + "px").attr('src', u);
		if(!$(this).is(':visible')){
			$('.' + id + 'Thumb').show();
		}else
			$('.' + id + 'Thumb').hide();
	};
	o.editorData = function(id, parent){
		if(!id)
			id = 'Editor';
		if(o.li[id]){
			$(o.li[id].eli.mainEl).show();
			return o.li[id].li[0];
		}
		;
		var style = 'border: 1px solid #707070;background-color:#d0d0d0;';
		if(parent)
			style += 'position:relative;width:100%;height:100%;';
		else
			style += 'position:fixed;z-index:10000;';
		$(parent ? parent : "body").append('<div style="' + style + '" id="map' + id + 'Overlay">' + '<div style="position:absolute;width:100%;height:20px;background-color:#e78f08;color:#ffffff;font-weight: bold;font-family: Trebuchet MS,Tahoma,Verdana,Arial,sans-serif;font-size:11px;"><div style="padding:0 10px;line-height:20px;">' + (!parent ? '<a id="mapCloseB" href="#" role="button" style="display:block;float:right;color:#ffffff;">' + o.lng.close + '</a>' : '') + '<span class="mapTitle">' + o.lng.title + '</span> <span class="mapUnitsBox"><a class="mapUnitNM" href="#" rel="nm" style="color:#ffffff;" title="' + o.lng.unitTitle + '">NM</a> <a class="mapUnitKm" href="#" rel="km" style="color:#ffffff;" title="' + o.lng.unitTitle + '">Km</a></span></div></div>' + '<div class="map"></div>' + '<div style="bottom:2px;left:5px;position:absolute;"><input class="mapSearchI" value="" style="width:200px;"/><button class="mapSearchB" style="">' + o.lng.search + '</button></div>' + '<div style="bottom:2px;right:5px;position:absolute;"><button class="mapClearB" title="' + o.lng.clearTitle + '">' + o.lng.clear + '</button><button class="mapSaveB" title="' + o.lng.saveTitle + '">' + o.lng.save + '</button></div></div>');
		var topEl = document.getElementById('map' + id + 'Overlay'), h = new o.hold($('.map', topEl)[0]);
		h.id = id;
		h.editor = true;
		h.eli.mainEl = topEl;
		h.eli.mapUnitsBox = $('.mapUnitsBox', topEl)[0];
		$(h.eli.mapUnitsBox).hide();
		h.eli.mapUnitBLi = [
				$('.mapUnitNM', topEl)[0], $('.mapUnitKm', topEl)[0]
		];
		for( var i = 0; i < h.eli.mapUnitBLi.length; i++){
			h.eli.mapUnitBLi[i].data = h;
			$(h.eli.mapUnitBLi[i]).click(o.setUnitHandler)
		}
		;
		h.eli.mapSearchI = $('.mapSearchI', topEl)[0];
		h.eli.mapSearchI.data = h;
		h.eli.mapSearchB = $('.mapSearchB', topEl)[0];
		h.eli.mapSearchB.data = h;
		$(h.eli.mapSearchB).click(o.search);
		$(h.eli.mapSearchI).keydown(o.searchKey);
		h.eli.mapTitle = $('.mapTitle', topEl)[0];
		if(parent){
			h.pop = false;
		}else{
			o.popid = id;
			h.eli.mapCloseB = document.getElementById("mapCloseB");
			h.eli.mapCloseB.data = h;
			$(h.eli.mapCloseB).click(o.close);
		}
		h.eli.mapClearB = $('.mapClearB', topEl)[0];
		h.eli.mapClearB.data = h;
		$(h.eli.mapClearB).click(o.clear);
		h.eli.mapSaveB = $('.mapSaveB', topEl)[0];
		h.eli.mapSaveB.data = h;
		$(h.eli.mapSaveB).click(o.save);
		h.li = [
			new o.data(h)
		];
		o.li[id] = h;
		o.resize();
		return h.li[0];
	};
	o.hold = function(mapEl){
		var h = this;
		h.eli = {};
		h.li = [];
		h.pop = true;
		h.editor = false;
		h.mapType = 'terrain';
		h.map = null;
		h.geocoder = null;
		h.cluster = null;
		h.mapEl = mapEl;
		h.id = null;
		h.init = function(){
			if(!h.map){
				h.geocoder = new google.maps.Geocoder();
				h.map = new google.maps.Map(h.mapEl, {
					mapTypeId : h.mapType
				});
				h.map.data = h;
				h.map.setCenter(new google.maps.LatLng(30, 35));
				h.map.setZoom(2);
				if(!h.editor)
					h.cluster = new MarkerClusterer(h.map, [], {
						'maxZoom' : 10,
						'zoomOnClick' : true
					});
			}
		};
	};
	o.data = function(p){
		var d = this;
		d.pathColor = "#0000ff";
		d.strokeWeight = 4;
		d.pathAlpha = "0.5";
		d.parent = p;
		d.dataEl = null;
		d.title = '';
		d.infoEl = null;
		d.ico = null;
		d.markers = [];
		d.path = null;
		d.distance = 0;
		d.addListeners = function(){
			if(!d.parent.editor)
				return;
			var ge = google.maps.event;
			ge.clearListeners(d.parent.map, 'click');
			ge.addListener(d.parent.map, 'click', o.editorClick);
			if(d.markers.length == 0)
				return;
			ge.clearListeners(d.path);
			ge.addListener(d.path, 'click', function(e){
				var min = 999, mi, ms = this.data.markers;
				for( var i = 0; i < ms.length - 1; i++){
					var m1 = ms[i], m2 = ms[i + 1], diff = Math.abs(google.maps.geometry.spherical.computeDistanceBetween(m1.getPosition(), m2.getPosition()) - (google.maps.geometry.spherical.computeDistanceBetween(e.latLng, m1.getPosition()) + google.maps.geometry.spherical.computeDistanceBetween(e.latLng, m2.getPosition())));
					if(diff < min){
						min = diff;
						mi = i;
					}
				}
				ms.splice(mi + 1, 0, null);
				this.getPath().insertAt(mi + 1, e.latLng);
				this.data.updateMarker(e.latLng, mi + 1);
				this.data.updateDistance();
			});
			for( var i = 0; i < d.markers.length; i++){
				var m = d.markers[i];
				ge.clearListeners(m);
				ge.addListener(m, 'drag', function(){
					this.data.path.getPath().setAt(indexOf(this.data.markers, this), this.getPosition());
				});
				ge.addListener(m, 'dragend', function(){
					this.data.path.getPath().setAt(indexOf(this.data.markers, this), this.getPosition());
					this.data.updateDistance();
				});
				ge.addListener(m, 'dblclick', function(){
					var i = indexOf(this.data.markers, this);
					this.data.path.getPath().removeAt(i);
					this.data.markers.splice(i, 1);
					this.setMap(null);
					this.data.updateDistance();
					this.data = null;
				});
			}
		};
		d.updateMarker = function(latLng, i){
			var l = d.markers.length;
			if(typeof (i) == 'undefined')
				i = l > 0 ? l - 1 : 0;
			else if(i > l)
				i = l;
			if(!d.markers[i]){
				d.markers[i] = new google.maps.Marker(d.parent.editor ? {
					title : d.title,
					draggable : true,
					raiseOnDrag : false
				} : {
					title : d.title
				});
				var m = d.markers[i];
				m.data = d;
				if(d.parent.editor){
					d.addListeners();
					m.setTitle(o.lng.markerTitle);
				}
				if(d.parent.cluster)
					d.parent.cluster.addMarker(m);
			}
			if(d.ico){
				m.setIcon(o.icons[d.ico] ? o.icons[d.ico] : d.ico);
				m.setZIndex(1);
			}
			m.setPosition(latLng);
			m.setMap(d.parent.map);
			if(d.infoEl)
				if(d.infoEl.length > 0)
					m.htmlInfo = $(d.infoEl).html();
			$(d.infoEl).hide();
		};
		d.resetWP = function(){
			if(d.path)
				d.path.setPath([]);
			while(d.markers.length > 0)
				d.markers.pop().setMap(null);
		};
		d.addWP = function(latLng, wm){
			if(!d.path){
				d.path = new google.maps.Polyline({
					map : d.parent.map,
					path : [],
					strokeColor : d.pathColor,
					strokeOpacity : d.pathAlpha,
					strokeWeight : d.strokeWeight,
					geodesic : true
				});
				d.path.data = d;
			}
			d.path.setMap(d.parent.map);
			d.path.getPath().push(latLng);
			if(wm)
				d.updateMarker(latLng, d.markers.length);
		};
		d.updateDistance = function(){
			d.distance = 0;
			if(!d.path)
				return;
			var l = d.path.getPath();
			if(l.length > 1){
				for(i = 1; i < l.length; i++){
					d.distance += o.distance(l.getAt(i - 1).lat(), l.getAt(i - 1).lng(), l.getAt(i).lat(), l.getAt(i).lng());
				}
			}
			d.distance = Math.round(d.distance * 10) / 10;
			if(d.parent.editor){
				$(d.parent.eli.mapTitle).html(d.distance > 0 ? o.lng.distance + d.distance + o.unit : o.lng.title);
				if(d.distance > 0)
					$(d.parent.eli.mapUnitsBox).show();
				else
					$(d.parent.eli.mapUnitsBox).hide();
			}
		};
		d.get = function(){
			if(!d.dataEl)
				return [];
			return o.parsePos($(d.dataEl).val());
		};
	};
	o.showQueue = [];
	o.show = function(id, f){
		if(id)
			o.showQueue.push({
				id : id,
				f : f
			});
		if(!o.load(o.show))
			return;
		//if(!Lazy.load(Sett.ll.goomapi, o.show))return; //loading cluster: currently loading together
		if(o.showQueue.length == 0)
			return;
		while(o.showQueue.length > 0){
			var q = o.showQueue.pop(), id = q.id, f = q.f;
			if(!o.li[id]){
				var md = document.getElementById(id);
				if(!md)
					return;
				$(md).append('<div id="map' + id + 'holder" style="width:100%;height:' + $(md).height() + 'px;"></div>');
				o.li[id] = new o.hold(document.getElementById("map" + id + "holder"));
				o.li[id].id = id;
				o.li[id].init();
				$('.mapsData', md).each(function(){
					var d = new o.data(o.li[id]);
					d.dataEl = $('.geoData', this);
					if($('.pathColor', this).length > 0)
						d.pathColor = $('.pathColor', this).val();
					if($('.strokeWeight', this).length > 0)
						d.strokeWeight = $('.strokeWeight', this).val();
					d.title = $(d.dataEl).attr('title');
					d.infoEl = $('.geoInfo', this);
					d.ico = $('.geoIco', this).val();
					o.li[id].li.push(d);
				});
			}
			var h = o.li[id], bounds = new google.maps.LatLngBounds();
			for( var i = 0; i < h.li.length; i++){
				var d = h.li[i], l = d.get(), ll = l.length;
				d.resetWP();
				if(ll > 0){
					po = (Math.round(l[ll - 1][0] * 1000)) + ',' + (Math.round(l[ll - 1][1] * 1000));
					if(o.poLi[po]){
						var a = Math.ceil(o.poLi[po] / 4), b = 4 - ((a * 4) - o.poLi[po]);
						if(b == 1)
							l[ll - 1][0] += a * 0.0001;
						else if(b == 2)
							l[ll - 1][1] += a * 0.0002;
						else if(b == 3)
							l[ll - 1][0] -= a * 0.0001;
						else
							l[ll - 1][1] -= a * 0.0002;
						o.poLi[po]++;
					}else
						o.poLi[po] = 1;
					for( var j = 0; j < ll; j++){
						var p = new google.maps.LatLng(l[j][0], l[j][1]);
						d.addWP(p, d.parent.editor || j == ll - 1);
						bounds.extend(p);
					}
					d.updateDistance();
				}
				;
				ll = d.markers.length;
				if(!d.parent.editor && ll > 0){
					var m = d.markers[ll - 1];
					google.maps.event.clearListeners(m);
					if(d.infoEl.length > 0){
						m.htmlInfo = m.htmlInfo.replace('[[DISTANCE]]', d.distance);
						google.maps.event.addListener(m, 'click', function(e){
							if(!o.info)
								o.info = new google.maps.InfoWindow({
									maxWidth : 300
								});
							o.info.setContent(this.htmlInfo);
							o.info.open(this.getMap(), this);
						});
					}
				}
			}
			;
			if(!bounds.isEmpty()){
				o.fit.push({
					m : h.map,
					b : bounds
				});
				setTimeout(o.fitLater, 100);
			}
			$(window).unbind('resize', o.mapResize).resize(o.mapResize).resize();
			if($.isFunction(f))
				f(id);
		}
	};
	o.mapEditorQueue = [];
	o.mapEditor = function(id){
		if(id)
			if(indexOf(o.mapEditorQueue, id) == -1)
				o.mapEditorQueue.push(id);
		if(!o.load(o.mapEditor))
			return;
		//if(!Lazy.load(Sett.ll.goomapi, o.mapEditor))return; //loading cluster: currently loading together
		if(o.mapEditorQueue.length == 0)
			return;
		while(o.mapEditorQueue.length > 0){
			id = o.mapEditorQueue.pop();
			o.editorData(id).parent.init();
			o.show(id, function(id){
				var d = o.editorData(id);
				if(!d.dataEl)
					$(d.parent.eli.mapSaveB).hide();
				else
					$(d.parent.eli.mapSaveB).show();
				d.addListeners();
				d.updateDistance();
				if(d.parent.pop){
					$(window).resize(o.resize).resize();
					$(window).keydown(o.wkey);
				}
			});
		}
	};
	o.fit = [];
	o.fitLater = function(){
		while(o.fit.length > 0){
			var b = o.fit.pop();
			b.m.fitBounds(b.b);
		}
	};
	o.resize = function(e){
		var w = $(window).width(), h = $(window).height(), mw = w * 0.9, mh = h * 0.9, mx = (w - mw) / 2, my = (h - mh) / 2;
		for(k in o.li)
			if(o.li[k].pop)
				$("#map" + o.li[k].id + "Overlay").css('width', mw + 'px').css('height', mh + 'px').css('left', mx + 'px').css('top', my + 'px');
	};
	o.mapResize = function(){
		for(k in o.li){
			if(o.li[k].editor)
				$(o.li[k].mapEl).css('width', '100%').css('position', 'absolute').css('top', '20px').css('bottom', '30px');
			if(o.li[k].map)
				google.maps.event.trigger(o.li[k].map, 'resize');
		}
	};
	o.editorClick = function(e){
		var d = o.editorData(e.id ? e.id : this.data.id), r = d.path ? d.path.getPath() : null, add = true, l = 0;
		if(r)
			l = r.getLength();
		if(l > 0)
			if(r.getAt(l - 1).lat() == e.latLng.lat() && r.getAt(l - 1).lng() == e.latLng.lng())
				add = false;
		if(add)
			d.addWP(e.latLng, true);
		d.updateDistance();
	};
	o.close = function(e){
		var d = o.editorData(o.popid);
		$(d.parent.eli.mainEl).hide();
		$(window).unbind('resize', o.resize);
		$(window).unbind('keydown', o.wkey);
		o.popid = null;
		return false;
	};
	o.save = function(e){
		var d = o.editorData(this.data.id);
		if(d.dataEl){
			var oldVal = $(d.dataEl).val(), newVal = o.toString(this.data.id);
			$(d.dataEl).val(newVal);
			if(oldVal != newVal)
				$(d.dataEl).change();
		}
		o.close();
	};
	o.toString = function(id){
		var d = o.editorData(id), r = '';
		if(d.path){
			var l = [];
			d.path.getPath().forEach(function(latLng){
				l.push(latLng.toUrlValue(4));
			});
			r = l.join("\n");
		}
		return r;
	};
	o.clear = function(e){
		var d = o.editorData(this.data.id);
		d.resetWP();
		d.updateDistance();
		$(d.parent.eli.mapTitle).html(o.lng.title);
	};
	o.wkey = function(e){
		if(e.keyCode == 27)
			o.close();
	}
	o.searchKey = function(e){
		if(e.keyCode == 13)
			o.search(e);
	};
	o.searchResultHandler = function(g, id){
		var d = o.editorData(id);
		o.fit.push({
			m : d.parent.map,
			b : g.bounds
		});
		setTimeout(o.fitLater, 100);
		o.editorClick({
			id : id,
			latLng : g.location
		});
	};
	o.search = function(e){
		var d = o.editorData(e.target.data.id), valI = d.parent.eli.mapSearchI.value, pos = o.parsePos(valI);
		if(pos.length > 0)
			o.searchResultHandler({
				bounds : new google.maps.LatLngBounds(new google.maps.LatLng(pos[0][0], pos[0][1]), new google.maps.LatLng(pos[0][0], pos[0][1])),
				location : new google.maps.LatLng(pos[0][0], pos[0][1])
			}, e.target.data.id);
		else if(d.parent.geocoder)
			d.parent.geocoder.geocode({
				address : valI
			}, function(results, status){
				if(status == google.maps.GeocoderStatus.OK)
					o.searchResultHandler(results[0].geometry, e.target.data.id);
			});
	};
};