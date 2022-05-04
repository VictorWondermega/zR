
// version: 1

// ザガタ。六 /////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

function zR(za,a,n) {
	/* Zagata.Request */

	this.za = (typeof(za)=='undefined')?false:za; // core
	var a = (typeof(a)=='undefined')?false:a; // attr
	this.n = (typeof(n)=='undefined')?'zR':n; // name

	this.proc = function() {
		// print("Content-type: text/text;charset=utf-8\n");
		var srv = {'SCRIPT_FILENAME':'','QUERY_STRING':'','HTTP_ACCEPT_LANGUAGE':'','SERVER_PROTOCOL':'','HTTPS':'','HTTP_HOST':'','HTTP_USER_AGENT':'','HTTP_ACCEPT':'','HTTP_ACCEPT_ENCODING':'','REMOTE_ADDR':'','SCRIPT_FILENAME':'','REQUEST_URI':''};
		var re = new Object();
		
		var url = window.location.href;
		// url = (url.indexOf('?')>0)?url.substring(url.indexOf('?')+1):url.split('/').splice(3).join('/');
		url = url.split('/').splice(3).join('/');

		url = this.replaceAll((new Array('?','&','=','//')), '/', url);
		tmp = this.utm_param(url);
		url = tmp.url; utm = tmp.utm;		
		
		// _SERVER (~)
		re.https = (window.location.protocol=='https:')?'https':'http';
		re.host = window.location.host;
		re.uag = navigator.userAgent;
		re.accept = ''; // HTTP_ACCEPT (?)
		re.ulng = navigator.languages;
		re.gzip = ''; // HTTP_ACCEPT_ENCODING (?)
		re.uip = ''; // (?)
		re.url = url;
		re.utm = utm;
		re.file = window.location.pathname; // (?)
		re.rq = this.getRq(); // ( REQUEST_URI )
		
		var fle = re.file.replace('\\','/').split('/').slice(-2).join('/');
		re['base'] = '/'+fle+'?';
		
		// _COOKIE
		var tmp = document.cookie.split(';');
		for(i=0;i<tmp.length;i++) { if(tmp[i].indexOf('=')>-1) {
			tmp[i] = tmp[i].replace(/^\s+|\s+$/g, '').split('=');
			re[tmp[i][0]] = tmp[i][1];
		} else {} }
		
		// _SESSION (?)
		
		// host(?) + _GET
		tmp = re.host.split('.');
		tmp = tmp.splice(0,tmp.length-1).join('/');
		tmp = this.get_parse(tmp);
		re = this.post_implode(re,tmp[0]);
		
		if(url!='/'&&url.length>0) {
			tmp = this.get_parse(url);
			re['fullurl'] = re['url'];
			re['url'] = tmp[1];
			re['abc'] = this.filter(tmp[1].split('/'));
			re = this.post_implode(re,tmp[0]);
		} else {}

		this.za.mm('vrs',re);
		re = null; 
	};
	
	///////////////////////////////
	// funcs
	this.utm_param = function(url) {
		var vrs = url.split('/'),
			url = new Array(),
			utm = new Array();
		
		var i=0, ix = vrs.length;
		for(i;i<ix;i++) {
			if(vrs[i].indexOf('utm_')===0) {
				// utm[ vrs[i] ] = (typeof(vrs[(i+1)])!=='undefined')?vrs[(i+1)]:'';
				utm.push( vrs[i]+'='+((typeof(vrs[(i+1)])!=='undefined')?vrs[(i+1)]:'') );
				i++;
			} else {
				url.push( vrs[i] );
			}
		}
		url = url.join('/');
		utm = utm.join('&amp;');
	return {'url':url,'utm':utm};
	};
	
	this.get_parse = function(url) {
		var re = new Object();
		var nosys = new Array();
		
		if(url!='/') {
			var sys = this.za.mm('vrs')[0]['sys'];
			var v = '';
			
			for(k in sys) {
				v = sys[k]; 
				if(typeof(v)=='object') {
					if(v.constructor === Array) { // if(Array.isArray(v)) { // simple array: tuple, list
						// get variables
						for(i=0;i<v.length;i++) {
							if(('/'+url).indexOf('/'+v[i])>=0) {
								re[k] = v[i];
								url = '/'+url;
								url = url.replace( '/'+v[i], '' ).replace('//','/');
								break;
							} else {}
						}
					} else { // object, assoc
						// get parameters
						kv1 = new Array(); 
						for(kv in v) { kv1.push(kv); }
						kv = kv1.sort(function(a,b) { return a.length-b.length; } ); kv1 = null; ix = kv.length;
						var tf = '', tt = '';
						for(i=0;i<ix;i++) {
							tf = '/'+kv[i]+'/'; tt = '/{+}'+kv[i]+'/';
							url = url.replace(tf,tt,1).replace('//','/');
						}
						//url = url.split('{+}').filter(function(e) { return e!=null&&e!=''&&e!='/'; });
						url = this.filter(url.split('{+}'));
						var ix = url.length;
						for(i=0;i<ix;i++) {
							// url[i] = url[i].split('/').filter(function(e) { return e!=null&&e!=''&&e!='/'; });
							url[i] = this.filter(url[i].split('/'));
							var k = url[i][0];
							if(typeof(v[k])!='undefined') {
								url[i].shift();
								re[k] = (v[k]>1)?new Array():'';
								if(v[k]>1) {
									for(y=0;y<v[k];y++) {
										re[k].push( ((y < url[i].length)?url[i][y]:false) );
									}
									if(y < url[i].length) { url[i].splice(0,y); } else {}
								} else {
									if(v[k]==1) {
										re[k] = (url[i].length>0)?url[i].shift():false;
									} else {
										re[k]=true;
									}
								}
							} else {}
							nosys.push(url[i].join('/'));
						}
						// url = nosys.filter(function(e) { return e!=null&&e!=''&&e!='/'; }).join('/');
						url = this.filter(nosys).join('/');
						
					}
				} else {
					if(typeof(v)=='number') {
						// get other stupud things, have to delete it
						console.log(k+' '+v+' get other stupud things, have to delete it');
					} else {
						this.za.msg('err','zR','wrong parameter in sys '+k);
					}
				}
			}
		} else {}
		nosys = url;
		
	return new Array(re,nosys);
	};
	
	this.post_parse = function() {
		void(0);
	};
	
	this.post_implode = function(re,v) {
		for(k in v) {
			if(typeof(re[k])!='undefined'&&typeof(v[k])=='object') {
				re[k] = this.post_implode(re[k],v[k]);
			} else { re[k] = v[k]; }
		}
	return re;
	};
	
	this.replaceAll = function(fr,to,wh) {
		if(typeof(fr)!='object') { fr = new Array(fr); } else {}
		var i = 0, ix = fr.length;
		for(i;i<ix;i++) {
			while(wh.indexOf(fr[i])>=0) {
				wh = wh.replace(fr[i],to);
			}
		}
	return wh;
	};
	
	this.filter = function(a) {
		var re = new Array(), i = 0, ix = a.length;
		for(i=0;i<ix;i++) { if(a[i]!=null&&a[i]!=''&&a[i]!='/') {
			re.push(a[i]);
		} else {} }
	return re;
	};
	
	this.getRq = function() {
		re = window.location;
		re = re.href.substr(re.href.indexOf(re.host)+re.host.length).replace('&','&amp;');
		return re;
	};

	///////////////////////////////
	// ini
	this.proc();
};

////////////////////////////////////////////////////////////////
if(typeof(zlo)=='object') {
	zlo.da('zR');
} else {
  console.log('zR');
}
