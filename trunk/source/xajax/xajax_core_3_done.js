
/*
	Class: xajax.tools
	
	This contains utility functions which are used throughout
	the xajax core.
*/
xajax.tools = {};

/*
	Function: $

	Shorthand for finding a uniquely named element within 
	the document.
	
	sId - (string):
		The unique name of the element (specified by the 
		ID attribute), not to be confused with the name
		attribute on form elements.
		
	Returns:
	
	object - The element found or null.
	
	Note:
		This function uses the <xajax.config.baseDocument>
		which allows <xajax> to operate on the main window
		document as well as documents from contained
		iframes and child windows.
	
	See also:
		<xajax.$> and <xjx.$>
*/
xajax.tools.$ = function(sId) {
	if (!sId) {
		return null;
	}
	var oDoc = xajax.config.baseDocument;

	var obj = oDoc.getElementById(sId);
	if (obj) {
		return obj;
	}
	if (oDoc.all) {
		return oDoc.all[sId];
  }
	return obj;
};

/*
	Function arrayContainsValue
	
	Looks for a value within the specified array and, if found, 
	returns true; otherwise it returns false.
	
	array - (object):
		The array to be searched.
		
	valueToCheck - (object):
		The value to search for.
		
	Returns:
	
	true - The value is one of the values contained in the 
		array.
		
	false - The value was not found in the specified array.
*/
xajax.tools.arrayContainsValue = function(array, valueToCheck) {
	var i = 0;
	var l = array.length;
	while (i < l) {
		if (array[i] == valueToCheck) {
			return true;
		}
		++i;
	}
	return false;
};

/*
	Function: doubleQuotes
	
	Replace all occurances of the single quote character with a double
	quote character.
	
	haystack - The source string to be scanned.
	
	Returns:
	
	string - A new string with the modifications applied.
*/
xajax.tools.doubleQuotes = function(haystack) {
	return haystack.replace(new RegExp("'", 'g'), '"');
};

/*
	Function: singleQuotes
	
	Replace all occurances of the double quote character with a single
	quote character.
	
	haystack - The source string to be scanned.
	
	Returns:
	
	string - A new string with the modification applied.
*/
xajax.tools.singleQuotes = function(haystack) {
	return haystack.replace(new RegExp('"', 'g'), "'");
};

/*
	Function: _escape
	
	Determine if the specified value contains special characters and
	create a CDATA section so the value can be safely transmitted.
	
	data - (string or other):
		The source string value to be evaluated or an object of unknown
		type.
		
	Returns:
	
	string - The string value, escaped if necessary or the object provided
		if it is not a string.
		
	Note:
		When the specified object is NOT a string, the value is returned
		as is.
*/
xajax.tools._escape = function(data) {
	if ('undefined' == typeof data) {
		return data;
	}
	
	// 'object' is handled elsewhere, 
	// 'string' handled below, 
	// 'number' will be returned here
	// 'boolean' will be returned here
	if ('string' != typeof data) {
		return data;
	}
	
	var needCDATA = false;
	
	if (encodeURIComponent(data) != data) {
		needCDATA = true;
		
		var segments = data.split('<![CDATA[');
		var segLen = segments.length;
		data = [];
		for (var i = 0; i < segLen; ++i) {
			var segment = segments[i];
			var fragments = segment.split(']]>');
			var fragLen = fragments.length;
			segment = '';
			for (var j = 0; j < fragLen; ++j) {
				if (0 !== j) {
					segment += ']]]]><![CDATA[>';
				}
				segment += fragments[j];
			}
			if (0 !== i) {
				data.push('<![]]><![CDATA[CDATA[');
			}
			data.push(segment);
		}
		data = data.join('');
	}
	
	if (needCDATA) {
		data = '<![CDATA[' + data + ']]>';
	}
	
	return data;
};

/*
	Function: _objectToXML
	
	Convert a javascript object or array into XML suitable for
	transmission to the server.
	
	obj - The object or array to convert.
	
	guard - An object used to track the level of recursion
		when encoding javascript objects.  When an object
		contains a reference to it's parent and the parent
		contains a reference to the child, an infinite
		recursion will cause some browsers to crash.
		
	Returns:
	
	string - the xml representation of the object or array.
	
	See also:
	
	<xajax.config.maxObjectDepth> and <xajax.config.maxObjectSize>
*/
xajax.tools._objectToXML = function(obj, guard) {
	var aXml = [],val;
	aXml.push('<xjxobj>');
	for (var key in obj) {
		++guard.size;
		if (guard.maxSize < guard.size) {
			return aXml.join('');
		}
		if ('undefined' != typeof obj[key]) {
			if ('constructor' == key) {
				continue;
			}
			if ('function' == typeof obj[key]) {
				continue;
			}
			aXml.push('<e><k>');
			val = xajax.tools._escape(key);
			aXml.push(val);
			aXml.push('</k><v>');
			if ('object' == typeof obj[key]) {
				++guard.depth;
				if (guard.maxDepth > guard.depth) {
					try {
						aXml.push(xajax.tools._objectToXML(obj[key], guard));
					} catch (e) {
						// do nothing, if the debug module is installed
						// it will catch the exception and handle it
					}
				}
				--guard.depth;
			} else {
				val = xajax.tools._escape(obj[key]);
				if ('undefined' == typeof val || null === val) {
					aXml.push('*');
				} else {
					var sType = typeof val;
					if ('string' == sType) {
						aXml.push('S');
					} else if ('boolean' == sType) {
						aXml.push('B');
					} else if ('number' == sType){ 
						aXml.push('N');
					}
					aXml.push(val);
				}
			}
				
			aXml.push('</v></e>');
		}
	}
	aXml.push('</xjxobj>');
	
	return aXml.join('');
};

/*
	Function: _enforceDataType
	
	Ensure that the javascript variable created is of the correct data type.

	Returns:
		
		(unknown) - The value provided converted to the correct data type.
*/
xajax.tools._enforceDataType = function(value) {
	value = new String(value);
	var type = value.substr(0, 1);
	value = value.substr(1);

	if ('*' == type) {
		value = null;
	} else if ('N' == type) {
		value = value - 0;
	} else if ('B' == type) {
		value = !!value; }
//	else if ('S' == type)
//		value = new String(value);

	return value;
};

/*
	Function: _nodeToObject
	
	Deserialize a javascript object from an XML node.
	
	node - A node, likely from the xml returned by the server.
	
	Returns:
	
		object - The object extracted from the xml node.
*/
xajax.tools._nodeToObject = function(node) {
	if (null === node) {
		return '';
	}
	if ('undefined' != typeof node.nodeName) {
		if ('#cdata-section' == node.nodeName || '#text' == node.nodeName) {
			var data = '';
			do {
        if (node.data) { data += node.data; } 
      } while (node = node.nextSibling);
			return xajax.tools._enforceDataType(data);
		} else if ('xjxobj' == node.nodeName) {
			var key = null;
			var value = null;
			var data = [];
			var child = node.firstChild;
			while (child) {
				if ('e' == child.nodeName) {
					var grandChild = child.firstChild;
					while (grandChild) {
						if ('k' == grandChild.nodeName) {
							// Don't support objects here, only number, string, etc...
							key = xajax.tools._enforceDataType(grandChild.firstChild.data);
						} else if ('v' == grandChild.nodeName) {
							// Value might be object, string, number, boolean... even null or undefined
							value = xajax.tools._nodeToObject(grandChild.firstChild);
						}
						grandChild = grandChild.nextSibling;
					};
					// Allow the value to be null or undefined (or a value)
					if (null !== key) { // && null != value) {
						data[key] = value;
						key = value = null;
					}
				}
				child = child.nextSibling;
			}
			return data;
		}
	}
	
	throw { code: 10001, data: node.nodeName };
};

/*
	Function: getRequestObject
	
	Construct an XMLHttpRequest object dependent on the capabilities
	of the browser.
	
	Returns:
	
	object - Javascript XHR object.
*/
xajax.tools.getRequestObject = function() {
	if ('undefined' != typeof XMLHttpRequest) {
		xajax.tools.getRequestObject = function() {
			return new XMLHttpRequest();
		};
	} else if ('undefined' != typeof ActiveXObject) {
		xajax.tools.getRequestObject = function() {
			try {
				return new ActiveXObject('Msxml2.XMLHTTP.4.0');
			} catch (e) {
				xajax.tools.getRequestObject = function() {
					try {
						return new ActiveXObject('Msxml2.XMLHTTP');
					} catch (e2) {
						xajax.tools.getRequestObject = function() {
							return new ActiveXObject('Microsoft.XMLHTTP');
						};
						return xajax.tools.getRequestObject();
					}
				};
				return xajax.tools.getRequestObject();
			}
		};
	} else if (window.createRequest) {
		xajax.tools.getRequestObject = function() {
			return window.createRequest();
		};
	} else {
		xajax.tools.getRequestObject = function() {
			throw { code: 10002 };
		};
	}
	
	// this would seem to cause an infinite loop, however, the function should
	// be reassigned by now and therefore, it will not loop.
	return xajax.tools.getRequestObject();
};

/*
	Function: getBrowserHTML
	
	Insert the specified string of HTML into the document, then 
	extract it.  This gives the browser the ability to validate
	the code and to apply any transformations it deems appropriate.
	
	sValue - (string):
		A block of html code or text to be inserted into the
		browser's document.
		
	Returns:
	
	The (potentially modified) html code or text.
*/
xajax.tools.getBrowserHTML = function(sValue) {
	var oDoc = xajax.config.baseDocument;
	if (!oDoc.body) {
		return '';
	}
	var elWorkspace = xajax.$('xajax_temp_workspace');
	if (!elWorkspace)
	{
		elWorkspace = oDoc.createElement('div');
		elWorkspace.setAttribute('id', 'xajax_temp_workspace');
		elWorkspace.style.display = 'none';
		elWorkspace.style.visibility = 'hidden';
		oDoc.body.appendChild(elWorkspace);
	}
	elWorkspace.innerHTML = sValue;
	var browserHTML = elWorkspace.innerHTML;
	elWorkspace.innerHTML = '';	
	
	return browserHTML;
};

/*
	Function: willChange
	
	Tests to see if the specified data is the same as the current
	value of the element's attribute.
	
	element - (string or object):
		The element or it's unique name (specified by the ID attribute)
		
	attribute - (string):
		The name of the attribute.
		
	newData - (string):
		The value to be compared with the current value of the specified
		element.
		
	Returns:
	
	true - The specified value differs from the current attribute value.
	false - The specified value is the same as the current value.
*/
xajax.tools.willChange = function(element, attribute, newData) {
	if ('string' == typeof element) {
		element = xajax.$(element);
	}
	if (element) {
		var oldData;		
		eval('oldData=element.'+attribute);
		return (newData != oldData);
	}

	return false;
};

/*
	Function: getFormValues
	
	Build an associative array of form elements and their values from
	the specified form.
	
	element - (string): The unique name (id) of the form to be processed.
	disabled - (boolean, optional): Include form elements which are currently disabled.
	prefix - (string, optional): A prefix used for selecting form elements.

	Returns:
	
	An associative array of form element id and value.
*/
xajax.tools.getFormValues = function(parent) {
	var submitDisabledElements = false;
	if (arguments.length > 1 && arguments[1] === true) {
		submitDisabledElements = true;
	}
	
	var prefix = '';
	if(arguments.length > 2) {
		prefix = arguments[2];
	}
	
	if ('string' == typeof parent) {
		parent = xajax.$(parent);
	}
	
	var aFormValues = {};
	
//		JW: Removing these tests so that form values can be retrieved from a specified
//		container element like a DIV, regardless of whether they exist in a form or not.
//
//		if (parent.tagName)
//			if ('FORM' == parent.tagName.toUpperCase())
	if (parent) {
		if (parent.childNodes) {
			xajax.tools._getFormValues(aFormValues, parent.childNodes, submitDisabledElements, prefix);
		}
	}
	
	return aFormValues;
};

/*
	Function: _getFormValues
	
	Used internally by <xajax.tools.getFormValues> to recursively get the value
	of form elements.  This function will extract all form element values 
	regardless of the depth of the element within the form.
*/
xajax.tools._getFormValues = function(aFormValues, children, submitDisabledElements, prefix)
{
	var iLen = children.length;
	for (var i = 0; i < iLen; ++i) {
		var child = children[i];
		if ('undefined' != typeof child.childNodes) {
			xajax.tools._getFormValues(aFormValues, child.childNodes, submitDisabledElements, prefix);
		}
		xajax.tools._getFormValue(aFormValues, child, submitDisabledElements, prefix);
	}
};

/*
	Function: _getFormValue
	
	Used internally by <xajax.tools._getFormValues> to extract a single form value.
	This will detect the type of element (radio, checkbox, multi-select) and 
	add it's value(s) to the form values array.
*/
xajax.tools._getFormValue = function(aFormValues, child, submitDisabledElements, prefix)
{
	if (!child.name) {
		return;
	}
		
	if (child.disabled) {
		if (true === child.disabled) {
			if (false === submitDisabledElements) {
				return;
			}
		}
	}
				
	if (prefix != child.name.substring(0, prefix.length)) {
		return;
	}
		
	if (child.type) {
		if (child.type == 'radio' || child.type == 'checkbox') {
			if (false === child.checked) {
				return;
			}
		}
	}

	var name = child.name;

	var values = [];

 	if ('select-multiple' == child.type) {
 		var jLen = child.length;
 		for (var j = 0; j < jLen; ++j) {
 			var option = child.options[j];
 			if (true === option.selected) {
 				values.push(option.value);
 			}
 		}
 	} else {
 		values = child.value;
 	}

	var keyBegin = name.indexOf('[');
	if (0 <= keyBegin) {
		var n = name;
		var k = n.substr(0, n.indexOf('['));
		var a = n.substr(n.indexOf('['));
		if (typeof aFormValues[k] == 'undefined') {
			aFormValues[k] = [];
		}
		var p = aFormValues; // pointer reset
		while (a.length !== 0) {
			var sa = a.substr(0, a.indexOf(']')+1);
			
			var lk = k; //save last key
			var lp = p; //save last pointer
			
			a = a.substr(a.indexOf(']')+1);
			p = p[k];
			k = sa.substr(1, sa.length-2);
			if (k === '') {
				if ('select-multiple' == child.type) {
					k = lk; //restore last key
					p = lp;
				} else {
					k = p.length;
				}
			}
			if (typeof p[k] == 'undefined') {
				p[k] = []; 
			}
		}
		p[k] = values;
	} else {
		aFormValues[name] = values;
	}
};

/*
	Function: stripOnPrefix
	
	Detect, and if found, remove the prefix 'on' from the specified 
	string.  This is used while working with event handlers.
	
	sEventName - (string): The string to be modified.
	
	Returns:
	
	string - The modified string.
*/
xajax.tools.stripOnPrefix = function(sEventName) {
	sEventName = sEventName.toLowerCase();
	if (0 === sEventName.indexOf('on')) {
		sEventName = sEventName.replace(/on/,'');
	}
	return sEventName;
};

/*
	Function: addOnPrefix
	
	Detect, and add if not found, the prefix 'on' from the specified 
	string.  This is used while working with event handlers.
	
	sEventName - (string): The string to be modified.
	
	Returns:
	
	string - The modified string.
*/
xajax.tools.addOnPrefix = function(sEventName) {
	sEventName = sEventName.toLowerCase();
	if (0 !== sEventName.indexOf('on')) {
		sEventName = 'on' + sEventName;
	}
	return sEventName;
};

/*
	Class: xajax.tools.xml
	
	An object that contains utility function for processing
	xml response packets.
*/
xajax.tools.xml = {};

/*
	Function: parseAttributes
	
	Take the parameters passed in the command of the XML response
	and convert them to parameters of the args object.  This will 
	serve as the command object which will be stored in the 
	response command queue.
	
	child - (object):  The xml child node which contains the 
		attributes for the current response command.
		
	obj - (object):  The current response command that will have the
		attributes applied.
*/
xajax.tools.xml.parseAttributes = function(child, obj) {
	var iLen = child.attributes.length;
	for (var i = 0; i < iLen; ++i) {
		var attr = child.attributes[i];
		obj[attr.name] = attr.value;
	}
};

/*
	Function: parseChildren
	
	Parses the child nodes of the command of the response XML.  Generally,
	the child nodes contain the data element of the command; this member
	may be an object, which will be deserialized by <xajax._nodeToObject>
	
	child - (object):   The xml node that contains the child (data) for
		the current response command object.
		
	obj - (object):  The response command object.
*/
xajax.tools.xml.parseChildren = function(child, obj) {
	obj.data = '';
	if (0 < child.childNodes.length) {
		if (1 < child.childNodes.length) {
			var grandChild = child.firstChild;
			do {
				if ('#cdata-section' == grandChild.nodeName || '#text' == grandChild.nodeName) {
					obj.data += grandChild.data;
				}
			} while (grandChild = grandChild.nextSibling);
		} else {
			var grandChild = child.firstChild;
			if ('xjxobj' == grandChild.nodeName) {
				obj.data = xajax.tools._nodeToObject(grandChild);
				return;
			} else if ('#cdata-section' == grandChild.nodeName || '#text' == grandChild.nodeName) {
				obj.data = grandChild.data;
			}
		}
	} else if ('undefined' != typeof child.data) {
		obj.data = child.data;
	}
	
	obj.data = xajax.tools._enforceDataType(obj.data);
};

/*
	Function: processFragment
	
	xmlNode - (object):  The first xml node in the xml fragment.
	seq - (number):  A counter used to keep track of the sequence
		of this command in the response.
	oRet - (object):  A variable that is used to return the request
		"return value" for use with synchronous requests.
*/
xajax.tools.xml.processFragment = function(xmlNode, seq, oRet, oRequest) {
	var xx = xajax;
	var xt = xx.tools;
	while (xmlNode) {
		if ('cmd' == xmlNode.nodeName) {
			var obj = {};
			obj.fullName = '*unknown*';
			obj.sequence = seq;
			obj.request = oRequest;
			obj.context = oRequest.context;
			
			xt.xml.parseAttributes(xmlNode, obj);
			xt.xml.parseChildren(xmlNode, obj);
			
			xt.queue.push(xx.response, obj);
		} else if ('xjxrv' == xmlNode.nodeName) {
			oRet = xt._nodeToObject(xmlNode.firstChild);
		} else if ('debugmsg' == xmlNode.nodeName) {
			// txt = xt._nodeToObject(xmlNode.firstChild);
		} else { 
			throw { code: 10004, data: xmlNode.nodeName };
		}
		++seq;
		xmlNode = xmlNode.nextSibling;
	}
	return oRet;
};

/*
	Class: xajax.tools.queue
	
	This contains the code and variables for building, populating
	and processing First In Last Out (FILO) buffers.
*/
xajax.tools.queue = {};

/*
	Function: create
	
	Construct and return a new queue object.
	
	size - (integer):
		The number of entries the queue will be able to hold.
*/
xajax.tools.queue.create = function(size) {
	return {
		start: 0,
		size: size,
		end: 0,
		commands: [],
		timeout: null
	};
};

/*
	Function: retry
	
	Maintains a retry counter for the given object.
	
	obj - (object):
		The object to track the retry count for.
		
	count - (integer):
		The number of times the operation should be attempted
		before a failure is indicated.
		
	Returns:
	
	true - The object has not exhausted all the retries.
	false - The object has exhausted the retry count specified.
*/
xajax.tools.queue.retry = function(obj, count) {
	var retries = obj.retries;
	if (retries) {
		--retries;
		if (1 > retries) {
			return false;
		}
	} else {
   retries = count;
  }
	obj.retries = retries;
	return true;
};

/*
	Function: rewind
	
	Rewind the buffer head pointer, effectively reinserting the 
	last retrieved object into the buffer.
	
	theQ - (object):
		The queue to be rewound.
*/
xajax.tools.queue.rewind = function(theQ) {
	if (0 < theQ.start) {
		--theQ.start;
	} else {
		theQ.start = theQ.size;
	}
};

/*
	Function: setWakeup
	
	Set or reset a timeout that is used to restart processing
	of the queue.  This allows the queue to asynchronously wait
	for an event to occur (giving the browser time to process
	pending events, like loading files)
	
	theQ - (object):
		The queue to process upon timeout.
		
	when - (integer):
		The number of milliseconds to wait before starting/
		restarting the processing of the queue.
*/
xajax.tools.queue.setWakeup = function(theQ, when) {
	if (null !== theQ.timeout) {
		clearTimeout(theQ.timeout);
		theQ.timeout = null;
	}
	theQ.timout = setTimeout(function() { xajax.tools.queue.process(theQ); }, when);
};

/*
	Function: process
	
	While entries exist in the queue, pull and entry out and
	process it's command.  When a command returns false, the
	processing is halted.
	
	theQ - (object): The queue object to process.  This should
		have been crated by calling <xajax.tools.queue.create>.
	
	Returns:

	true - The queue was fully processed and is now empty.
	false - The queue processing was halted before the 
		queue was fully processed.
		
	Notes:
	
	- Use <xajax.tools.queue.setWakeup> or call this function to 
	cause the queue processing to continue.

	- This will clear the associated timeout, this function is not 
	designed to be reentrant.
	
	- When an exception is caught, do nothing; if the debug module 
	is installed, it will catch the exception and handle it.
*/
xajax.tools.queue.process = function(theQ) {
	if (null !== theQ.timeout) {
		clearTimeout(theQ.timeout);
		theQ.timeout = null;
	}
	var obj = xajax.tools.queue.pop(theQ);
	while (null !== obj) {
		try {
			if (false === xajax.executeCommand(obj)) {
				return false;
			}
		} catch (e) {
		}
		delete obj;
		
		obj = xajax.tools.queue.pop(theQ);
	}
	return true;
};

/*
	Function: push
	
	Push a new object into the tail of the buffer maintained by the
	specified queue object.
	
	theQ - (object):
		The queue in which you would like the object stored.
		
	obj - (object):
		The object you would like stored in the queue.
*/
xajax.tools.queue.push = function(theQ, obj) {
	var next = theQ.end + 1;
	if (next > theQ.size) {
		next = 0;
	}
	if (next != theQ.start) {				
		theQ.commands[theQ.end] = obj;
		theQ.end = next;
	} else {
		throw { code: 10003 };
	}
};

/*
	Function: pushFront
	
	Push a new object into the head of the buffer maintained by 
	the specified queue object.  This effectively pushes an object
	to the front of the queue... it will be processed first.
	
	theQ - (object):
		The queue in which you would like the object stored.
		
	obj - (object):
		The object you would like stored in the queue.
*/
xajax.tools.queue.pushFront = function(theQ, obj) {
	xajax.tools.queue.rewind(theQ);
	theQ.commands[theQ.start] = obj;
};

/*
	Function: pop
	
	Attempt to pop an object off the head of the queue.
	
	theQ - (object):
		The queue object you would like to modify.
		
	Returns:
	
	object - The object that was at the head of the queue or
		null if the queue was empty.
*/
xajax.tools.queue.pop = function(theQ) {
	var next = theQ.start;
	if (next == theQ.end) {
		return null;
	}
	next++;
	if (next > theQ.size) {
		next = 0;
	}
	var obj = theQ.commands[theQ.start];
	delete theQ.commands[theQ.start];
	theQ.start = next;
	return obj;
};