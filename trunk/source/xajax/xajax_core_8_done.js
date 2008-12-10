
/*
	Class: xajax.callback
*/
xajax.callback = {};

/*
	Function: create
	
	Create a blank callback object.  Two optional arguments let you 
	set the delay time for the onResponseDelay and onExpiration events.
	
	Returns:
	
	object - The callback object.
*/
xajax.callback.create = function() {
	var xx = xajax;
	var xc = xx.config;
	var xcb = xx.callback;
	
	var oCB = {};
	oCB.timers = {};
	
	oCB.timers.onResponseDelay = xcb.setupTimer(
		(arguments.length > 0) ? arguments[0]	: xc.defaultResponseDelayTime);
	
	oCB.timers.onExpiration = xcb.setupTimer(
		(arguments.length > 1) ? arguments[1] : xc.defaultExpirationTime);

	oCB.onRequest = null;
	oCB.onResponseDelay = null;
	oCB.onExpiration = null;
	oCB.beforeResponseProcessing = null;
	oCB.onFailure = null;
	oCB.onRedirect = null;
	oCB.onSuccess = null;
	oCB.onComplete = null;
	
	return oCB;
};

/*
	Function: setupTimer
	
	Create a timer to fire an event in the future.  This will
	be used fire the onRequestDelay and onExpiration events.
	
	iDelay - (integer):  The amount of time in milliseconds to delay.
	
	Returns:
	
	object - A callback timer object.
*/
xajax.callback.setupTimer = function(iDelay)
{
	return { timer: null, delay: iDelay };
};

/*
	Function: clearTimer
	
	Clear a callback timer for the specified function.
	
	oCallback - (object):  The callback object (or objects) that
		contain the specified function timer to be cleared.
	sFunction - (string):  The name of the function associated
		with the timer to be cleared.
*/
xajax.callback.clearTimer = function(oCallback, sFunction)
{
	if ('undefined' != typeof oCallback.timers) {
		if ('undefined' != typeof oCallback.timers[sFunction]) {
			clearTimeout(oCallback.timers[sFunction].timer);
		}
	} else if ('object' == typeof oCallback) {
		var iLen = oCallback.length;
		for (var i = 0; i < iLen; ++i) {
			xajax.callback.clearTimer(oCallback[i], sFunction);
		}
	}
};

/*
	Function: execute
	
	Execute a callback event.
	
	oCallback - (object):  The callback object (or objects) which 
		contain the event handlers to be executed.
	sFunction - (string):  The name of the event to be triggered.
	args - (object):  The request object for this request.
*/
xajax.callback.execute = function(oCallback, sFunction, args) {
	if ('undefined' != typeof oCallback[sFunction]) {
		var func = oCallback[sFunction];
		if ('function' == typeof func) {
			if ('undefined' != typeof oCallback.timers[sFunction]) {
				oCallback.timers[sFunction].timer = setTimeout(function() { 
					func(args);
				}, oCallback.timers[sFunction].delay);
			}
			else {
				func(args);
			}
		}
	} else if ('object' == typeof oCallback) {
		var iLen = oCallback.length;
		for (var i = 0; i < iLen; ++i) {
			xajax.callback.execute(oCallback[i], sFunction, args);
		}
	}
};

/*
	Class: xajax.callback.global
	
	The global callback object which is active for every request.
*/
xajax.callback.global = xajax.callback.create();