;(function( $ ) {
    $.fn.pop = function() {
        var top = this.get(-1);
        this.splice(this.length-1,1);
        return top;
    };

    $.fn.shift = function() {
        var bottom = this.get(0);
        this.splice(0,1);
        return bottom;
    };
})( jQuery );
/* autogrow */
;(function($){
    $.fn.autogrow = function(options){
        this.filter('textarea').each(function(){
            var $this = $(this), minHeight = $this.height(), u = function(){
                var pt = parseInt($this.css('padding-top').replace('px','')),pb = parseInt($this.css('padding-bottom').replace('px','')),lh = parseInt($this.css("line-height").replace("px", "")),h = $this.height(), sh = $this.prop('scrollHeight')-(pt+pb);
                if(sh > h) 
                    $this.css('height', sh + (lh*2));
            };
            $this.off('change', u).on('keydown', u).change(u).keydown(u).change();
        });
        return this;
    };
})(jQuery);
/**
 * index if item in array
 */
function indexOf(arr, obj, start){
    for( var i = (start || 0); i < arr.length; i++)
        if(arr[i] == obj)
            return i;
    return -1;
}