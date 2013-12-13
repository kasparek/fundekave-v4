/** AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING */
var GaleryEdit = new function(){
	var o = this;
	o.uploadStatus = '';
	o.numToUpload = 0;
	o.cfg = {};
	o.lng = {};
	o.init = function(cfg,lng){
		o.cfg = cfg;
		o.lng = lng;
		$("#uploadButt").off('click').click(function(){
			$("#fuga")[0].fuupGateIn("upload");
		});
		$("#cancelButt").off('click').click(function(){
			$("#fuga")[0].fuupGateIn("cancel");
		});
		$("#removeAllButt").off('click').click(function(){
			$("#fuga")[0].fuupGateIn("removeAll");
		});
		listen('delete-galery', 'click', GaleryEdit.del);
	};
	o.updateControls = function(){
		if(o.uploadStatus == 'statusBusy'){
			$("#uploadButt").attr('disabled','disabled');
			$("#cancelButt").removeAttr('disabled');
			$("#removeAllButt").attr('disabled','disabled');
		}else{
			$("#cancelButt").attr('disabled','disabled');
			if(o.numToUpload > 0){
				$("#uploadButt").removeAttr('disabled');
				$("#removeAllButt").removeAttr('disabled');
			}else{
				$("#uploadButt").attr('disabled','disabled');
				$("#removeAllButt").attr('disabled','disabled');
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
	o.del = function(e){
		var f = Fajax, i = gup('d',$(this).attr("href")).split(':').pop();
		if(confirm($(this).attr("title"))){
			f.add('item', i);
			f.send('item-delete');
			f.formStop = true;
			$('#i' + i).hide('slow', function(){
				$('#i' + i).remove()
			});
		}
		return false;
	};
};