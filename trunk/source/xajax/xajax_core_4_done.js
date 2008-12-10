
/*
	Class: xajax.responseProcessor
*/
xajax.responseProcessor = {};

/*
	Function: xml
	
	Parse the response XML into a series of commands.  The commands
	are constructed by calling <xajax.tools.xml.parseAttributes> and 
	<xajax.tools.xml.parseChildren>.
	
	oRequest - (object):  The request context object.
*/
xajax.responseProcessor.xml = function(oRequest) {
	var xx = xajax;
	var xt = xx.tools;
	var xcb = xx.callback;
	var gcb = xcb.global;
	var lcb = oRequest.callback;
	
	var oRet = oRequest.returnValue;
	
	if (xt.arrayContainsValue(xx.responseSuccessCodes, oRequest.request.status)) {
		xcb.execute([gcb, lcb], 'onSuccess', oRequest);
		var seq = 0;
		if (oRequest.request.responseXML) {
			var responseXML = oRequest.request.responseXML;
			if (responseXML.documentElement) {
				oRequest.status.onProcessing();
				
				var child = responseXML.documentElement.firstChild;
				oRet = xt.xml.processFragment(child, seq, oRet, oRequest);
			};
		}; 
		var obj = {};
		obj.fullName = 'Response Complete';
		obj.sequence = seq;
		obj.request = oRequest;
		obj.context = oRequest.context;
		obj.cmd = 'rcmplt';
		xt.queue.push(xx.response, obj);
		
		// do not re-start the queue if a timeout is set
		if (xx.response.timeout === null) {
			xt.queue.process(xx.response);
		};
	} else if (xt.arrayContainsValue(xx.responseRedirectCodes, oRequest.request.status)) {
		xcb.execute([gcb, lcb], 'onRedirect', oRequest);
		window.location = oRequest.request.getResponseHeader('location');
		xx.completeResponse(oRequest);
	} else if (xt.arrayContainsValue(xx.responseErrorsForAlert, oRequest.request.status)) {
		xcb.execute([gcb, lcb], 'onFailure', oRequest);
		xx.completeResponse(oRequest);
	};
	
	return oRet;
};