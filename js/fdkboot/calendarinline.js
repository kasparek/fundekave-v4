function calendarInit() {
	if($("#calendar-inline").length === 0) return;
	if(!Lazy.load(_fdk.load.ui, calendarInit)) return;
	var date = $('#calendar-inline').data('dateset');
	var viewMode = $('#calendar-inline').data('minviewmode');
	var getDate = gup('date',window.location.href);
	if(getDate) {
		if(getDate.length==4) getDate += '-01-01';
		else if(getDate.length==7) getDate += '-01';
		date = getDate;
	}
	calendarDate = new Date(date);
	$('#calendar-inline').on('show',function(){
		$('th.datepicker-switch').on('click',calendarInlineInvalidate);
		$('th.next').on('click',calendarInlineInvalidate);
		$('th.prev').on('click',calendarInlineInvalidate);
		//$('span.year').tooltip({container: 'body',placement: 'left'});
		//$('span.month').tooltip({container: 'body',placement: 'left'});
		//$('td.day').tooltip({container: 'body',placement: 'left'});
		calendarInlineInvalidate();
	}).datepicker({ date:date, language: "cs", minViewMode: viewMode ? viewMode : 0, weekStart: 1})
	.on('changeYear',calendarInlineInvalidate)
	.on('changeDate', function(e){
		var viewMode = calendarViewMode();
		var cat = gup('c',window.location.href);
		var uri = "?k="+_fdk.cfg.page+(cat?'&c='+cat:'')+"&date="+e.date.getFullYear() 
		+ (viewMode < 2 ? '-' + ('0' + (e.date.getMonth()+1)).slice(-2) : '' )
		+ (viewMode < 1 ? '-' + ('0' + e.date.getDate()).slice(-2) : '' );
		window.location.replace(uri);
	}).on('changeMonth', calendarInlineInvalidate);
	if(date) {
		var d=date.split('-'),da=new Date(parseInt(d[0]), parseInt(d[1])-1, parseInt(d[2]));
		$('#calendar-inline').datepicker('update', da);
	}
}
function calendarViewMode() {
	var cal = $('#calendar-inline')[0];
	if($(".datepicker-months",cal).css('display')=='block') return 1;
	if($(".datepicker-years",cal).css('display')=='block') return 2;
	return 0;
}
function calendarInlineInvalidate(e){
	if(e && e.date) calendarDate=e.date;
	if(calendarIsInvalid) return;
	calendarIsInvalid=true;
	setTimeout(calendarInlineUpdate,10);
}
var calendarDate;
var calendarFirstInit=true;
var calendarDataLoaded={};
var calendarIsInvalid=false;
function calendarInlineUpdate() {
	var year,month;
	calendarIsInvalid=false;
	var $cal = $('#calendar-inline'), viewMode = calendarViewMode();
	if(!calendarFirstInit && (viewMode==2 || calendarDate)) {
		if(calendarDate) { 
			year=calendarDate.getFullYear();
			month=parseInt(calendarDate.getMonth())+1;
		}
		var loading = viewMode+'-'+(viewMode<2?year+(viewMode<1?'-'+month:''):'');
		if(!calendarDataLoaded[loading]) {
			Fajax.add('loading', loading);
			if(viewMode) Fajax.add('viewmode', viewMode);
			if(viewMode<1) Fajax.add('month', month);
			if(viewMode<2) Fajax.add('year', year);
			Fajax.send('calendar-show', '');
		} else calendarLoading=null;
	}
	calendarFirstInit=false;
	var dayEvents = $(".event",$cal[0]);
	$("td.day",$cal[0]).each(function(){$(this).removeClass('active');});
	$("span.month",$cal[0]).each(function(){$(this).removeClass('active');});
	$("span.year",$cal[0]).each(function(){$(this).removeClass('active');});
	dayEvents.each(function(){
		$("span",this).remove();
		var ed = String($(this).data('date'));
		var tooltip = $(this).html();
		if(viewMode==2) {
			$("span.year",$cal[0]).each(function(){
				$(this).removeClass('old');
				$(this).removeClass('new');
				if($(this).html()==ed) {
					$(this).addClass('active');
					$(this).attr('title',tooltip);
				}
			});
		} else if(viewMode==1) {
			var thisYear = $(".datepicker-switch",$cal[0]).html().substr(-4);
			if(ed.indexOf(thisYear)===0) {
				var edMonth = parseInt(ed.substr(5));
				var monthIndex=1;
				$("span.month",$cal[0]).each(function(){
					if(monthIndex==edMonth) {
						$(this).addClass('active');
						$(this).attr('title',tooltip);
					}
					monthIndex++;
				});
			}
		} else {
			var date = calendarDate ? calendarDate : $cal.datepicker('getDate');
			var thisDate = date.getFullYear()+'-'+('0' + (date.getMonth()+1)).slice(-2)+'-';
			if(ed.indexOf(thisDate)===0) {
				$("td.day",$cal[0]).each(function(){
					var thisDay = parseInt($(this).html());
					var edDay = parseInt(ed.substr(8));
					if(thisDay==edDay && !$(this).hasClass('new') && !$(this).hasClass('old')) {
						$(this).addClass('active');
						$(this).attr('title',tooltip);
					}
				});
			}
		}
	});
}
function datePickerInit(){
	if($(".date").length === 0) return;
	if(!Lazy.load(_fdk.load.ui, datePickerInit)) return;
	$('.date').datepicker({todayBtn: true,weekStart: 1,autoclose: true,language: "cs",calendarWeeks: true,todayHighlight: true,format: 'dd.mm.yyyy'});
}
function calendarLoaded(loading) {
calendarDataLoaded[loading]=true;
}
function calendarUpdate(data) {
	data = data.split("\n");
	for( var i in data){
		var id = $(data[i]).data('id');
		if($("#calendar-inline div.event[data-id='"+id+"']").length === 0) {
			$("#calendar-inline").append(data[i]);
		}
	}
	calendarInlineInvalidate();
}