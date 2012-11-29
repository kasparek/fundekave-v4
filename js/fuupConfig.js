var FuupConfig = {"fuga":
{"settings":
	{"autoUpload":"0","showControls":"0","timeout":"30","showImages":"1","multi":"1","chunkSize":"131072","chunkLimit":"6","fileLimit":"80"
,"image":{"width":"2048","height":"2048","quality":"90","type":"jpg,jpeg,gif,png"}
,"appSize":{"width":"-1","height":"200"},"callback":"GaleryEdit.check"}
,"service":{"url":"files.php","vars":{"k":Sett.page,"f":"fuga","auth":Sett.auth}}}
};
FuupConfig.tempStore = jQuery.extend(true, {}, FuupConfig.fuga);
FuupConfig.fuga.settings.browseImg = "http://fundekave-v4.googlecode.com/svn/trunk/assets/browse_big.png";
FuupConfig.tempStore.settings.autoUpload = "1";
FuupConfig.tempStore.settings.showImages = "0";
FuupConfig.tempStore.settings.multi = "0";
FuupConfig.tempStore.settings.appSize.width = "100";
FuupConfig.tempStore.settings.appSize.height = "26";
FuupConfig.tempStore.settings.callback = "fuupUploadComplete";
FuupConfig.tempStore.service.vars.f = "tempStore";