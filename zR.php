<?php
// version: 1
namespace za\zR;

// ザガタ。六 /////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class zR {
	/* Zagata.Request */
	private $za = null;
	public $n = '';
	
	public function proc() {
		// header('Content-type:text/plain;charset=utf-8');
		$srv = array_merge(array('SCRIPT_FILENAME'=>'','QUERY_STRING'=>'','HTTP_ACCEPT_LANGUAGE'=>'','SERVER_PROTOCOL'=>'','HTTPS'=>'','HTTP_HOST'=>'','HTTP_USER_AGENT'=>'','HTTP_ACCEPT'=>'','HTTP_ACCEPT_ENCODING'=>'','REMOTE_ADDR'=>'','SCRIPT_FILENAME'=>'','REQUEST_URI'=>''),$_SERVER);
		$tmp = explode('/',$srv['SCRIPT_FILENAME']); $tmp = array_filter($tmp); $tmp = end($tmp); // .php script

		$url = '';
		if(php_sapi_name() == 'cli-server') {
			$fle = explode('/', str_replace('\\','/',$srv['SCRIPT_FILENAME']));
			$fle = array_splice($fle,-2,2);
			$fle = '/'.implode('/',$fle);
			$url.= '/'.str_replace($fle,'',$srv['REQUEST_URI']);
		} elseif($srv['QUERY_STRING']==''&&isset($_SERVER['argv'])) {
			$tmp = array_slice($_SERVER['argv'],1); 
			$url.= implode('/',$tmp); 
			$srv['REQUEST_URI'].='/'.$url; 
		} else {
			$url.= '/'.$srv['QUERY_STRING'];
		}
		
		$url = str_replace(array('?','=','&','//'),'/',$url);			
		list($url,$utm,$get) = $this->utm_param($url);

		$ulng = array_values(array_filter(explode('/',str_replace(array(',',';'),'/',$srv['HTTP_ACCEPT_LANGUAGE']))));
		$tmp = array(); $ix = count($ulng);
		for($i=0;$i<$ix;$i++) {
			if(strpos($ulng[$i],'q=')===false) { $tmp[] = $ulng[$i]; } else {}
		}
		$ulng = $tmp;

		//  srv
		$re = array(
			'https'=>(strpos(strtolower($srv['SERVER_PROTOCOL']),'https')!==false||($srv['HTTPS']=='on'))?'https':'http',
			'host'=> $srv['HTTP_HOST'],
			'uag'=> $srv['HTTP_USER_AGENT'],
			'accept'=> $srv['HTTP_ACCEPT'],
			'ulng'=> $ulng,
			'gzip'=> (strpos(strtolower($srv['HTTP_ACCEPT_ENCODING']),'gzip')!==false)?true:false,
			'uip'=> $srv['REMOTE_ADDR'],
			'url'=> $url,
			'utm'=> $utm,
			'file'=> $srv['SCRIPT_FILENAME'],
			'rq'=> str_replace('&','&amp;',$srv['REQUEST_URI'])
		);
		if(strpos(php_sapi_name(),'cli')!==false && !isset($srv['SERVER_SOFTWARE'])) { $re['cli'] = true; } else {}
		
		$fle = explode('/', str_replace('\\','/',$re['file']));
		$re['base'] = '/'.implode('/',array_splice($fle,-2,2)).'?';
		
		// _COOKIE
		if(isset($_COOKIE)) { $re = array_merge($re, $_COOKIE); } else {}
		
		// _SESSION
		if(!headers_sent()) {
			$tmp = session_save_path();
			if(!is_dir($tmp)||(is_dir($tmp)&&!is_writable($tmp))) { $_SESSION = array(); $this->za->msg('err',$this->n.'.proc','bad dir for sessions'); } else {}
			if(!isset($_SESSION)) { ini_set('session.serialize_handler', 'php'); session_name('zs'); @session_start(); } else {}
			$re = array_merge($re, $_SESSION);
		} else {}

		// host + _GET
		$tmp = explode('.',$re['host']);
		$tmp = implode('/', array_slice($tmp,0,count($tmp)));
		$tmp = $this->get_parse($tmp); 
		$re = array_merge($re,$tmp[0]);
		
		$re['fullurl'] = $re['url'];
		if($url!='/'&&strlen($url)>0) {
			$tmp = $this->get_parse($url); 
			$re['url'] = $tmp[1];
			$re['abc'] = array_values(array_filter(explode('/',$tmp[1])));
			$re = array_merge($re,$tmp[0]);
		} else {}

		// _GET 
		if(isset($get)&&count($get)>0) { 
			$re = $this->post_implode($re, $get); 
		} else {}

		// _POST
		if(isset($_POST)&&count($_POST)>0) { 
			$re = $this->post_implode($re, $_POST); 
		} else {}

		// _FILES
		if(isset($_FILES)&&count($_FILES)>0) {
			$fls = $this->file_parse2($_FILES);
			// $this->za->dbg($fls);
			// $fls1 = $this->file_sends($fls);
			// $this->za->dbg(array($fls,$fls1));
			// $this->za->dbg(array($_FILES,$fls));
			if($fls) {
				$re = $this->post_implode($re,$fls);
			} else { }
		} else {}

		$this->za->mm('vrs',$re);

		$re = null; unset($re);
	}

	///////////////////////////////
	// funcs
	public function utm_param($url) {
		$url = str_replace('.','__dot__',$url);
		parse_str($url,$vrs); // don't like this function
		$url = array();
		$utm = array();
		$get = array();
		foreach($vrs as $k=>$v) {
			$k = str_replace('__dot__','.',$k);
			if(strpos($k,'utm_')===0) {
				$utm[$k] = $v; // $url
			} elseif(is_array($v)) {
				$get[$k] = $v;
			} else {
				$url[] = $k.(($v)?'/'.$v:'');
			}
		}
		$url = array_unique($url);
		$url = implode('/',$url);

		$utm = str_replace('&','&amp;',http_build_query($utm));
	return array($url,$utm,$get);
	}
	
	private function get_parse($url) {
		$re = array(); // arr of parameters
		$nosys = array(); // leftovers of sys extraction
		if($url!='/') {
			$sys = $this->za->mm('vrs'); $sys = $sys[0]['sys'];
			foreach($sys as $k=>$v) {
				if(is_array($v)&&is_numeric(current(array_keys($v)))) {  // simple array: tuple, list
					$ix = count($v);
					for($i=0;$i<$ix;$i++) {
						if(strpos('/'.$url.'/','/'.$v[$i].'/')!==false) {
							$re[$k] = $v[$i];
							$url = str_replace($v[$i],'','/'.$url.'/');
							$url = str_replace('//','/',$url);
							break;
						} else {}
					}
				} elseif(is_array($v)) { // object, assoc
					arsort($v); // shold change it to sort by key length (?)
					$url = $url.'/';
					foreach($v as $kk=>$vv) {
						$url = str_replace('//', '/', str_replace('/'.$kk.'/','/{+}'.$kk.'/',$url));
					}
					$url = array_values(array_filter(explode('{+}',$url)));
					$ix = count($url);
					for($i=0;$i<$ix;$i++) {
						$url[$i] = array_values(array_filter(explode('/',$url[$i])));
						if($url[$i]) {
							$k = $url[$i][0];
							if(isset($v[$k])) {
								array_shift($url[$i]);
								$re[$k] = ($v[$k]>1)?array():'';
								if($v[$k]>1) {
									for($y=0;$y<$v[$k];$y++) {
										$re[$k][] = (count($url[$i])>0)?array_shift($url[$i]):false;
									}
								} elseif($v[$k]==1) {
									$re[$k] = (count($url[$i])>0)?array_shift($url[$i]):false;
								} else {
									$re[$k] = true;
								}
							} else {}
							$nosys[] = implode('/',$url[$i]);
						} else {}
					}
					$url = implode('/',array_filter($nosys));
				} elseif(is_numeric($v)) {
					// get other stupud things, have to delete it
				} else {
					$this->za->msg('err','zR','wrong parameter in sys '+$k);
				}
			}
		} else {}
		$nosys = $url;
	return array($re,$nosys);
	}
	
	private function post_parse($url) {
		// ??? fortunately seems i don't need it here
	}
	
	private function post_implode($re,$v) {
		foreach($v as $k=>$a) {
			if(isset($re[$k])&&is_array($v[$k])) {
				$re[$k] = $this->post_implode($re[$k],$v[$k]);
			} else {
				if(isset($re[$k])) {
					$re = array_merge($re,$v);
				} else {
					if(is_string($re)) { $re = array($re); } else {} // ???
					$re[$k] = $v[$k];
				} 
			}
		}
	return $re;
	}
	
	private function file_sends($a) {

	}
	
	private function file_parse($a) {
		$re = array(); // ideal form 
		if(is_array($a)&&isset($a['tmp_name'])&&isset($a['type'])) {
			if(is_array($a['tmp_name'])) {
				foreach($a['tmp_name'] as $k1=>$v1) {
					foreach($a as $k2=>$v2) { 
						$re[$k1][$k2] = $a[$k2][$k1]; 
					}
				}
				if(is_array($re[$k1][$k2])) {
					$tmp = array();
					foreach($re as $k1=>$v1) {
						$tmp[$k1] = $this->file_parse($v1);
					}
					$re = $tmp;
				} else { }
			} else {
				$re = $a;
			}
		} elseif(is_array($a)) {
			foreach($a as $k=>$v) { $re[$k] = $this->file_parse($v); }
		} else { return $a; }
	return $re;
	}
	
	private function file_parse2($a,$b=false,$c=false) {
		if(is_array($b)) {
			if(is_array($a)) { 
				foreach($a as $k=>$v) {
					$bx = $b; $bx[] = $k;
					$a[$k] = $this->file_parse2($v,$bx,$c);
					if($a[$k]==false) { unset($a[$k]); } else {} 
				}
				return $a;
			} else {
				// get from $c values by path $b
				$ix = count($b); 
				foreach($c as $k=>$v) {
					for($i=0;$i<$ix;$i++) { $v = $v[$b[$i]]; }
					$re[$k] = $v;
				}
				return ($re['size']>0)?$re:false;
			}
		} else {
			if(is_array($a)&&isset($a['tmp_name'])&&isset($a['size'])) {
				$re = $a['tmp_name']; // tree
				$b = array(); // path of axis
				$re = $this->file_parse2($re,$b,$a);
				return $re;
			} else {
				$re = array();
				foreach($a as $k=>$v) {
					$re[$k] = $this->file_parse2($v);
				}
				return $re;
			}
		}
	}	

	/////////////////////////////// 
	// ini
	function __construct($za,$a=false,$n=false) {
		$this->za = $za;
		$this->n = (($n)?$n:'zR');
		// $this->za->msg('dbg','zR','i am '.$this->n.'(zR)');
		
		$this->proc();
	}
}

// ザガタ。六 /////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

if(class_exists('\zlo')) {
	\zlo::da('zR');
} elseif(realpath(__FILE__) == realpath($_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME'])) {
	header("content-type: text/plain;charset=utf-8");
	exit('zR');
} else {}

?>