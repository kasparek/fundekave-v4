/** INITIALIZATION ON DOM */
var isInit = false;
var isMobile = _fdk.isMobile ? _fdk.isMobile : false;
var topBannerHeight = 0;
var topBannerMargin = 0;

// jQuery plugin to prevent double submission of forms
jQuery.fn.preventDoubleSubmission = function() {
  $(this).on('submit',function(e){
    var $form = $(this);

    $('.btn',this).attr('disabled','disabled');
/*
    if ($form.data('submitted') === true) {
      // Previously submitted - don't submit again
      e.preventDefault();
    } else {
      // Mark it so that the next submit can be ignored
      $form.data('submitted', true);
    }*/
  });

  // Keep chainability
  return this;
};

function topBannerPosition() {
    if (topBannerHeight > 0) return;
    var valign = $(".top-image").data('valign'),
        bh = $(".top-image").height(),
        ih = $(".top-image img").height(),
        iy = 0;
    if (valign == 'middle') iy = (bh - ih) / 2;
    else if (valign == 'bottom') iy = bh - ih;
    iy += $(".top-image").data('margin');
    iy = iy > 0 ? 0 : (iy + ih < bh ? bh - ih : iy);
    $(".top-image img").stop().animate({
        'margin-top': iy + 'px'
    }, 100);
}

function loadSidebarPanel($panel) {
    Fajax.add('panel', $panel.data('src'));
    Fajax.send('sidebar-get', _fdk.cfg.page);
}

function boot() {
    //$('form:not(.fajaxform)').preventDoubleSubmission();
    //load language
    var defaultLang = 'cs';
    Lazy.load([_fdk.cfg.jsUrl + 'i18n/_fdk.lng.' + (_fdk.cfg.lang ? _fdk.cfg.lang : defaultLang) + '.js'], boot);
    if (isInit) return;
    isInit = true;
    if (!isMobile) {
        (function(a) {
            if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) isMobile = true
        })(navigator.userAgent || navigator.vendor || window.opera);
    }
    var __resetLayout = Packery.prototype._resetLayout;
    Packery.prototype._resetLayout = function() {
        __resetLayout.call(this);
        // reset packer
        var parentSize = getSize(this.element.parentNode);
        var colW = this.columnWidth + this.gutter;
        this.fitWidth = Math.floor((parentSize.innerWidth + this.gutter) / colW) * colW;
        this.packer.width = this.fitWidth;
        this.packer.height = Number.POSITIVE_INFINITY;
        this.packer.reset();
    };
    Packery.prototype._getContainerSize = function() {
        // remove empty space from fit width
        var emptyWidth = 0;
        for (var i = 0, len = this.packer.spaces.length; i < len; i++) {
            var space = this.packer.spaces[i];
            if (space.y === 0 && space.height === Number.POSITIVE_INFINITY) {
                emptyWidth += space.width;
            }
        }
        return {
            width: this.fitWidth - this.gutter - emptyWidth,
            height: this.maxY - this.gutter
        };
    };
    // always resize
    Packery.prototype.needsResizeLayout = function() {
        return true;
    };
    $('.blog-gallery-thumbs').packery({
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer'
    });
    $("img").unveil();
    $(".sidebar-content").each(function() {
        var $panel = $(this);
        var delay = parseInt($(this).data('delay'));
        if (delay > 0) setTimeout(function() {
            loadSidebarPanel($panel);
        }, delay);
        else loadSidebarPanel(this);
    });
    if (window.location.pathname != '/') {
        _fdk.fuup.fuga.service.url = window.location.pathname + _fdk.fuup.fuga.service.url;
    }
    $("#head-banner").on('click', function() {
        var imgh = $("#head-banner img").height();
        if (topBannerHeight === 0) {
            topBannerHeight = $(".top-image").css('height');
            topBannerMargin = $(".top-image img").css('marginTop');
            $(".top-image").animate({
                height: imgh + 'px'
            }, 500);
            $(".top-image img").animate({
                marginTop: '0px'
            }, 500);
            if ($(this).attr('href').length > 0) {
                if ($("#topImageLink").length === 0) {
                    $(".top-image").append('<div id="topImageLink" class="alert alert-info" style="top:' + (parseInt(topBannerHeight.replace('px', '')) - 60) + 'px;"><a href="' + $(this).attr('href') + '">' + $(this).attr('title') + '</a>');
                }
            }
        } else {
            $(".top-image").animate({
                height: topBannerHeight
            }, 500);
            $(".top-image img").animate({
                marginTop: topBannerMargin
            }, 500);
            topBannerHeight = 0;
        }
        return false;
    });
    if (!isMobile) setTimeout(function() {
        if ($("#galeryFeed").length > 0) {
            Fajax.send('page-thumbs', _fdk.cfg.page);
        }
    }, 1000);
    if ($("#head-banner").length > 0) {
        $(window).resize(topBannerPosition).resize();
        $(".top-image img").on('load', topBannerPosition);
    }
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var h = $(e.target).attr("href"),
            t = $(e.target).data("target");
        if (!t) return;
        if ($(t).is(':empty')) {
            $.ajax({
                type: "GET",
                url: h.replace("show", "show-x"),
                complete: function(a) {
                    if (a.responseText) $(t).html(a.responseText);
                }
            })
        };
    });
    gaLoad();
    calendarInit();
    var w = $(window).width();
    if ($("#sidebar").length == 0) $('body').addClass('bodySidebarOff');
    $(".expand").autogrow();
    $(".opacity").bind('mouseenter', function() {
        $(this).fadeTo("fast", 1);
    }).bind('mouseleave', function() {
        $(this).fadeTo("fast", 0.5);
    });
    fajaxInit();
    fconfirmInit();
    switchOpen();
    $(".thumbnail-xs a").each(function() {
        $(this).addClass('history');
    });
    $(".mapThumbLink").bind('click', gooMapiThumbClick);
    if ($(".geoInput").length > 0 || $(".mapLarge").length > 0) gooMapiInit();
    History.Adapter.bind(window, 'statechange', function() {
        var State = History.getState();
        History.log('statechange:', State.data, State.title, State.url);
        if (parseInt(State.data.i) > 0) {
            if (Fajax.xhrList['item-show']) {
                History.log('cancel ajax:', 'item-show');
                Fajax.cancel('item-show');
            }
            Fajax.action(State.data.action + '/i=' + State.data.i + '/0/' + State.data.eid);
        }
    });
    listen('history', 'click', function(e) {
        var i = gup("i", e.currentTarget.href);
        History.pushState({
            action: 'item-show',
            eid: $(e.target).attr('id'),
            i: i
        }, "Loading ...", "?i=" + i);
        return false;
    });
    slimboxInit();
    Fullscreen.init();
    fuupInit();
    datePickerInit();

    $("a[data-toggle=collapse]").on('click',function(){
        var href = $($(this).attr('href'));
        setTimeout(function(){
            if($(href).length>0 && !$(href).hasClass('collapse')) {
                var shown = $(href)[0];
                $("input:text, textarea",shown).first().focus();

            }
        },500);
        
    });

    if (parseInt(_fdk.cfg.user) > 0) {
        ckedInit();
        $("#recipient").change(avatarfrominput);
        $("#selectAllCheckbox").change(function() {
            $(".deleteCheckbox").prop('checked', $(this).prop('checked'));
        });
        $('#ppinput').hide();
        $("#recipientList").change(function(evt) {
            var rec = [];
            $("#recipientList option:selected").each(function() {
                rec.push($(this).text());
            });
            if (rec) $("#recipient").val(rec.join(',')).change();
            avatarfrominput();
        });
        GaleryEdit.init();
        if (parseInt(_fdk.cfg.msgTi) > 0) Msg.startPoll();
        var perm = $("#accessSel");
        if (perm.length > 0) perm.change(function() {
            var v = $(this).val();
            if (v == 0) $("#rule1").show();
            else $("#rule1").hide();
        }).change();
    }
}

function recaptchaStart() {
    if (!Lazy.load(['http://www.google.com/recaptcha/api/js/recaptcha_ajax.js'], recaptchaStart)) return;
    Recaptcha.create("6LexXNkSAAAAAE_BDWQHhapdx-XPHItdWgBvDTSm", 'recaptchaBox', {
        tabindex: 3
    });
}
/** INIT jQuery UI and everything possibly needed for ajax forms and items */
function jUIInit() {
    if (!Lazy.load(_fdk.load.ui, jUIInit)) return;
    datePickerInit();
    ckedInit();
    fajaxInit();
    fconfirmInit();
    gooMapiInit();
    fuupInit();
    slimboxInit();
    GaleryEdit.init();
    $(".expand").autogrow();
};

function gooMapiInit() {
    if (!Lazy.load(_fdk.load.goomapi, gooMapiInit)) return;
    GooMapi.init(_fdk.cfg, _fdk.lng.goomapi);
};
var gooMapiThumbClickElement = null;

function gooMapiThumbClick() {
    $(this).unbind('click', gooMapiThumbClick);
    if (!gooMapiThumbClickElement) gooMapiThumbClickElement = this;
    if (!Lazy.load(_fdk.load.goomapi, gooMapiInit)) return;
    $(gooMapiThumbClickElement).click();
    gooMapiThumbClickElement = null;
}

function ckedInit() {
    if ($(".markitup").length === 0) return;
    if (!Lazy.load(_fdk.load.richta, ckedInit)) return;
    //http://www.tinymce.com/wiki.php/Controls - complete list of controls
    tinymce.remove();
    tinymce.init({
        selector: ".markitup",
        menubar: false,
        toolbar: "save | undo redo | styleselect | bold italic | link unlink image | alignleft aligncenter alignright alignjustify bullist blockquote | visualblocks code fullscreen",
        mode: 'textareas',
        toolbar_items_size: 'small',
        save_onsavecallback: function(a) {
            var form = $("#" + a.id)[0].form;
            console.log("TinyMCE Save");
            Fajax.form(null, form);
            return false;
        },
        plugins: ["autoresize", "autosave save", "advlist autolink lists link image anchor", "searchreplace visualblocks code fullscreen", "media contextmenu paste"]
    });
}

function slimboxInit() {
    if ($("a[rel^='lightbox']").length === 0 && $(".fotomashup").length === 0) return;
    if (!Lazy.load(_fdk.load.colorbox, slimboxInit)) return;
    $(".fotomashup a").colorbox({
        rel: 'grp1',
        title: function() {
            var url = $(this).attr('href');
            return '<a href="' + url + '">Album ' + $(this).attr('title') + '</a>';
        },
        href: function() {
            return $('img', this).data('image');
        },
        scalePhotos: true,
        maxHeight: '100%',
        maxWidth: '100%'
    });
    $("a[rel^='lightbox']").colorbox({
        scalePhotos: true,
        maxHeight: '100%',
        maxWidth: '100%'
    });
}

function fuupInit() {
    if ($(".filepond").length === 0) return;
    if (!Lazy.load(_fdk.load.filepond, fuupInit)) return;
}
/** request init */
function friendRequestInit(text) {
    $('#friendrequest').remove();
    $("#sysmsgBox").after(text);
    $('#friendrequest').removeClass('hidden').show('slow');
    fajaxInit();
    $('#cancel-request').off('click').on('click', function(event) {
        remove('friendrequest');
        return false;
    });
}
/** ajax link init */
function fajaxInit() {
    Fajax.init();
}

function fconfirmInit(event) {
    $('.confirm').each(function() {
        var pf = false;
        if (this.form) pf = $(this.form).hasClass('fajaxform');
        if (!$(this).hasClass('fajaxa') && !pf) {
            $(this).bind('click', onConfirm);
        }
    });
}

function onConfirm(e) {
    if (!confirm($(e.currentTarget).attr("title"))) {
        preventAjax = true;
        e.preventDefault();
    }
}
/** simple functions */
function shiftTo(y) {
    if (!y) y = 0;
    $(window).scrollTop(y);
}

function scrollToBottom(id) {
    $("#" + id).animate({
        scrollTop: $('#' + id)[0].scrollHeight
    }, 1000);
}

function enable(id) {
    $('#' + id).removeAttr('disabled');
}

function remove(id) {
    $('#' + id).remove();
}

function switchOpen() {
    $('.switchOpen').click(function() {
        $('#' + this.rel).toggleClass('hidden');
        return false;
    });
}

function listen(c, e, f) {
    $("." + c).unbind(e, f).bind(e, f);
}

function gup(n, url) {
    n = n.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var r = new RegExp("[\\?&|]" + n + "=([^&#|]*)"),
        res = r.exec(url);
    return res === null ? 0 : res[1];
}
var msgId = 1;

function msg(type, text) {
    $("#sysmsgBox").append('<div id="sysmsg' + msgId + '" class="alert alert-' + type + ' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' + text + '</div>');
    $("#sysmsg" + msgId).delay('10000').hide('slow');
    msgId++;
}

function redirect(dir) {
    window.location.replace(dir);
}
/** AVATAR FROM input IN fpost */
function avatarfrominput(evt) {
    Fajax.add('username', $("#recipient").val());
    Fajax.add('call', 'fajaxInit');
    Fajax.send('post-avatarfrominput', '');
}
/** IMAGE UPLOADING TOOL HANDLERS - FUUP */
function fuupUploadComplete(k, v) {
    if (k == 'error') alert(_fdk.lng.galery[v]);
    if (k != 'complete') return;
    var i = $('#i').attr('value');
    if (i > 0) Fajax.add('i', i);
    Fajax.add('call', 'jUIInit');
    Fajax.send('item-image', _fdk.cfg.page);
}

function tempStoreDeleteInit() {
    $("#tempStoreButt").click(function(e) {
        $("#imageHolder").html('');
        Fajax.send('item-tempStoreFlush', _fdk.cfg.page);
        e.preventDefault();
        return false;
    });
}
/** MSG CHAT FUNCTIONS */
var Msg = new function() {
    var o = this;
    o.getData = function() {
        var p = gup('p', window.location.href),
            l = [],
            xs = [];
        $(".fitem.unread.sent").each(function() {
            l.push($(this).attr('id').replace('mess', ''));
        });
        $(".msg-xs").each(function() {
            xs.push($(this).attr('id').replace('messxs', ''));
        });
        if (l.length > 0) Fajax.add('unreadedSent', l.join(','));
        if (xs.length > 0) Fajax.add('xsShow', xs.join(','));
        if (_fdk.cfg.page == 'fpost' && p > 0) Fajax.add('p', p);
        return Fajax.request.get();
    };
    o.startPoll = function() {
        $.PeriodicalUpdater('?m=post-poll-x&k=' + _fdk.cfg.page, {
            data: o.getData,
            minTimeout: _fdk.cfg.msgTi / 2,
            maxTimeout: _fdk.cfg.msgTi,
            multiplier: 2
        }, function(remoteData, success, xhr, handle) {
            Fajax.response.run(remoteData);
        });
    };
    o.chatInit = function() {
        $(".msg-text").off('keypress').on('keypress', function(e) {
            if (e.which == 13) {
                $('#msgBtnSubmit').click();
                e.preventDefault();
                $('#msgList').append('<div class="msg-xs" id="messxs0">' + $("#msgText").val() + '</div>');
                $("#msgText").val('');
                scrollToBottom('msgList');
            }
        });
    };
};
/* lazy google analytics load */
function gaLoad() {
    var ga = document.createElement('script');
    ga.type = 'text/javascript';
    ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ga, s);
}