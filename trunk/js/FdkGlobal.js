function setTagEventListeners(event) { setListeners('tagLink','click',function(evt) { addXMLRequest('item', $(this).attr("id")); sendAjax(gup('m',$(this).attr("href"))); evt.preventDefault(); }); };

function setPocketAddEventListeners(event) { setListeners('pocketAdd','click',function(event) { 
if(gup("i",this.href)>0) {
  addXMLRequest('item', gup("i",this.href));
} else { 
  addXMLRequest('page', gup("k",this.href));
}
sendAjax('user-pocketIn'); 
evt.preventDefault(); }); };

function setPollListeners(event) { setListeners('pollAnswerLink','click',function(event) { 
    addXMLRequest('poll', gup('poll',this.href));
    addXMLRequest('result', $(this).attr("id"));
    addXMLRequest('resultProperty', 'html');
    addXMLRequest('call','setPollListeners');
    sendAjax('user-poll');
evt.preventDefault(); }); };

function datePickerInit() { datePickerController.create(); };

function initSupernote() { supernote = new SuperNote('supernote', {}); }

function draftSetEventListeners() { setListeners('draftable','keyup',draftEventHandler); };

function initSwitchFriend() {
  $(".switchFriend").unbind('click', switchFriendRequest);
  $(".switchFriend").bind('click', switchFriendRequest);
}

/**
 *main init
 **/ 
$(document).ready(function(){
  //---set tag listeners
  setTagEventListeners();
  //---set poll
  setPollListeners();
  //---pocket
  setPocketAddEventListeners();
  //---popup
setListeners('popupLink', 'click', function(evt) { openPopup(this.href); evt.preventDefault(); });
initSupernote();
initSwitchFriend();
draftSetEventListeners();

//---fuvatar
$('.fuvatarswf').each(function() {
      var elmInst = $(this);
      var elmImgInst = $("#"+elmInst.id.replace('fuplay','fuimg'));
      var width = gup('w',elmImgInst.attr('src'));
      var height =  gup('h',elmImgInst.attr('src'));
      swfobject.embedSWF("/fuvatar/fuplay.swf", elmInst.id, width, height, "9.0.115", "expressInstall.swf",{u:elmInst.attr('id').replace('fuplay',''),time:gup('t',elmImgInst.src)},{allowFullScreen:"true"});    
    });

//---temporary domtabs
$('.domtabs').each(function() { $(this).css('display','block'); });

//---textarea toolbox
  initInsertToTextarea();
//---draft set listeners
  draftSetEventListeners();
  
});

//---textarea - text2cursor
var activeTextareaId = '';

function setCsr(elem, caretPos) {
  if(elem.createTextRange) {
    var range = elem.createTextRange();
    range.move('character', caretPos);
    range.select();
  } else {
    if(elem.selectionStart) {
      elem.focus();
      elem.setSelectionRange(caretPos, caretPos);
    } else {
      elem.focus();
    }
  }
};

function insertAtCursor(myField, myValue) {
  block = '';
  //IE support
  if (document.selection) {
    myField.focus();
    sel = document.selection.createRange();
    block = sel.text;
    sel.text = myValue;
  }
  //MOZILLA/NETSCAPE support
  else if (myField.selectionStart || myField.selectionStart == '0') {
    var startPos = myField.selectionStart, endPos = myField.selectionEnd;
    block = myField.value.substring(startPos,endPos);
    myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
  } else {
    myField.value += myValue;
  }
  caretPos = myField.value.indexOf('-cursor-')+block.length;
  myField.value = myField.value.replace('-cursor-', block);
  setCsr(myField,caretPos);
};

function insert2Area(what) {
  var activeTextareaIdFromHref = gup('textid',what);
  if(activeTextareaIdFromHref) { activeTextareaId = $("#"+gup('textid',what)).id; };
  if(activeTextareaId) { insertAtCursor($("#"+activeTextareaId), gup('tag',what)); };
};

//---textarea - text2curosr init function
function initInsertToTextarea() {
  setListeners('clickDaTag','click',function(evt) { insert2Area(this.href); evt.preventDefault(); });
  setListeners('draftable','click',function(evt) { activeTextareaId = this.id; evt.preventDefault(); });
  setListeners('submit','click',function(evt) { if(draftTimeout) { clearTimeout(draftTimeout); } });
  setListeners('toggleToolSize','click',function(evt) { 
    $("#"+gup("textid",this.href)).toggleClass(gup("class",this.href));
    $("#"+gup('toolid',this.href)).toggleClass('textareaToolboxLarge');
    evt.preventDefault(); });
}

//---drafting ----------------------------
//---textarea drafting
var arrDraft = [], //0-id, 1-lastlength
timerRunning = false,
draftTimer = 5000,
draftTimeout, //---with this timer could be stopped
draftTimeoutCounter = 0; //--if bigger than 3 stop timer - reset every time save function is called

function draftDoSave() { 
  var x, arrDraftLength = arrDraft.length; 
  for (x=0;x<arrDraftLength;x++) {
    taText = $("#"+arrDraft[x][0]).attr('value');
    if(taText.length != arrDraft[x][1] && taText.length > 0) {
        addXMLRequest('place', $("#"+arrDraft[x][0]).attr("id"));
        addXMLRequest('text', taText);
        addXMLRequest('call', 'draftSaved');
        sendAjax('draft-save');        
        arrDraft[x][1] = taText.length;
        draftTimeoutCounter = 0;
    };
  };
};

function draftGetLength(textareaInst) {
  return textareaInst.value.length;
};

//set class - is saved - green - callback function from xajax
function draftSaved(textareaId) {
  $("#"+textareaId).removeClass('draftNotSave');
  $("#"+textareaId).addClass('draftSave');
};

//check all ta and set not saved class if there is a difference between string length
function draftIsSavedSetClass() {
  var x, ta, arrDraftLength = arrDraft.length;
  for (x=0;x<arrDraftLength;x++) {
    if(arrDraft[x][1] != $("#"+arrDraft[x][0]).attr('value').length) {
      $("#"+arrDraft[x][0]).removeClass('draftSave');
      $("#"+arrDraft[x][0]).addClass('draftNotSave');
    }
  } 
};

//register textarea
function setDraftElement(tarea) { 
  var x, add=1, arrDraftLength = arrDraft.length;
  for (x=0;x<arrDraftLength;x++) { 
    if(arrDraft[x][0] == $(tarea).attr('id')) {
      add=0;
    }; 
  }; 
  if(add==1) { 
    arrDraft.push([$(tarea).attr('id'),0]); 
  }; 
};

//initiate timer, check not saved
function startDraftTimer() {
  if(timerRunning === false) {
    draftTimeout = setTimeout(draftTimerHandler, draftTimer);
    timerRunning = true;
  }
  draftIsSavedSetClass();
};

function draftTimerHandler() {
  draftDoSave();
  draftTimeout = setTimeout(draftTimerHandler, draftTimer);
  if(draftTimeoutCounter > 3) {
    draftTimeoutCounter = 0;
    timerRunning = false;
    clearTimeout(draftTimeout);
  }
};

//register ib keyup
function draftEventHandler() { setDraftElement(this); startDraftTimer(); };
//---end-drafting------------------------

//---popup opening
function openPopup(href) { window.open(href,'fpopup','scrollbars='+gup("scrollbars",href)+',toolbar='+gup("toolbar",href)+',menubar='+gup("menubar",href)+',status='+gup("status",href)+',resizable='+gup("resizable",href)+',width='+gup("width",href)+',height='+gup("height",href)+''); };
//---util funcions
function setListeners(className,eventName,functionDefinition) {
  $("."+className).unbind(eventName, functionDefinition);
  $("."+className).bind(eventName, functionDefinition);
};
function gup(name, url) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp( "[\\?&]"+name+"=([^&#]*)" ), results = regex.exec( url );
  return (results === null)?(0):(results[1]);
};

//---ajax request functions
function switchFriendRequest(evt) {
    evt.preventDefault();
    var data = gup('d',$(this).attr("href"));
    var dataArr = data.split(';');
    for(var i=0;i<dataArr.length;i++) {
    var valueArr = dataArr[i].split(':');
      addXMLRequest(valueArr[0], valueArr[1]);
    }
    addXMLRequest('result', $(this).attr("id"));
    addXMLRequest('resultProperty', 'html');
    addXMLRequest('call', 'initSwitchFriend');
    sendAjax(gup('m',$(this).attr("href")));
}

//---send and process ajax request
function sendAjax(action) {
  var data = getXMLRequest();
  $.ajax({
			type: "POST", url: "index.php", dataType:'xml', dataProcess:false, cache:false, data: "m="+action+"-x&d=" + data,
			complete: function(data){
        $(data.responseXML).find("Item").each(function() {
          var item = $(this);
          switch(item.attr('property')) {
            case 'callback':
              eval( item.text() + "( data.responseText );" );
            break;
            case 'call':
              eval( item.text() + "();" );
            break;
            case 'html':
              eval( '$("#'+item.attr('target')+'").' + item.attr('property') + '( item.text() );' );
            break;
            default:
              eval( '$("#'+item.attr('target')+'").attr("' + item.attr('property') + '", item.text());' );
          }
        });
			}
		 });
}

//---build xml request
var xmlArray = [];
var xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
function resetXMLRequest() {
  xmlArray = []
}
function addXMLRequest(key,value) {
  var str = xmlStr;
  str = str.replace('{KEY}',key);
  str = str.replace('{DATA}',value);
  xmlArray.push(str);
}
function getXMLRequest() {
  var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>';
  resetXMLRequest();
  return str;
}