//http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js
function fuupUploadOneComplete() {
	sendAjax('page-fuup');
};
function fuupUploadAvatarComplete() {

};
function fuupUploadPageAvatarComplete() {

};

function deleteFoto(event) {
		if (confirm($(this).attr("title"))) {
			//---send ajax
			var id = $(this).attr("id");
			idArr = id.split("-");
			addXMLRequest('itemId', idArr[1]);
			sendAjax('galery-delete');
			//---remove element
			$('#foto-'+idArr[1]).hide('slow',function(){$('#foto-'+idArr[1]).remove()});
			fotoTotal--;
			$("#fotoTotal").text(fotoTotal);
		}
		event.preventDefault();
		preventAjax = true;
	};

var buttonClicked = '';
var preventAjax = false;
function fconfirm(event) { setListeners('confirm', 'click', function(event) { if (!confirm($(this).attr("title"))) { preventAjax = true; event.preventDefault(); } }); };
function onButtonClick(event) { buttonClicked = event.target.name; };
function fsubmit(event) {
	$("#errormsgJS").hide();
	if (preventAjax == true) { preventAjax = false; return; }
	var arr = $(this).formToArray(false);
	var result = false;
	var resultProperty = false;
	while (arr.length > 0) {
		var obj = arr.shift();
		addXMLRequest(obj.name, obj.value);
		if (obj.name == 'result') result = true;
		if (obj.name == 'resultProperty') resultProperty = true;
	}
	if (result == false) addXMLRequest('result', $(this).attr("id"));
	if (resultProperty == false) addXMLRequest('resultProperty', 'html');
	if (buttonClicked.length > 0) addXMLRequest('action', buttonClicked);
	addXMLRequest('k', gup('k', this.action));
	sendAjax(gup('m', this.action),gup('k', this.action));
	event.preventDefault();
};

function fajaxform(event) {
	setListeners('button', 'click', onButtonClick);
	setListeners('fajaxform', 'submit', fsubmit );
};
function bindDeleteFoto() {
	setListeners('deletefoto', 'click', deleteFoto);
}

function fuupInit() {
	$(".fuup").each(function(i){
		swfobject.embedSWF("assets/Fuup.swf", $(this).attr('id'), "100", "25", "10.0.12", "expressInstall.swf", {config:"files.php?k="+gup('k',$(".fajaxform").attr('action'))+"|f=cnf|c="+$(this).attr('id').replace(/D/g,".").replace(/S/g,'/'),containerId:$(this).attr('id')},{wmode:'transparent'});
	});
}

function uploadifyInit() {
	$(".uploadify").each(function(i){
		$(this).fileUpload( {
			'uploader' : 'uploadify/uploader.swf',
			'cancelImg' : 'uploadify/cancel.png',
			'script' : 'uploadify/upload.php',
			'fileExt' : '*.jpg;*.jpeg;*.gif;*.png',
			'scriptData' : {
				'u' : gup('u', $(".fajaxform").attr('action')),
				'm' : gup('m', $(".fajaxform").attr('action'))
			},
			'onComplete' : function(event) {
				if($(this).hasClass('multi')) {
					addXMLRequest('uploadify', 'oneDone');
					addXMLRequest('modul', gup('m', $(".fajaxform").attr('action')));
					addXMLRequest('item', gup('i', $(".fajaxform").attr('action')));
					addXMLRequest('ident', $(this).attr('id'));
					sendAjax(gup('m', $(".fajaxform").attr('action')));
				}
			},
			'onAllComplete' : function(event) {
				addXMLRequest('uploadify', 'complete');
				addXMLRequest('modul', gup('m', $(".fajaxform").attr('action')));
				addXMLRequest('item', gup('i', $(".fajaxform").attr('action')));
				addXMLRequest('ident', $(this).attr('id'));
				sendAjax(gup('m', $(".fajaxform").attr('action')));
			},
			'multi' : ($(this).hasClass('multi')) ?  true  :  false ,
			'auto' : ($(this).hasClass('multi')) ? false : true,
			'displayData' : 'speed'
		});
	});
	$(".submitUploadify").click(function(evt){
		$("#"+$(".uploadify.multi").attr('id')).fileUploadStart();
		evt.preventDefault();
	});
};

function fajaxa(event) {
	setListeners('fajaxa', 'click', function(event) {
		if (preventAjax == true) {
			preventAjax = false;
			return;
		}
		var str = gup('d', this.href);
		var arr = str.split(';');
		var result = false;
		var resultProperty = false;
		while (arr.length > 0) {
			var rowStr = arr.shift();
			var row = rowStr.split(':');
			addXMLRequest(row[0], row[1]);
			if (row[0] == 'result')
				result = true;
			if (row[0] == 'resultProperty')
				resultProperty = true;
		}
		if (result == false)
			addXMLRequest('result', $(this).attr("id"));
		if (resultProperty == false)
			addXMLRequest('resultProperty', 'html');
		sendAjax(gup('m', this.href));
		event.preventDefault();
	});
};

function initUserPost() {
	$("#prokoho").change(avatarfrominput);
	$("#recipientcombo").change( function(evt) {
		var str = "";
		$("#recipientcombo option:selected").each( function() {
			str += $(this).text() + " ";
		});
		$("#prokoho").attr("value", str);
		$("#recipientcombo").attr("selectedIndex", 0);
		avatarfrominput(evt);
	});
}
function avatarfrominput(evt) {
	addXMLRequest('username', $("#prokoho").attr("value"));
	addXMLRequest('result', "recipientavatar");
	addXMLRequest('resultProperty', 'html');
	addXMLRequest('call', 'initSupernote');
	addXMLRequest('call', 'fajaxa');
	sendAjax('post-avatarfrominput');
}

function datePickerInit() {
	$.datepicker.setDefaults($.extend( {
		showMonthAfterYear : false
	}, $.datepicker.regional['']));
	$(".datepicker").datepicker($.datepicker.regional['cs']);
};

function initSlimbox() {
	$("a[rel^='lightbox']").slimbox( {
		overlayFadeDuration : 100,
		resizeDuration : 100,
		imageFadeDuration : 100,
		captionAnimationDuration : 100
	}, null, function(el) {
		return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));
	});
}

function initSupernote() {
	supernote = new SuperNote('supernote', {});
}

function draftSetEventListeners() {
	setListeners('draftable', 'keyup', draftEventHandler);
};

function markItUpInit() {
	$('.markItUp').markItUp(mySettings);
}

function switchOpen() {
	setListeners('switchOpen', 'click', 
			function(evt){ 
		      $('#'+this.rel).toggleClass('hidden');
		     }
		);
}
/**
 *main init
 **/
$(document).ready( function() {
$("#errormsgJS").css('display','block');
$("#errormsgJS").hide();
	//---set default listerens - all links with fajaxa class - has to have in href get param m=Module-Function and d= key:val;key:val
		fajaxa();
		fconfirm();
		switchOpen();
		setListeners('popupLink', 'click', function(evt) {
			openPopup(this.href);
			evt.preventDefault();
		});
		// ---ajax textarea
		draftSetEventListeners();
		// ---fuvatar
		$('.fuvatarswf').each(
				function() {
					var elmInst = $(this);
					var elmImgInst = $("#"
							+ elmInst.id.replace('fuplay', 'fuimg'));
					var width = gup('w', elmImgInst.attr('src'));
					var height = gup('h', elmImgInst.attr('src'));
					swfobject.embedSWF("/fuvatar/fuplay.swf", elmInst.id,
							width, height, "9.0.115", "expressInstall.swf", {
								u : elmInst.attr('id').replace('fuplay', ''),
								time : gup('t', elmImgInst.src)
							}, {
								allowFullScreen : "true"
							});
				});
		// ---textarea toolbox
		initInsertToTextarea();
		// ---round corners
		DD_roundies.addRule('.radcon', 5);
		// ---message page
		$("#prokoho").change(avatarfrominput);
		$("#recipientcombo").change( function(evt) {
			var str = "";
			$("#recipientcombo option:selected").each( function() {
				str += $(this).text() + " ";
			});
			$("#prokoho").attr("value", str);
			$("#recipientcombo").attr("selectedIndex", 0);
		});
		fotoTotal = $("#fotoTotal").text();
		$('#fotoList').each(function() {
			if(fotoTotal > 0) {
				galeryLoadThumb();
			}
		});
		
	});

var fotoTotal = 0;
var fotoLoaded = 0;
var itemsNewList =  [];
var itemsUpdatedList = [];
var galeryCheckRunning = false;

function galeryRefresh(itemsNew,itemsUpdated,total) {
	fotoTotal = parseInt( total );
	$("#fotoTotal").text(total);
	
	var itemsNewArr=[],itemsUpdatedArr=[];
	if(itemsNew.length>0) itemsNewArr = itemsNew.split(',');
	if(itemsUpdated.length>0) itemsUpdatedArr = itemsUpdated.split(',');
	while(itemsNewArr.length>0) {
		itemsNewList.push(itemsNewArr.shift());
	}
	while(itemsUpdatedArr.length>0) { 
		itemsUpdatedList.push(itemsUpdatedArr.shift());
	}
	if(!galeryCheckRunning) galeryCheck();
}

function galeryCheck() {
	if(itemsUpdatedList.length>0) {
		galeryLoadThumb(itemsUpdatedList.shift(),'U');
	} else if(itemsNewList.length>0) {
	  galeryLoadThumb(itemsNewList.shift(),'N');
	} else if(fotoLoaded < fotoTotal) {
		galeryLoadThumb();
	}
}

function galeryLoadThumb(item,type) {
	var destSet=false;
	galeryCheckRunning = true;
	if(item > 0) {
		addXMLRequest('item', item);
		if(type=='U') {
			addXMLRequest('result', 'foto-'+item);
			addXMLRequest('resultProperty', 'replaceWith');
			destSet=true;
		}
	} else {
		addXMLRequest('total', fotoTotal);
		addXMLRequest('seq', fotoLoaded);
	}
	if(destSet===false) {
		addXMLRequest('result', 'fotoList');
		addXMLRequest('resultProperty', 'append');
	}
	addXMLRequest('call', 'fajaxform');
	addXMLRequest('call', 'datePickerInit');
	addXMLRequest('call', 'bindDeleteFoto');
	fotoLoaded++;
	if(fotoLoaded < fotoTotal) {
		addXMLRequest('call', 'galeryCheck');
	} else {
	  galeryCheckRunning = false;
	}
	sendAjax('galery-editThumb');
};
// ---textarea - size/markitup switching
function initInsertToTextarea() {
	setListeners('submit', 'click', function(evt) {
		if (draftTimeout) {
			clearTimeout(draftTimeout);
		}
	});
	setListeners(
			'toggleToolSize',
			'click',
			function(evt) {
				$("#" + gup("textid", this.href)).toggleClass(
						gup("class", this.href));
				$("#" + gup('toolid', this.href)).toggleClass(
						'textareaToolboxLarge');
				if ($("#" + gup("textid", this.href) + ".markItUpEditor").length === 1) {
					$("#" + gup("textid", this.href)).markItUpRemove();
				} else {
					$("#" + gup("textid", this.href)).markItUp(mySettings);
				}
				evt.preventDefault();
			});
}

//---drafting ----------------------------
// ---textarea drafting
var arrDraft = [], //0-id, 1-lastlength
timerRunning = false, draftTimer = 5000, draftTimeout, //---with this timer could be stopped
draftTimeoutCounter = 0; // --if bigger than 3 stop timer - reset every time
							// save function is called

function draftDoSave() {
	var x, arrDraftLength = arrDraft.length;
	for (x = 0; x < arrDraftLength; x++) {
		taText = $("#" + arrDraft[x][0]).attr('value');
		if (taText.length != arrDraft[x][1] && taText.length > 0) {
			addXMLRequest('place', $("#" + arrDraft[x][0]).attr("id"));
			addXMLRequest('text', taText);
			addXMLRequest('call', 'draftSaved;' + $("#" + arrDraft[x][0]).attr(
					"id"));
			sendAjax('draft-save');
			arrDraft[x][1] = taText.length;
			draftTimeoutCounter = 0;
		}
		;
	}
	;
};

function draftGetLength(textareaInst) {
	return textareaInst.value.length;
};

// set class - is saved - green - callback function from xajax
function draftSaved(textareaId) {
	$("#" + textareaId).removeClass('draftNotSave');
	$("#" + textareaId).addClass('draftSave');
};

// check all ta and set not saved class if there is a difference between string
// length
function draftIsSavedSetClass() {
	var x, ta, arrDraftLength = arrDraft.length;
	for (x = 0; x < arrDraftLength; x++) {
		if (arrDraft[x][1] != $("#" + arrDraft[x][0]).attr('value').length) {
			$("#" + arrDraft[x][0]).removeClass('draftSave');
			$("#" + arrDraft[x][0]).addClass('draftNotSave');
		}
	}
};

// register textarea
function setDraftElement(tarea) {
	var x, add = 1, arrDraftLength = arrDraft.length;
	for (x = 0; x < arrDraftLength; x++) {
		if (arrDraft[x][0] == $(tarea).attr('id')) {
			add = 0;
		}
		;
	}
	;
	if (add == 1) {
		arrDraft.push( [ $(tarea).attr('id'), 0 ]);
	}
	;
};

// initiate timer, check not saved
function startDraftTimer() {
	if (timerRunning === false) {
		draftTimeout = setTimeout(draftTimerHandler, draftTimer);
		timerRunning = true;
	}
	draftIsSavedSetClass();
};

function draftTimerHandler() {
	draftDoSave();
	draftTimeout = setTimeout(draftTimerHandler, draftTimer);
	if (draftTimeoutCounter > 3) {
		draftTimeoutCounter = 0;
		timerRunning = false;
		clearTimeout(draftTimeout);
	}
};

// register ib keyup
function draftEventHandler() {
	setDraftElement(this);
	startDraftTimer();
};
// ---end-drafting------------------------

// ---popup opening
function openPopup(href) {
	window.open(href, 'fpopup', 'scrollbars=' + gup("scrollbars", href)
			+ ',toolbar=' + gup("toolbar", href) + ',menubar='
			+ gup("menubar", href) + ',status=' + gup("status", href)
			+ ',resizable=' + gup("resizable", href) + ',width='
			+ gup("width", href) + ',height=' + gup("height", href) + '');
};
// ---util funcions
function setListeners(className, eventName, functionDefinition) {
	$("." + className).unbind(eventName, functionDefinition);
	$("." + className).bind(eventName, functionDefinition);
};
function gup(name, url) {
	name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
	var regex = new RegExp("[\\?&|]" + name + "=([^&#|]*)"), results = regex
			.exec(url);
	return (results === null) ? (0) : (results[1]);
};
function msg(type, text) {
	$("#"+type+"msgJS").html( text );
	$("#"+type+"msgJS").show('slow');
	setTimeout(function(){ $("#"+type+"msgJS").hide('slow'); },5000)
}
// ---send and process ajax request
function sendAjax(action,k) {
	var data = getXMLRequest();
	$.ajax( {
		type : "POST",
		url : "index.php",
		dataType : 'xml',
		dataProcess : false,
		cache : false,
		data : "m=" + action + "-x"+((k)?("&k="+k):(''))+"&d=" + data,
		complete : function(data) {
			$(data.responseXML).find("Item").each(
					function() {
						var item = $(this);
						var command = '';
						switch (item.attr('property')) {
							case 'css':
								el = createEl('link',{'type':'text/css','rel':'stylesheet','href':item.text()});
						        if ($.browser.msie) el.onreadystatechange = function() { /loaded|complete/.test(el.readyState) && call(); };
						        else if ($.browser.opera) el.onload = call;
						        else { //FF, Safari, Chrome
						          (function(){
						            try {
							            el.sheet.cssRule;
						            } catch(e){
							            setTimeout(arguments.callee, 20);
							            return;
						            };
						            call();
						          })();
						        }
							  $('head').get(0).appendChild(el);
				                 break;
							case 'getScript':
								var arr = item.text().split(';');
								if(arr[1]) {
									command = "$.getScript('"+arr[0]+"',"+arr[1]+");";
								} else {
									command = "$.getScript('"+arr[0]+"');";
								}
								break;
							case 'callback':
								command = item.text() + "( data.responseText );";
								break;
							case 'call':
								var arr = item.text().split(';');
								var functionName = arr[0];
								var par = '';
								if (arr.length > 1) {
									arr.splice(0,1);
									par = arr.join("','");
								}
								command = functionName + "('" + par + "');";
								break;
							case 'html':
							case 'replaceWith':
							case 'append':
								command = '$("#' + item.attr('target') + '").' + item.attr('property') + '( item.text() );'
								break;
							default:
								command = '$("#' + item.attr('target') + '").attr("' + item.attr('property') + '", item.text());';
						}
						if(command.length>0) eval(command);
					});
		}
	});
}
function redirect(dir) {
	window.location.replace(dir);
}
//---build xml request
var xmlArray = [];
var xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
function resetXMLRequest() {
	xmlArray = []
}
function addXMLRequest(key, value) {
	var str = xmlStr;
	str = str.replace('{KEY}', key);
	str = str.replace('{DATA}', value);
	xmlArray.push(str);
}
function getXMLRequest() {
	var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>';
	resetXMLRequest();
	return str;
}

var call = function(){ return true; };
var createEl = function(type,attr){
    var el = document.createElement(type);
    $.each(attr,function(key){
      if(typeof(attr[key])!='undefined') el.setAttribute(key, attr[key]);
    });
    return el;
  };