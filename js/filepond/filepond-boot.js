$.getCSS = function(src) {
    $('<link>').appendTo('head').attr({
        type: 'text/css',
        rel: 'stylesheet',
        href: src
    });
}
$.createScript = function(url) {
      var head = document.getElementsByTagName("head")[0];
      var script = document.createElement("script");
      script.src = url;
      var promise = $.Deferred();
      // Handle Script loading
      {
         var done = false;

         // Attach handlers for all browsers
         script.onload = script.onreadystatechange = function(){
            if ( !done && (!this.readyState ||
                  this.readyState == "loaded" || this.readyState == "complete") ) {
               done = true;
              promise.resolve();
               // Handle memory leak in IE
               script.onload = script.onreadystatechange = null;
            }
         };
      }

      head.appendChild(script);

      // We handle everything using the script element injection
      return promise;
   };

$.filepondLoad = function(arr, minified) {
    var path = 'node_modules/';
    $.getCSS(path + 'filepond/dist/filepond.css');
    var src = 'filepond/dist/filepond.js';
    if (minified) {
        src = src.replace(/(.js)$/gi, '.min.js');
    }
    $.createScript((path || "") + src).done(function() {
        var register_plugin = [];
        var _arr = $.map(arr, function(src) {
            switch (src) {
                case 'image-preview':
                    $.getCSS(path + 'filepond-plugin-' + src + '/dist/filepond-plugin-' + src + '.css');
            }
            register_plugin.push('FilePondPlugin'+src.replace(/-/gi,' ').replace(/\b\w/g, l => l.toUpperCase()).replace(/\s/g, ''));
            src = 'filepond-plugin-' + src + '/dist/filepond-plugin-' + src + '.js';
            if (minified) {
                src = src.replace(/(.js)$/gi, '.min.js');
            }
            return $.createScript((path || "") + src);
        });
        _arr.push($.Deferred(function(deferred) {
            $(deferred.resolve);
        }));
        $.when.apply($, _arr).done(function() {
            for(var key in register_plugin) {
                FilePond.registerPlugin(window[register_plugin[key]]);
            }
            $(document).trigger('filepond-ready');
        });
    });
}