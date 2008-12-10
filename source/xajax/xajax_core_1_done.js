/*
	File: xajax_core.js
	
	This file contains the definition of the main xajax javascript core.
	
	This is the client side code which runs on the web browser or similar 
	web enabled application.  Include this in the HEAD of each page for
	which you wish to use xajax.
	
	Title: xajax core javascript library
	
	Please see <copyright.inc.php> for a detailed description, copyright
	and license information.
*/

/*
	@package xajax
	@version $Id: xajax_core_uncompressed.js 327 2007-02-28 16:55:26Z calltoconstruct $
	@copyright Copyright (c) 2005-2006 by Jared White & J. Max Wilson
	@license http://www.xajaxproject.org/bsd_license.txt BSD License
*/

/*
	Class: xajax.config
	
	This class contains all the default configuration settings.  These
	are application level settings; however, they can be overridden
	by including a xajax.config definition prior to including the
	<xajax_core.js> file, or by specifying the appropriate configuration
	options on a per call basis.
*/
if ('undefined' == typeof xajax) {
	xajax = {};
};

if ('undefined' == typeof xajax.config) {
	xajax.config = {};
};

/*
	Function: setDefault
	
	This function will set a default configuration option if it is 
	not already set.
	
	option - (string):
		The name of the option that will be set.
		
	defaultValue - (unknown):
		The value to use if a value was not already set.
*/
xajax.config.setDefault = function(option, defaultValue) {
	if ('undefined' == typeof xajax.config[option]) {
		xajax.config[option] = defaultValue;
	};
};

/*
	Object: commonHeaders
	
	An array of header entries where the array key is the header
	option name and the associated value is the value that will
	set when the request object is initialized.
	
	These headers will be set for both POST and GET requests.
*/
xajax.config.setDefault('commonHeaders', {
	'If-Modified-Since': 'Sat, 1 Jan 2000 00:00:00 GMT'
	});

/*
	Object: postHeaders
	
	An array of header entries where the array key is the header
	option name and the associated value is the value that will
	set when the request object is initialized.
*/
xajax.config.setDefault('postHeaders', {});

/*
	Object: getHeaders
	
	An array of header entries where the array key is the header
	option name and the associated value is the value that will
	set when the request object is initialized.
*/
xajax.config.setDefault('getHeaders', {});

/*
	Boolean: waitCursor
	
	true - xajax should display a wait cursor when making a request
	false - xajax should not show a wait cursor during a request
*/
xajax.config.setDefault('waitCursor', false);

/*
	Boolean: statusMessages
	
	true - xajax should update the status bar during a request
	false - xajax should not display the status of the request
*/
xajax.config.setDefault('statusMessages', false);

/*
	Object: baseDocument
	
	The base document that will be used throughout the code for
	locating elements by ID.
*/
xajax.config.setDefault('baseDocument', document);

/*
	String: requestURI
	
	The URI that requests will be sent to.
*/
xajax.config.setDefault('requestURI', xajax.config.baseDocument.URL);

/*
	String: defaultMode
	
	The request mode.
	
	'asynchronous' - The request will immediately return, the
		response will be processed when (and if) it is received.
		
	'synchronous' - The request will block, waiting for the
		response.  This option allows the server to return
		a value directly to the caller.
*/
xajax.config.setDefault('defaultMode', 'asynchronous');

/*
	String: defaultHttpVersion
	
	The Hyper Text Transport Protocol version designated in the 
	header of the request.
*/
xajax.config.setDefault('defaultHttpVersion', 'HTTP/1.1');

/*
	String: defaultContentType
	
	The content type designated in the header of the request.
*/
xajax.config.setDefault('defaultContentType', 'application/x-www-form-urlencoded');

/*
	Integer: defaultResponseDelayTime
	
	The delay time, in milliseconds, associated with the 
	<xajax.callback.global.onRequestDelay> event.
*/
xajax.config.setDefault('defaultResponseDelayTime', 1000);

/*
	Integer: defaultExpirationTime
	
	The amount of time to wait, in milliseconds, before a request
	is considered expired.  This is used to trigger the
	<xajax.callback.global.onExpiration event.
*/
xajax.config.setDefault('defaultExpirationTime', 10000);

/*
	String: defaultMethod
	
	The method used to send requests to the server.
	
	'POST' - Generate a form POST request
	'GET' - Generate a GET request; parameters are appended
		to the <xajax.config.requestURI> to form a URL.
*/
xajax.config.setDefault('defaultMethod', 'POST');	// W3C: Method is case sensitive

/*
	Integer: defaultRetry
	
	The number of times a request should be retried
	if it expires.
*/
xajax.config.setDefault('defaultRetry', 5);

/*
	Object: defaultReturnValue
	
	The value returned by <xajax.call> when in asynchronous
	mode, or when a syncrhonous call does not specify the
	return value.
*/
xajax.config.setDefault('defaultReturnValue', false);

/*
	Integer: maxObjectDepth
	
	The maximum depth of recursion allowed when serializing
	objects to be sent to the server in a request.
*/
xajax.config.setDefault('maxObjectDepth', 20);

/*
	Integer: maxObjectSize
	
	The maximum number of members allowed when serializing
	objects to be sent to the server in a request.
*/
xajax.config.setDefault('maxObjectSize', 2000);

xajax.config.setDefault('responseQueueSize', 1000);

/*
	Class: xajax.config.status
	
	Provides support for updating the browser's status bar during
	the request process.  By splitting the status bar functionality
	into an object, the xajax developer has the opportunity to
	customize the status bar messages prior to sending xajax requests.
*/
xajax.config.status = {
	/*
		Function: update
		
		Constructs and returns a set of event handlers that will be
		called by the xajax framework to set the status bar messages.
	*/
	update: function() {
		return {
			onRequest: function() {
				window.status = 'Sending Request...';
			},
			onWaiting: function() {
				window.status = 'Waiting for Response...';
			},
			onProcessing: function() {
				window.status = 'Processing...';
			},
			onComplete: function() {
				window.status = 'Done.';
			}
		};
	},
	/*
		Function: dontUpdate
		
		Constructs and returns a set of event handlers that will be
		called by the xajax framework where status bar updates
		would normally occur.
	*/
	dontUpdate: function() {
		return {
			onRequest: function() {},
			onWaiting: function() {},
			onProcessing: function() {},
			onComplete: function() {}
		};
	}
};