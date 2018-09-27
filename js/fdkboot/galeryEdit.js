/** AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING */
var GaleryEdit = new function(){
	var o = this;
	o.uploadStatus = '';
	o.numToUpload = 0;
	o.init = function(){
		listen('delete-galery', 'click', GaleryEdit.del);
	};
	o.del = function(e){
		var f = Fajax, i = gup('d',$(this).attr("href")).split('=').pop();
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