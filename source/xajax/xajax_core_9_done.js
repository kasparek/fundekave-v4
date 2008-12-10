
/*
	Class: xajax
*/

/*
	Object: response
	
	The response queue that holds response commands, once received
	from the server, until they are processed.
*/	
xajax.response = xajax.tools.queue.create(xajax.config.responseQueueSize);

/*
	Object: responseSuccessCodes
	
	This array contains a list of codes which will be returned from the 
	server upon successful completion of the server portion of the 
	request.
	
	These values should match those specified in the HTTP standard.
*/
xajax.responseSuccessCodes = ['0', '200'];

// 10.4.1 400 Bad Request
// 10.4.2 401 Unauthorized
// 10.4.3 402 Payment Required
// 10.4.4 403 Forbidden
// 10.4.5 404 Not Found
// 10.4.6 405 Method Not Allowed
// 10.4.7 406 Not Acceptable
// 10.4.8 407 Proxy Authentication Required
// 10.4.9 408 Request Timeout
// 10.4.10 409 Conflict
// 10.4.11 410 Gone
// 10.4.12 411 Length Required
// 10.4.13 412 Precondition Failed
// 10.4.14 413 Request Entity Too Large
// 10.4.15 414 Request-URI Too Long
// 10.4.16 415 Unsupported Media Type
// 10.4.17 416 Requested Range Not Satisfiable
// 10.4.18 417 Expectation Failed
// 10.5 Server Error 5xx
// 10.5.1 500 Internal Server Error
// 10.5.2 501 Not Implemented
// 10.5.3 502 Bad Gateway
// 10.5.4 503 Service Unavailable
// 10.5.5 504 Gateway Timeout
// 10.5.6 505 HTTP Version Not Supported

/*
	Object: responseErrorsForAlert
	
	This array contains a list of status codes returned by
	the server to indicate that the request failed for some
	reason.
*/
xajax.responseErrorsForAlert = ['400','401','402','403','404','500','501','502','503'];

// 10.3.1 300 Multiple Choices
// 10.3.2 301 Moved Permanently
// 10.3.3 302 Found
// 10.3.4 303 See Other
// 10.3.5 304 Not Modified
// 10.3.6 305 Use Proxy
// 10.3.7 306 (Unused)
// 10.3.8 307 Temporary Redirect

/*
	Object: responseRedirectCodes
	
	An array of status codes returned from the server to
	indicate a request for redirect to another URL.
	
	Typically, this is used by the server to send the browser
	to another URL.  This does not typically indicate that
	the xajax request should be sent to another URL.
*/
xajax.responseRedirectCodes = ['301','302','307'];

/*
	Class: xajax.command
	
	The object that manages commands and command handlers.
*/
if ('undefined' == typeof xajax.command) {
	xajax.command = {};
}

/*
	Function: create
	
	Creates a new command (object) that will be populated with
	command parameters and eventually passed to the command handler.
*/
xajax.command.create = function(sequence, request, context) {
	var newCmd = {};
	newCmd.cmd = '*';
	newCmd.fullName = '* unknown command name *';
	newCmd.sequence = sequence;
	newCmd.request = request;
	newCmd.context = context;
	return newCmd;
};

/*
	Class: xajax.command.handler
	
	The object that manages command handlers.
*/
if ('undefined' == typeof xajax.command.handler) {
	xajax.command.handler = {};
}

/*
	Object: handlers
	
	An array that is used internally in the xajax.command.handler object
	to keep track of command handlers that have been registered.
*/
if ('undefined' == typeof xajax.command.handler.handlers) {
	xajax.command.handler.handlers = [];
}
/*
	Function: register
	
	Registers a new command handler.
*/
xajax.command.handler.register = function(shortName, func) {
	xajax.command.handler.handlers[shortName] = func;
};

/*
	Function: unregister
	
	Unregisters and returns a command handler.
*/
xajax.command.handler.unregister = function(shortName) {
	var func = xajax.command.handler.handlers[shortName];
	delete xajax.command.handler.handlers[shortName];
	return func;
};

/*
	Function: isRegistered
	
	Returns true or false depending on whether a command handler has 
	been created for the specified command (object).
*/
xajax.command.handler.isRegistered = function(command) {
	var shortName = command.cmd;
	if (xajax.command.handler.handlers[shortName]) {
		return true;
	}
	return false;
};

/*
	Function: call
	
	Calls the registered command handler for the specified command
	(you should always check isRegistered before calling this function)
*/
xajax.command.handler.call = function(command) {
	var shortName = command.cmd;
	return xajax.command.handler.handlers[shortName](command);
};

xajax.command.handler.register('rcmplt', function(args) {
	xajax.completeResponse(args.request);
	return true;
});

xajax.command.handler.register('as', function(args) {
	args.fullName = 'assign/clear';
	try {
		return xajax.dom.assign(args.target, args.prop, args.data);
	} catch (e) {
		// do nothing, if the debug module is installed it will
		// catch and handle the exception
	}
	return true;
});
xajax.command.handler.register('ap', function(args) {
	args.fullName = 'append';
	return xajax.dom.append(args.target, args.prop, args.data);
});
xajax.command.handler.register('pp', function(args) {
	args.fullName = 'prepend';
	return xajax.dom.prepend(args.target, args.prop, args.data);
});
xajax.command.handler.register('rp', function(args) {
	args.fullName = 'replace';
	return xajax.dom.replace(args.id, args.prop, args.data);
});
xajax.command.handler.register('rm', function(args) {
	args.fullName = 'remove';
	return xajax.dom.remove(args.id);
});
xajax.command.handler.register('ce', function(args) {
	args.fullName = 'create';
	return xajax.dom.create(args.id, args.data, args.prop);
});
xajax.command.handler.register('ie', function(args) {
	args.fullName = 'insert';
	return xajax.dom.insert(args.id, args.data, args.prop);
});
xajax.command.handler.register('ia', function(args) {
	args.fullName = 'insertAfter';
	return xajax.dom.insertAfter(args.id, args.data, args.prop);
});

xajax.command.handler.register('c:as', xajax.dom.contextAssign);
xajax.command.handler.register('c:ap', xajax.dom.contextAppend);
xajax.command.handler.register('c:pp', xajax.dom.contextPrepend);

xajax.command.handler.register('s', xajax.js.sleep);
xajax.command.handler.register('ino', xajax.js.includeScriptOnce);
xajax.command.handler.register('in', xajax.js.includeScript);
xajax.command.handler.register('rjs', xajax.js.removeScript);
xajax.command.handler.register('wf', xajax.js.waitFor);
xajax.command.handler.register('js', xajax.js.execute);
xajax.command.handler.register('jc', xajax.js.call);
xajax.command.handler.register('sf', xajax.js.setFunction);
xajax.command.handler.register('wpf', xajax.js.wrapFunction);
xajax.command.handler.register('al', function(args) {
	args.fullName = 'alert';
	alert(args.data);
	return true;
});
xajax.command.handler.register('cc', xajax.js.confirmCommands);

xajax.command.handler.register('ev', xajax.events.setEvent);

xajax.command.handler.register('ah', xajax.events.addHandler);
xajax.command.handler.register('rh', xajax.events.removeHandler);

xajax.command.handler.register('dbg', function(args) {
	args.fullName = 'debug message';
	return true;
});

/*
	Function: initializeRequest
	
	Initialize a request object, populating default settings, where
	call specific settings are not already provided.
	
	oRequest - (object):  An object that specifies call specific settings
		that will, in addition, be used to store all request related
		values.  This includes temporary values used internally by xajax.
*/
xajax.initializeRequest = function(oRequest) {
	var xx = xajax;
	var xc = xx.config;
	
	oRequest.append = function(opt, def) {
		if ('undefined' != typeof this[opt]) {
			for (var itmName in def) {
				if ('undefined' == typeof this[opt][itmName]) {
					this[opt][itmName] = def[itmName];
				}
			}
		} else { this[opt] = def; }
	};
	
	oRequest.append('commonHeaders', xc.commonHeaders);
	oRequest.append('postHeaders', xc.postHeaders);
	oRequest.append('getHeaders', xc.getHeaders);

	oRequest.set = function(option, defaultValue) {
		if ('undefined' == typeof this[option]) {
			this[option] = defaultValue;
		}
	};
	
	oRequest.set('statusMessages', xc.statusMessages);
	oRequest.set('waitCursor', xc.waitCursor);
	oRequest.set('mode', xc.defaultMode);
	oRequest.set('method', xc.defaultMethod);
	oRequest.set('URI', xc.requestURI);
	oRequest.set('httpVersion', xc.defaultHttpVersion);
	oRequest.set('contentType', xc.defaultContentType);
	oRequest.set('retry', xc.defaultRetry);
	oRequest.set('returnValue', xc.defaultReturnValue);
	oRequest.set('maxObjectDepth', xc.maxObjectDepth);
	oRequest.set('maxObjectSize', xc.maxObjectSize);
	oRequest.set('context', window);
	
	var xcb = xx.callback;
	var gcb = xcb.global;
	var lcb = xcb.create();
	
	lcb.take = function(frm, opt) {
		if ('undefined' != typeof frm[opt]) {
			lcb[opt] = frm[opt];
			lcb.hasEvents = true;
		}
		delete frm[opt];
	};
	
	lcb.take(oRequest, 'onRequest');
	lcb.take(oRequest, 'onResponseDelay');
	lcb.take(oRequest, 'onExpiration');
	lcb.take(oRequest, 'beforeResponseProcessing');
	lcb.take(oRequest, 'onFailure');
	lcb.take(oRequest, 'onRedirect');
	lcb.take(oRequest, 'onSuccess');
	lcb.take(oRequest, 'onComplete');
	
	if ('undefined' != typeof oRequest.callback) {
		if (lcb.hasEvents) {
			oRequest.callback = [oRequest.callback, lcb];
		}
	} else {
		oRequest.callback = lcb;
	}
	
	oRequest.status = (oRequest.statusMessages) ? xc.status.update() : xc.status.dontUpdate();
	
	oRequest.cursor = (oRequest.waitCursor) ? xc.cursor.update() : xc.cursor.dontUpdate();
	
	oRequest.method = oRequest.method.toUpperCase();
	if ('GET' != oRequest.method) {
		oRequest.method = 'POST';	// W3C: Method is case sensitive
	}
	oRequest.requestRetry = oRequest.retry;
	
	oRequest.append('postHeaders', {
		'content-type': oRequest.contentType
		});
		
	delete oRequest['append'];
	delete oRequest['set'];
	delete oRequest['take'];

	if ('undefined' == typeof oRequest.URI) {
		throw { code: 10005 };
	}
};

/*
	Function: processParameters
	
	Processes request specific parameters and generates the temporary 
	variables needed by xajax to initiate and process the request.
	
	oRequest - A request object, created initially by a call to
		<xajax.initializeRequest>
		
	This is called once per request; upon a request failure, this 
	will not be called for additional retries.
*/
xajax.processParameters = function(oRequest) {
	var xx = xajax;
	var xt = xx.tools;
	
	var rd = [];
	
	var separator = '';
	for (var sCommand in oRequest.functionName) {
		if ('constructor' != sCommand) {
			rd.push(separator);
			rd.push(sCommand);
			rd.push('=');
			rd.push(encodeURIComponent(oRequest.functionName[sCommand]));
			separator = '&';
		}
	}
	var dNow = new Date();
	rd.push('&xjxr=');
	rd.push(dNow.getTime());
	delete dNow;

	if (oRequest.parameters) {
		var i = 0;
		var iLen = oRequest.parameters.length;
		while (i < iLen) {
			var oVal = oRequest.parameters[i];
			if ('object' == typeof oVal && null !== oVal) {
				try {
					var oGuard = {};
					oGuard.depth = 0;
					oGuard.maxDepth = oRequest.maxObjectDepth;
					oGuard.size = 0;
					oGuard.maxSize = oRequest.maxObjectSize;
					oVal = xt._objectToXML(oVal, oGuard);
				} catch (e) {
					oVal = '';
					// do nothing, if the debug module is installed
					// it will catch the exception and handle it
				}
				rd.push('&xjxargs[]=');
				oVal = encodeURIComponent(oVal);
				rd.push(oVal);
				++i;
			} else {
				rd.push('&xjxargs[]=');
				oVal = xt._escape(oVal);
				if ('undefined' == typeof oVal || null === oVal) {
					rd.push('*');
				} else {
					var sType = typeof oVal;
					if ('string' == sType) {
						rd.push('S');
					} else if ('boolean' == sType) {
						rd.push('B');
					} else if ('number' == sType) {
						rd.push('N');
					}
					oVal = encodeURIComponent(oVal);
					rd.push(oVal);
				}
				++i;
			}
		}
	}
	
	oRequest.requestURI = oRequest.URI;
	
	if ('GET' == oRequest.method) {
		oRequest.requestURI += oRequest.requestURI.indexOf('?')== -1 ? '?' : '&';
		oRequest.requestURI += rd.join('');
		rd = [];
	}
	
	oRequest.requestData = rd.join('');
};

/*
	Function: prepareRequest
	
	Prepares the XMLHttpRequest object for this xajax request.
	
	oRequest - (object):  An object created by a call to <xajax.initializeRequest>
		which already contains the necessary parameters and temporary variables
		needed to initiate and process a xajax request.
		
	This is called each time a request object is being prepared for a 
	call to the server.  If the request is retried, the request must be
	prepared again.
*/
xajax.prepareRequest = function(oRequest) {
	var xx = xajax;
	var xt = xx.tools;
	
	oRequest.request = xt.getRequestObject();
	
	oRequest.setRequestHeaders = function(headers) {
	 	if ('object' == typeof headers) {
			for (var optionName in headers) {
				this.request.setRequestHeader(optionName, headers[optionName]);
			}
		}
	};
	oRequest.setCommonRequestHeaders = function() {
		this.setRequestHeaders(this.commonHeaders);
	};
	oRequest.setPostRequestHeaders = function() {
		this.setRequestHeaders(this.postHeaders);
	};
	oRequest.setGetRequestHeaders = function() {
		this.setRequestHeaders(this.getHeaders);
	};
	
	if ('asynchronous' == oRequest.mode) {
		// references inside this function should be expanded
		// IOW, don't use shorthand references like xx for xajax
		oRequest.request.onreadystatechange = function() {
			if (oRequest.request.readyState != 4) {
				return;
			}
			xajax.responseReceived(oRequest);
		};
		oRequest.finishRequest = function() {
			return this.returnValue;
		};
	} else {
		oRequest.finishRequest = function() {
			return xajax.responseReceived(oRequest);
		};
	}
	
	if ('undefined' != typeof oRequest.userName && 'undefined' != typeof oRequest.password) {
		oRequest.open = function() {
			this.request.open(
				this.method, 
				this.requestURI, 
				'asynchronous' == this.mode, 
				oRequest.userName, 
				oRequest.password);
		};
	} else {
		oRequest.open = function() {
			this.request.open(
				this.method, 
				this.requestURI, 
				'asynchronous' == this.mode);
		};
	}
	
	if ('POST' == oRequest.method) {	// W3C: Method is case sensitive
		oRequest.applyRequestHeaders = function() {
			this.setCommonRequestHeaders();
			try {
				this.setPostRequestHeaders();
			} catch (e) {
				this.method = 'GET';
				this.requestURI += this.requestURI.indexOf('?')== -1 ? '?' : '&';
				this.requestURI += this.requestData;
				this.requestData = '';
				if (0 === this.requestRetry) { this.requestRetry = 1; }
				throw e;
			}
		};
	} else {
		oRequest.applyRequestHeaders = function() {
			this.setCommonRequestHeaders();
			this.setGetRequestHeaders();
		};
	}
};

/*
	Function: request
*/
xajax.request = function() {
	var numArgs = arguments.length;
	if (0 === numArgs) {
		return false;
	}
	var oRequest = {};
	if (1 < numArgs) {
		oRequest = arguments[1];
	}
	oRequest.functionName = arguments[0];
	
	var xx = xajax;
	
	xx.initializeRequest(oRequest);
	xx.processParameters(oRequest);
	while (0 < oRequest.requestRetry) {
		try {
			--oRequest.requestRetry;
			xx.prepareRequest(oRequest);
			return xx.submitRequest(oRequest);
		} catch (e) {
			xajax.callback.execute(
				[xajax.callback.global, oRequest.callback], 
				'onFailure', oRequest);
			if (0 === oRequest.requestRetry) {	throw e; }
		}
	}
};

/*
	Function: call
	
	Initiates a call to the server.
	
	sFunctionName - (string):  The name of the function to execute
		on the server.
		
	oRequestOptions - (object, optional):  A request object which 
		may contain call specific parameters.  This object will be
		used by xajax to store all the request parameters as well
		as temporary variables needed during the processing of the
		request.
		
	Returns:
	
	unknown - For asynchronous calls, the return value will always
		be the value set for <xajax.config.defaultReturnValue>
*/
xajax.call = function() {
	var numArgs = arguments.length;
	if (0 === numArgs) {
		return false;
	}
	var oRequest = {};
	if (1 < numArgs) {
		oRequest = arguments[1];
	}
	oRequest.functionName = { xjxfun: arguments[0] };
	
	var xx = xajax;
	
	xx.initializeRequest(oRequest);
	xx.processParameters(oRequest);
	
	while (0 < oRequest.requestRetry) {
		try {
			--oRequest.requestRetry;
			xx.prepareRequest(oRequest);
			return xx.submitRequest(oRequest);
		} catch (e) {
			xajax.callback.execute(
				[xajax.callback.global, oRequest.callback], 
				'onFailure', oRequest);
			if (0 === oRequest.requestRetry) {
				throw e;
			}
		}
	}
};

/*
	Function: submitRequest
	
	Create a request object and submit the request using the specified
	request type; all request parameters should be finalized by this 
	point.  Upon failure of a POST, this function will fall back to a 
	GET request.
	
	oRequest - (object):  The request context object.
*/
xajax.submitRequest = function(oRequest) {
	oRequest.status.onRequest();
	
	var xcb = xajax.callback;
	var gcb = xcb.global;
	var lcb = oRequest.callback;
	
	xcb.execute([gcb, lcb], 'onResponseDelay', oRequest);
	xcb.execute([gcb, lcb], 'onExpiration', oRequest);
	xcb.execute([gcb, lcb], 'onRequest', oRequest);
	
	oRequest.open();
	oRequest.applyRequestHeaders();

	oRequest.cursor.onWaiting();
	oRequest.status.onWaiting();
	
	xajax._internalSend(oRequest);
	
	// synchronous mode causes response to be processed immediately here
	return oRequest.finishRequest();
};

/*
	Function: _internalSend
	
	This function is used internally by xajax to initiate a request to the
	server.
	
	oRequest - (object):  The request context object.
*/
xajax._internalSend = function(oRequest) {
	// this may block if synchronous mode is selected
	oRequest.request.send(oRequest.requestData);
};

/*
	Function: abortRequest
	
	Abort the request.
	
	oRequest - (object):  The request context object.
*/
xajax.abortRequest = function(oRequest)
{
	oRequest.aborted = true;
	oRequest.request.abort();
	xajax.completeResponse(oRequest);
};

/*
	Function: responseReceived
	
	Process the response.
	
	oRequest - (object):  The request context object.
*/
xajax.responseReceived = function(oRequest) {
	var xx = xajax;
	var xcb = xx.callback;
	var gcb = xcb.global;
	var lcb = oRequest.callback;
	// sometimes the responseReceived gets called when the
	// request is aborted
	if (oRequest.aborted) {
		return;
	}
	xcb.clearTimer([gcb, lcb], 'onExpiration');
	xcb.clearTimer([gcb, lcb], 'onResponseDelay');
	
	xcb.execute([gcb, lcb], 'beforeResponseProcessing', oRequest);
	
	var fProc = xx.getResponseProcessor(oRequest);
	if ('undefined' == typeof fProc) {
		xcb.execute([gcb, lcb], 'onFailure', oRequest);
		xx.completeResponse(oRequest);
		return;
	}
	
	return fProc(oRequest);
};

/*
	Function: getResponseProcessor
	
	This function attempts to determine, based on the content type of the
	reponse, what processor should be used for handling the response data.
	
	The default xajax response will be text/xml which will invoke the
	xajax xml response processor.  Other response processors may be added
	in the future.  The user can specify their own response processor on
	a call by call basis.
	
	oRequest - (object):  The request context object.
*/
xajax.getResponseProcessor = function(oRequest) {
	var fProc;
	
	if ('undefined' == typeof oRequest.responseProcessor) {
		var cTyp = oRequest.request.getResponseHeader('content-type');
		if (cTyp) {
			if (0 <= cTyp.indexOf('text/xml')) {
				fProc = xajax.responseProcessor.xml;
	//		} else if (0 <= cTyp.indexOf('application/json')) {
	//			fProc = xajax.responseProcessor.json;
			}
		}
	} else { fProc = oRequest.responseProcessor;
	}
	return fProc;
};

/*
	Function: executeCommand
	
	Perform a lookup on the command specified by the response command
	object passed in the first parameter.  If the command exists, the
	function checks to see if the command references a DOM object by
	ID; if so, the object is located within the DOM and added to the 
	command data.  The command handler is then called.
	
	If the command handler returns true, it is assumed that the command 
	completed successfully.  If the command handler returns false, then the
	command is considered pending; xajax enters a wait state.  It is up
	to the command handler to set an interval, timeout or event handler
	which will restart the xajax response processing.
	
	obj - (object):  The response command to be executed.
	
	Returns:
	
	true - The command completed successfully.
	false - The command signalled that it needs to pause processing.
*/
xajax.executeCommand = function(command) {
	if (xajax.command.handler.isRegistered(command)) {
		// it is important to grab the element here as the previous command
		// might have just created the element
		if (command.id) {
			command.target = xajax.$(command.id);
		}
		// process the command
		if (false === xajax.command.handler.call(command)) {
			xajax.tools.queue.pushFront(xajax.response, command);
			return false;
		}
	}
	return true;
};

/*
	Function: completeResponse
	
	Called by the response command queue processor when all commands have 
	been processed.
	
	oRequest - (object):  The request context object.
*/
xajax.completeResponse = function(oRequest) {
	xajax.callback.execute(
		[xajax.callback.global, oRequest.callback], 
		'onComplete', oRequest);
	oRequest.cursor.onComplete();
	oRequest.status.onComplete();
	// clean up -- these items are restored when the request is initiated
	delete oRequest['functionName'];
	delete oRequest['requestURI'];
	delete oRequest['requestData'];
	delete oRequest['requestRetry'];
	delete oRequest['request'];
	delete oRequest['set'];
	delete oRequest['open'];
	delete oRequest['setRequestHeaders'];
	delete oRequest['setCommonRequestHeaders'];
	delete oRequest['setPostRequestHeaders'];
	delete oRequest['setGetRequestHeaders'];
	delete oRequest['applyRequestHeaders'];
	delete oRequest['finishRequest'];
	delete oRequest['status'];	
	delete oRequest['cursor'];	
};

/*
	Function: $
	
	Shortcut to <xajax.tools.$>.
*/
xajax.$ = xajax.tools.$;

/*
	Function: getFormValues
	
	Shortcut to <xajax.tools.getFormValues>.
*/
xajax.getFormValues = xajax.tools.getFormValues;

/*
	Boolean: isLoaded
	
	true - xajax module is loaded.
*/
xajax.isLoaded = true;


/*
	Class: xjx
	
	Contains shortcut's to frequently used functions.
*/
xjx = {};

/*
	Function: $
	
	Shortcut to <xajax.tools.$>.
*/
xjx.$ = xajax.tools.$;

/*
	Function: getFormValues
	
	Shortcut to <xajax.tools.getFormValues>.
*/
xjx.getFormValues = xajax.tools.getFormValues;

/*
	Function: call
	
	Shortcut to <xajax.call>.
*/
xjx.call = xajax.call;

xjx.request = xajax.request;