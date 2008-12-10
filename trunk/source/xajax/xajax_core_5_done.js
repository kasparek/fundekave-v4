
/*
	Class: xajax.js
	
	Contains the functions for javascript file and function
	manipulation.
*/
xajax.js = {};

/*
	Function: includeOnce
	
	Add a reference to the specified script file if one does not
	already exist in the HEAD of the current document.
	
	This will effecitvely cause the script file to be loaded in
	the browser.
	
	fileName - (string):  The URI of the file.
	
	Returns:
	
	true - The reference exists or was added.
*/
xajax.js.includeScriptOnce = function(command) {
	command.fullName = 'includeScriptOnce';
	var fileName = command.data;
	// Check for existing script tag for this file.
	var oDoc = xajax.config.baseDocument;
    var loadedScripts = oDoc.getElementsByTagName('script');
	var iLen = loadedScripts.length;
    for (var i = 0; i < iLen; ++i) {
		var script = loadedScripts[i];
        if (script.src) {
			if (0 <= script.src.indexOf(fileName)) {
				return true;
			}
		}
    }
	return xajax.js.includeScript(command);
};

/*
	Function: includeScript
	
	Adds a SCRIPT tag referencing the specified file.  This
	effectively causes the script to be loaded in the browser.
	
	fileName - (string):  The URI of the file.
	
	Returns:
	
	true - The reference was added.
*/
xajax.js.includeScript = function(command) {
	command.fullName = 'includeScript';
	var oDoc = xajax.config.baseDocument;
	var objHead = oDoc.getElementsByTagName('head');
	var objScript = oDoc.createElement('script');
	objScript.src = command.data;
	if ('undefined' == typeof command.type) { objScript.type = 'text/javascript'; }
	else { objScript.type = command.type; }
	if ('undefined' != typeof command.type) { objScript.setAttribute('id', command.elm_id); }
	objHead[0].appendChild(objScript);
	return true;
};

/*
	Function: removeScript
	
	Locates a SCRIPT tag in the HEAD of the document which references
	the specified file and removes it.
	
	fileName - (string):  The URI of the script file.
	unload - (function, optional):  The function to call just before
		the file reference is removed.  This can be used to clean up
		objects that reference code from that script file.
		
	Returns:
	
	true - The script was not found or was removed.
*/
xajax.js.removeScript = function(command) {
	command.fullName = 'removeScript';
	var fileName = command.data;
	var unload = command.unld;
	var oDoc = xajax.config.baseDocument;
	var loadedScripts = oDoc.getElementsByTagName('script');
	var iLen = loadedScripts.length;
	for (var i = 0; i < iLen; ++i) {
		var script = loadedScripts[i];
		if (script.src) {
			if (0 <= script.src.indexOf(fileName)) {
				if ('undefined' != typeof unload) {
					var args = {};
					args.data = unload;
					args.context = window;
					xajax.js.execute(args);
				}
				var parent = script.parentNode;
				parent.removeChild(script);
			}
		}
	}
	return true;
};

/*
	Function: sleep
	
	Causes the processing of items in the queue to be delayed
	for the specified amount of time.  This is an asynchronous
	operation, therefore, other operations will be given an
	opportunity to execute during this delay.
	
	args - (object):  The response command containing the following
		parameters.
		- args.prop: The number of 10ths of a second to sleep.
	
	Returns:
	
	true - The sleep operation completed.
	false - The sleep time has not yet expired, continue sleeping.
*/
xajax.js.sleep = function(command) {
	command.fullName = 'sleep';
	// inject a delay in the queue processing
	// handle retry counter
	if (xajax.tools.queue.retry(command, command.prop)) {
		xajax.tools.queue.setWakeup(xajax.response, 100);
		return false;
	}
	// wake up, continue processing queue
	return true;
};

/*
	Function: confirmCommands
	
	Prompt the user with the specified text, if the user responds by clicking
	cancel, then skip the specified number of commands in the response command
	queue.  If the user clicks Ok, the command processing resumes normal
	operation.
	
	msg - (string):  The message to display to the user.
	numberOfCommands - (integer):  The number of commands to skip if the user
		clicks Cancel.
		
	Returns:
	
	true - The operation completed successfully.
*/
xajax.js.confirmCommands = function(command) {
	command.fullName = 'confirmCommands';
	var msg = command.data;
	var numberOfCommands = command.id;
	if (false === confirm(msg)) {
		while (0 < numberOfCommands) {
			xajax.tools.queue.pop(xajax.response);
			--numberOfCommands;
		}
	}
	return true;
};

/*
	Function: execute
	
	Execute the specified string of javascript code, using the current
	script context.
	
	args - The response command object containing the following:
		- args.data: (string):  The javascript to be evaluated.
		- args.context: (object):  The javascript object that to be
			referenced as 'this' in the script.
			
	Returns:
	
	unknown - A value set by the script using 'returnValue = '
	true - If the script does not set a returnValue.
*/
xajax.js.execute = function(args) {
	args.fullName = 'execute Javascript';
	var returnValue = true;
	args.context.xajaxDelegateCall = function() {
		eval(args.data);
	};
	args.context.xajaxDelegateCall();
	return returnValue;
};

/*
	Function: waitFor
	
	Test for the specified condition, using the current script
	context; if the result is false, sleep for 1/10th of a
	second and try again.
	
	args - The response command object containing the following:
	
		- args.data: (string):  The javascript to evaluate.
		- args.prop: (integer):  The number of 1/10ths of a
			second to wait before giving up.
		- args.context: (object):  The current script context object
			which is accessable in the javascript being evaulated
			via the 'this' keyword.
	
	Returns:
	
	false - The condition evaulates to false and the sleep time
		has not expired.
	true - The condition evaluates to true or the sleep time has
		expired.
*/
xajax.js.waitFor = function(args) {
	args.fullName = 'waitFor';

	var bResult = false;
	var cmdToEval = 'bResult = (';
	cmdToEval += args.data;
	cmdToEval += ');';
	try {
		args.context.xajaxDelegateCall = function() {
			eval(cmdToEval);
		};
		args.context.xajaxDelegateCall();
	} catch (e) {
	}
	if (false === bResult) {
		// inject a delay in the queue processing
		// handle retry counter
		if (xajax.tools.queue.retry(args, args.prop)) {
			xajax.tools.queue.setWakeup(xajax.response, 100);
			return false;
		}
		// give up, continue processing queue
	}
	return true;
};

/*
	Function: call
	
	Call a javascript function with a series of parameters using 
	the current script context.
	
	args - The response command object containing the following:
		- args.data: (array):  The parameters to pass to the function.
		- args.func: (string):  The name of the function to call.
		- args.context: (object):  The current script context object
			which is accessable in the function name via the 'this'
			keyword.
			
	Returns:
	
	true - The call completed successfully.
*/
xajax.js.call = function(args) {
	args.fullName = 'call js function';
	
	var parameters = args.data;
	
	var scr = [];
	scr.push(args.func);
	scr.push('(');
	if ('undefined' != typeof parameters) {
		if ('object' == typeof parameters) {
			var iLen = parameters.length;
			if (0 < iLen) {
				scr.push('parameters[0]');
				for (var i = 1; i < iLen; ++i) {
					scr.push(', parameters[' + i + ']');
				}
			}
		}
	}
	scr.push(');');
	args.context.xajaxDelegateCall = function() {
		eval(scr.join(''));
	};
	args.context.xajaxDelegateCall();
	return true;
};

/*
	Function: setFunction

	Constructs the specified function using the specified javascript
	as the body of the function.
	
	args - The response command object which contains the following:
	
		- args.func: (string):  The name of the function to construct.
		- args.data: (string):  The script that will be the function body.
		- args.context: (object):  The current script context object
			which is accessable in the script name via the 'this' keyword.
			
	Returns:
	
	true - The function was constructed successfully.
*/
xajax.js.setFunction = function(args) {
	args.fullName = 'setFunction';

	var code = [];
	code.push(args.func);
	code.push(' = function(');
	if ('object' == typeof args.prop) {
		var separator = '';
		for (var m in args.prop) {
			code.push(separator);
			code.push(args.prop[m]);
			separator = ',';
		}
	} else { code.push(args.prop); }
	code.push(') { ');
	code.push(args.data);
	code.push(' }');
	args.context.xajaxDelegateCall = function() {
		eval(code.join(''));
	};
	args.context.xajaxDelegateCall();
	return true;
};

/*
	Function: wrapFunction
	
	Construct a javascript function which will call the original function with 
	the same name, potentially executing code before and after the call to the
	original function.
	
	args - (object):  The response command object which will contain 
		the following:
		
		- args.func: (string):  The name of the function to be wrapped.
		- args.prop: (string):  List of parameters used when calling the function.
		- args.data: (array):  The portions of code to be called before, after
			or even between calls to the original function.
		- args.context: (object):  The current script context object which is 
			accessable in the function name and body via the 'this' keyword.
			
	Returns:
	
	true - The wrapper function was constructed successfully.
*/
xajax.js.wrapFunction = function(args) {
	args.fullName = 'wrapFunction';

	var code = [];
	code.push(args.func);
	code.push(' = xajax.js.makeWrapper(');
	code.push(args.func);
	code.push(', args.prop, args.data, args.type, args.context);');
	args.context.xajaxDelegateCall = function() {
		eval(code.join(''));
	};
	args.context.xajaxDelegateCall();
	return true;
};

/*
	Function: makeWrapper
	
	Helper function used in the wrapping of an existing javascript function.
	
	origFun - (string):  The name of the original function.
	args - (string):  The list of parameters used when calling the function.
	codeBlocks - (array):  Array of strings of javascript code to be executed
		before, after and perhaps between calls to the original function.
	returnVariable - (string):  The name of the variable used to retain the
		return value from the call to the original function.
	context - (object):  The current script context object which is accessable
		in the function name and body via the 'this' keyword.
		
	Returns:
	
	object - The complete wrapper function.
*/
xajax.js.makeWrapper = function(origFun, args, codeBlocks, returnVariable, context) {
	var originalCall = '';
	if (0 < returnVariable.length) {
		originalCall += returnVariable;
		originalCall += ' = ';
	}
	originalCall += 	'origFun(';
	originalCall += args;
	originalCall += '); ';
	
	var code = 'wrapper = function(';
	code += args;
	code += ') { ';
	
	if (0 < returnVariable.length) {
		code += ' var ';
		code += returnVariable;
		code += ' = null;';
	}
	var separator = '';
	var bLen = codeBlocks.length;
	for (var b = 0; b < bLen; ++b) {
		code += separator;
		code += codeBlocks[b];
		separator = originalCall;
	}
	if (0 < returnVariable.length) {
		code += ' return ';
		code += returnVariable;
		code += ';';
	}
	code += ' } ';
	
	var wrapper = null;
	context.xajaxDelegateCall = function() {
		eval(code);
	};
	context.xajaxDelegateCall();
	return wrapper;
};