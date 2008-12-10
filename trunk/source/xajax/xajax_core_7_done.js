
/*
	Class: xajax.events
*/
xajax.events = {};

/*
	Function: setEvent
	
	Set an event handler.
	
	element - (string or object):  The name of, or the object itself.
	event - (string):  The name of the event to set.
	code - (string):  The javascript code to be assigned to this event.
	
	Returns:
	
	true - The operation completed successfully.
*/
xajax.events.setEvent = function(command) {
	command.fullName = 'addEvent';
	var element = command.id;
	var sEvent = command.prop;
	var code = command.data;
	if ('string' == typeof element) {
		element = xajax.$(element);
	}
	sEvent = xajax.tools.addOnPrefix(sEvent);
	code = xajax.tools.doubleQuotes(code);
	eval('element.' + sEvent + ' = function() { ' + code + '; }');
	return true;
};

/*
	Function: addHandler
	
	Add an event handler to the specified element.
	
	element - (string or object):  The name of, or the element itself
		which will have the event handler assigned.
	sEvent - (string):  The name of the event.
	fun - (string):  The function to be called.
	
	Returns:
	
	true - The operation completed successfully.
*/
xajax.events.addHandler = function(element, sEvent, fun) {
	if (window.addEventListener) {
		xajax.events.addHandler = function(command) {
			command.fullName = 'addHandler';
			var element = command.id;
			var sEvent = command.prop;
			var fun = command.data;
			if ('string' == typeof element) {
				element = xajax.$(element);
			}
			sEvent = xajax.tools.stripOnPrefix(sEvent);
			eval('element.addEventListener("' + sEvent + '", ' + fun + ', false);');
			return true;
		};
	} else {
		xajax.events.addHandler = function(command) {
			command.fullName = 'addHandler';
			var element = command.id;
			var sEvent = command.prop;
			var fun = command.data;
			if ('string' == typeof element) {
				element = xajax.$(element);
			}
			sEvent = xajax.tools.addOnPrefix(sEvent);
			eval('element.attachEvent("' + sEvent + '", ' + fun + ', false);');
			return true;
		};
	}
	return xajax.events.addHandler(element, sEvent, fun);
};

/*
	Function: removeHandler
	
	Remove an event handler from an element.
	
	element - (string or object):  The name of, or the element itself which
		will have the event handler removed.
	event - (string):  The name of the event for which this handler is 
		associated.
	fun - The function to be removed.
	
	Returns:
	
	true - The operation completed successfully.
*/
xajax.events.removeHandler = function(element, sEvent, fun) {
	if (window.removeEventListener) {
		xajax.events.removeHandler = function(command) {
			command.fullName = 'removeHandler';
			var element = command.id;
			var sEvent = command.prop;
			var fun = command.data;
			if ('string' == typeof element) {
				element = xajax.$(element);
			}
			sEvent = xajax.tools.stripOnPrefix(sEvent);
			eval('element.removeEventListener("' + sEvent + '", ' + fun + ', false);');
			return true;
		};
	} else {
		xajax.events.removeHandler = function(command) {
			command.fullName = 'removeHandler';
			var element = command.id;
			var sEvent = command.prop;
			var fun = command.data;
			if ('string' == typeof element) {
				element = xajax.$(element);
			}
			sEvent = xajax.tools.addOnPrefix(sEvent);
			eval('element.detachEvent("' + sEvent + '", ' + fun + ', false);');
			return true;
		};
	}
	return xajax.events.removeHandler(element, sEvent, fun);
};