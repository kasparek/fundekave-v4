$(function() {

    $.filepondLoad(['file-validate-size', 'file-validate-type', 'image-transform', 'image-resize',
        //'image-crop',
        'image-preview', 'file-encode', 'image-exif-orientation'
    ]);
    $(document).on('filepond-ready', function() {

        var input = document.querySelector('input[type="file"]');
        var form = input.form;
        var cfg = {
            imageTransformOutputQuality: 90,
            imagePreviewHeight: 240,
            imageResizeUpscale: false,
            imageResizeTargetWidth: 2048,
            imageResizeTargetHeight: 2048,
            imageResizeMode: 'contain',
            imageTransformOutputQualityMode: 'optional',
            instantUpload: false
        };
        var data = $(input).data();
        for(var key in data) {
            if(key.indexOf('filepond')===0) {
                var value = data[key];
                key = key.substr(8);
                key = key.charAt(0).toLowerCase() + key.slice(1);
                cfg[key] = value;
            }
        }
        if(cfg.maxFiles && cfg.maxFiles === 1) {
            $(input).removeAttr('multiple');
        }
        FilePond.setOptions({
            server: 'fpapi/'
        });
        var pond = FilePond.create(input, cfg);
        const pond_root = document.querySelector('.filepond--root');
        pond_root.addEventListener('FilePond:processfile', e => {
            if(!e.detail.error && e.detail.file) {
                var not_processed = get_not_processed_files();
                var file = e.detail.file;
                Fajax.add('file_id',file.serverId);
                $(document).one("Fajax:complete",function(event){
                    setTimeout(function(){pond.removeFile(file.id);},200);
                    //if(event.response)
                    console.log(event.response);
                    if(not_processed.length===0) {
                        if($(input).data('submit-after-upload')) {
                            $("button[type=submit]",form).removeAttr('disabled').trigger('click').attr('disabled','disabled');
                        }
                    }
                });
                Fajax.send('page-fuup', _fdk.cfg.page);
            }
        });
        var get_not_processed_files = function() {
            var files = pond.getFiles();
            var not_processed = [];
            for (var i = files.length - 1; i >= 0; i--) {
                //if any file not in status 5 process all files
                if (files[i].status != 5 && not_processed.indexOf(files[i].id) < 0) {
                    not_processed.push(files[i].id);
                }
            }
            return not_processed;
        }
        $("button[type=submit]",form).on('click', function() {
            
            var files = pond.getFiles();
            if (files.length===0) {
                if($(input).data('min-files')>0) {
                    alert('Please, insert some images.');
                    return false;
                } else {
                    return true;
                }
            }
            var not_processed = get_not_processed_files();
            if (not_processed.length>0) {
                $(this).attr("disabled", "disabled");
                for (var i = not_processed.length - 1; i >= 0; i--) {
                    pond.processFile(not_processed[i]).then(file => {
                        //file processed
                    });
                }
                return false;
            }
            return true;
        });
    });
});