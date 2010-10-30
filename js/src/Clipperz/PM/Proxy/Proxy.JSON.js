if (typeof(Clipperz) == 'undefined') { Clipperz = {}; }
if (typeof(Clipperz.PM) == 'undefined') { Clipperz.PM = {}; }

//=============================================================================

Clipperz.PM.Proxy.JSON = function(args) {
	Clipperz.PM.Proxy.JSON.superclass.constructor.call(this, args);

	this._url = args.url || Clipperz.Base.exception.raise('MandatoryParameter');
	
	return this;
}

YAHOO.extendX(Clipperz.PM.Proxy.JSON, Clipperz.PM.Proxy, {

	'toString': function() {
		return "Clipperz.PM.Proxy.JSON";
	},

	//=========================================================================

	'url': function () {
		return this._url;
	},

	//=========================================================================
	
	'sendMessage': function(aFunctionName, someParameters) {
		var	deferredResult;
		var parameters;
		
		parameters = {
			method: aFunctionName,
//			version: someParameters['version'],
//			message: someParameters['message'],
			parameters: Clipperz.Base.serializeJSON(someParameters)
		};

		deferredResult = new MochiKit.Async.Deferred();
		deferredResult.addCallback(function (aValue) {
			MochiKit.Signal.signal(Clipperz.Signal.NotificationCenter, 'remoteRequestSent');
			return aValue;
		});
		deferredResult.addCallback(MochiKit.Async.doXHR, this.url(), {
			method:'POST',
			sendContent:MochiKit.Base.queryString(parameters),
			headers:{"Content-Type":"application/x-www-form-urlencoded"}
		});
		deferredResult.addCallback(function (aValue) {
			MochiKit.Signal.signal(Clipperz.Signal.NotificationCenter, 'remoteRequestReceived');
			return aValue;
		});
//		deferredResult.addCallback(MochiKit.Async.evalJSONRequest);
		deferredResult.addCallback(MochiKit.Base.itemgetter('responseText'));
		deferredResult.addCallback(Clipperz.Base.evalJSON);
		deferredResult.addCallback(function (someValues) {
			if (someValues['result'] == 'EXCEPTION') {
				throw someValues['message'];
			}
			
			return someValues;
		})
//			return MochiKit.Base.evalJSON(req.responseText);
		deferredResult.callback();

		return deferredResult;
	},

	//=========================================================================
	__syntaxFix__: "syntax fix"
	
});
