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
	$('#calendar-inline')
	.on('show',function() {
		$('th.datepicker-switch').on('click',function(){
			calendarInlineInvalidate();
		});
		$('th.next').on('click',calendarInlineInvalidate);
		$('th.prev').on('click',calendarInlineInvalidate);
		calendarInlineInvalidate();
	}).datepicker({ date:date, language: "cs", startView: viewMode ? viewMode : 0, weekStart: 1, multidate:true})
	.on('changeYear',function(e){
		selectedDate = {year:e.date.getFullYear()};
		calendarInlineInvalidate(e);
	})
	.on('changeDate', function(e){
		selectedDate = {year:e.date.getFullYear(),month:('0' + (e.date.getMonth()+1)).slice(-2),date:('0' + e.date.getDate()).slice(-2)};
		calendarInlineInvalidate(e);
	})
	.on('changeMonth', function(e){
		selectedDate = {year:e.date.getFullYear(),month:('0' + (e.date.getMonth()+1)).slice(-2)};
		calendarInlineInvalidate(e);
	});
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
var invalidateTimeout;
function calendarInlineInvalidate(e){
	if(e && e.date) calendarDate=e.date;
	if(calendarIsInvalid) return;
	calendarIsInvalid=true;
	selectedDate.cat = gup('c',window.location.href);
	if(invalidateTimeout) clearTimeout(invalidateTimeout);
	invalidateTimeout = setTimeout(calendarInlineUpdate,100);
}
var selectedDate = {};
var calendarDate;
var calendarFirstInit=true;
var calendarDataLoaded={};
var galeryDateLoaded={};
var calendarIsInvalid=false;
function calendarInlineUpdate() {
	var year,month,day,i;
	calendarIsInvalid=false;
	var $cal = $('#calendar-inline'), viewMode = calendarViewMode();
	var galGrouped = null;
	if(!calendarFirstInit && (viewMode==2 || calendarDate)) {
		if(calendarDate) {
			year=calendarDate.getFullYear();
			month=parseInt(calendarDate.getMonth())+1;
			day=parseInt(calendarDate.getDate());
		}
		/* Disabled loading - hadrcode for galleries at the  moment not used anywhere else
		var loading = viewMode+'-'+(viewMode<2?year+(viewMode<1?'-'+month:''):'');
		if(!galeryDateLoaded[selectedDate.year] && !calendarDataLoaded[loading]) {
			Fajax.add('loading', loading);
			if(viewMode) Fajax.add('viewmode', viewMode);
			if(viewMode<1) Fajax.add('month', month);
			if(viewMode<2) Fajax.add('year', year);
			Fajax.send('calendar-show', '');
		}*/
		if(!galeryDateLoaded[selectedDate.year]) {
			galeryDateLoaded[selectedDate.year] = true;
			console.log('Updating galleries: ' + selectedDate.year);
			Fajax.add('year', selectedDate.year);
			Fajax.add('month', selectedDate.month||0);
			Fajax.add('date', selectedDate.date||0);
			Fajax.add('cat', selectedDate.cat||0);
			Fajax.add('type', 'galery');
			Fajax.send('page-listByDate', _fdk.cfg.page);
		} else {
			galGrouped = {};
			console.log('Showing galleries from cache');
			$("#pagesList").html('');
			var sDate = selectedDate.date ? parseInt(selectedDate.date) || null : null, sMonth = selectedDate.month ? parseInt(selectedDate.month) || null : null;
			for(i=0;i<galeryDateLoaded[selectedDate.year].length;i++) {
				var page = galeryDateLoaded[selectedDate.year][i];
				if(viewMode==1 || (sMonth===null || sMonth == parseInt($(page).data('month'))) && (sDate===null || sDate == parseInt($(page).data('date')))
					//&& (!selectedDate.cat || selectedDate.car == $(page).data('category'))
					) {
					$("#pagesList").append(page);
				}
				//group to show in calendar
				if(viewMode === 0) { //day view
					if(selectedDate.month == $(page).data('month')) {
						if(!galGrouped[$(page).data('date')]) galGrouped[$(page).data('date')]=0;
						galGrouped[$(page).data('date')]++;
					}
				} else if(viewMode === 1) { //month view
					if(!galGrouped[$(page).data('month')]) galGrouped[$(page).data('month')]=0;
					galGrouped[$(page).data('month')]++;
				}
			}
			$("#pagesList img").unveil();
		}
	}
	calendarFirstInit=false;

	//reset calendar
	$("td.day",$cal[0]).each(function(){$(this).removeClass('active');});
	$("span.month",$cal[0]).each(function(){$(this).removeClass('active');});
	$("span.year",$cal[0]).each(function(){$(this).removeClass('active');});

var el;
if(galGrouped) {
	for(var key in galGrouped) {
		if(viewMode==1) {
			el = $("span.month",$cal[0])[key-1];
		} else {
			el = $("td.day:not(.old):not(.new)",$cal[0])[key-1];
		}
		$(el).addClass('active');
		$(el).attr('title',galGrouped[key]);
	}
}

	//get all events
	var dayEvents = $(".event",$cal[0]);
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
function calendarPagesLoaded(key) {
	galeryDateLoaded[key] = $("#pagesList div.galeryPage").clone();
	$("#pagesList img").unveil();
	calendarInlineInvalidate();
}