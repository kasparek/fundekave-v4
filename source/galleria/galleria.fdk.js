//load: ,'galeria':['http://cdnjs.cloudflare.com/ajax/libs/galleria/1.2.8/galleria.min.js','[[URL_JS]]galleria.history.min.js']
//$(".galeryThumb a").bind('click',galeriaStart);

function getGaleriaHeight() {var offset=$(".galeria").offset(),galeriaHeight = $(".galeria").width() * 0.75,galeriaHeightMax = Math.round($(window).height() - 75 - offset.top);if(galeriaHeight > galeriaHeightMax) return galeriaHeightMax;return galeriaHeight;}

function galeriaStart(event){if($(".galeria").length==0)return;
$('html, body').animate({scrollTop: $(".galeria").offset().top}, 2000);
window.location.hash = '/'+$('.galeria .galeryThumb').index($("#i"+gup('i',this.href))[0]);galeriaInit();event.preventDefault();return false;}

function galeriaInit(){
$(".galeryThumb a").unbind('click',galeriaStart);
if($('.galeria').length==0)return;
$("body").addClass("bodySidebarOff");
if(!Lazy.load(Sett.ll.galeria,galeriaInit)) return;
if(!Lazy.load([Sett.jsUrl+'galleria.theme/theme.css'],galeriaInit)) return;
Galleria.loadTheme(Sett.jsUrl+'galleria.theme/theme.js');
Galleria.on('loadstart', function(e) {$("#afterFeed").html("");});
Galleria.on('image', function(e) {Fajax.add('id',e.galleriaData.id);Fajax.send('item-comments',Sett.page);});
Galleria.run('.galeria', {height:getGaleriaHeight(),width:$('.galeria').width(),
    dataConfig: function(img) {
        return {
            image: $(img).attr('data-image'),
            id: $(img).attr('id')
        }
    }
});
}