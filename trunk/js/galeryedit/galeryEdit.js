/** AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING */
var GaleryEdit = new function(){
	var o = this;
	o.uploadStatus = '';
	o.numToUpload = 0;
	o.numTotal = 0;
	o.numLoaded = 0;
	o.newLi = [];
	o.updLi = [];
	o.run = false;
	o.cfg = {};
	o.lng = {};
	o.init = function(cfg,lng){
		o.cfg = cfg;
		o.lng = lng;
		$("#uploadButt").unbind('click', Fajax.form).addClass('noFajax').click(function(){
			$("#fuga")[0].fuupGateIn("upload");
			return false;
		});
		$("#cancelButt").unbind('click', Fajax.form).addClass('noFajax').click(function(){
			$("#fuga")[0].fuupGateIn("cancel");
			return false;
		});
		$("#removeAllButt").unbind('click', Fajax.form).addClass('noFajax').click(function(){
			$("#fuga")[0].fuupGateIn("removeAll");
			return false;
		});
		o.numLoaded = 0;
		o.numTotal = parseInt($("#fotoTotal").text());
		if(o.numTotal > 0 && $('#fotoList').length > 0)
			o.load(0, 10);
	};
	o.updateControls = function(){
		if(o.uploadStatus == 'statusBusy'){
			$("#uploadButt").removeClass('ui-state-hover').button('disable');
			$("#cancelButt").button('enable');
			$("#removeAllButt").button('disable').removeClass('ui-state-hover');
		}else{
			$("#cancelButt").button('disable').removeClass('ui-state-hover');
			if(o.numToUpload > 0){
				$("#uploadButt").button('enable');
				$("#removeAllButt").button('enable');
			}else{
				$("#uploadButt").removeClass('ui-state-hover').button('disable');
				$("#removeAllButt").button('disable').removeClass('ui-state-hover');
			}
		}
	};
	o.check = function(k, v){
		console.log("FUUP::" + k + " '" + v + "'");
		switch(k) {
		case 'imageUploaded':
			Fajax.send('page-fuup', o.cfg.page);
			break;
		case 'imageNum':
			o.numToUpload = parseInt(v);
			o.updateControls();
			break;
		case 'status':
			o.uploadStatus = v;
			o.updateControls();
			if(v == "statusReady" && $("#uploadControlsHeading").length == 0){
				$('<h2 id="uploadControlsHeading">' + o.lng.selectFiles + '</h2>').hide().appendTo("#uploadControls").slideDown("slow");
			}
			break;
		case 'error':
			alert(o.lng[v]);
			break;
		}
	};
	o.refresh = function(n, u, t){
		o.numTotal = parseInt(t);
		$("#fotoTotal").text(o.numTotal);
		if(n.length > 0)
			o.newLi = o.newLi.concat(n.split(';'));
		if(u.length > 0)
			o.updLi = o.updLi.concat(u.split(';'));
		if(!o.run)
			o.next();
	};
	o.next = function(){
		if(o.updLi.length > 0)
			o.load(o.updLi.pop(), 1, 'U');
		else if(o.newLi.length > 0)
			o.load(o.newLi.pop(), 1);
		else if(o.numLoaded < o.numTotal)
			o.load(0, 10);
		else
			o.run = false;
	};
	o.load = function(item, offset, type){
		var f = Fajax;
		o.run = true;
		if(item > 0){
			f.add('item', item);
			if(type == 'U'){
				f.add('result', 'foto-' + item);
				f.add('resultProperty', '$replaceWith');
			}
		}else{
			f.add('total', o.numTotal);
			f.add('seq', o.numLoaded);
		}
		f.add('offset', offset);
		f.add('call', 'jUIInit');
		f.add('call', 'GaleryEdit.bindDelete');
		f.send('galery-editThumb', o.cfg.page);
	};
	o.loadHandler = function(num){
		var n = parseInt(num);
		if(n > 0)
			o.numLoaded += n;
		o.next();
	};
	o.bindDelete = function(){
		listen('deletefoto', 'click', GaleryEdit.del);
	};
	o.del = function(e){
		var f = Fajax, l = $(this).attr("id").split("-");
		if(confirm($(this).attr("title"))){
			f.add('item', l[1]);
			f.send('item-delete');
			f.formStop = true;
			$('#foto-' + l[1]).hide('slow', function(){
				$('#foto-' + l[1]).remove()
			});
			o.numTotal--;
			$("#fotoTotal").text(o.numTotal);
		}
		return false;
	};
};