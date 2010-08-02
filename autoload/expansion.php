<?php

/**
	Expansion pack for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Expansion
		@version 1.3.24
**/

//! Expansion pack
class Expansion {

	//! Minimum framework version required to run
	const F3_Minimum='1.3.24';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_Form='The form field hander {@CONTEXT} is invalid',
		TEXT_Minify='Unable to minify {@CONTEXT}',
		TEXT_Timeout='Connection timed out',
		TEXT_NotArray='{@CONTEXT} is not an array';
	//@}

	//! Carriage return/line feed sequence
	const EOL="\r\n";

	/**
		Remove HTML tags (except those enumerated) to protect against
		XSS/code injection attacks
			@return mixed
			@param $_input string
			@param $_tags string
			@public
	**/
	public static function scrub($_input,$_tags=NULL) {
		if (is_array($_input))
			foreach ($_input as $_key=>$_val)
				$_input[$_key]=self::scrub($_val,$_tags);
		if (is_string($_tags))
			$_tags='<'.implode('><',explode('|',$_tags)).'>';
		return is_string($_input)?
			htmlspecialchars(
				F3::fixQuotes(strip_tags($_input,$_tags)),
				ENT_COMPAT,F3::$global['ENCODING'],FALSE
			):$_input;
	}

	/**
		Call form field handler
			@param $_fields string
			@param $_funcs mixed
			@param $_tags string
			@param $_filter integer
			@param $_options mixed
			@public
	**/
	public static function input(
		$_fields,
		$_funcs,
		$_tags=NULL,
		$_filter=FILTER_UNSAFE_RAW,
		$_options=array()) {
			$_global=&F3::$global;
			foreach (explode('|',$_fields) as $_field) {
				// Sanitize relevant globals
				$_php=$_SERVER['REQUEST_METHOD'].'|REQUEST|FILES';
				foreach (explode('|',$_php) as $_var)
					if (isset($_global[$_var][$_field]))
						$_global[$_var][$_field]=filter_var(
							self::scrub($_global[$_var][$_field],$_tags),
							$_filter,$_options
						);
				$_input=&$_global
					[isset($_global['FILES'][$_field])?'FILES':'REQUEST']
					[$_field];
				if (is_string($_funcs)) {
					// String passed
					foreach (explode('|',$_funcs) as $_func) {
						if (!is_callable($_func)) {
							// Invalid handler
							$_global['CONTEXT']=$_include;
							trigger_error(self::TEXT_Form);
						}
						else
							// Call lambda function
							call_user_func($_func,$_input,$_field);
					}
				}
				else {
					// Closure
					if (!is_callable($_funcs)) {
						// Invalid handler
						$_global['CONTEXT']=$_funcs;
						trigger_error(self::TEXT_Form);
					}
					else
						// Call lambda function
						call_user_func($_funcs,$_input,$_field);
				}
			}
	}

	/**
		Return translation table for Latin diacritics and 7-bit equivalents
			@return array
			@public
	**/
	public static function diacritics() {
		return array(
			'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Æ'=>'A',
			'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'a',
			'Þ'=>'B','þ'=>'b','Č'=>'C','Ć'=>'C','Ç'=>'C','č'=>'c','ć'=>'c',
			'ç'=>'c','Đ'=>'Dj','đ'=>'dj','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
			'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','Ì'=>'I','Í'=>'I','Î'=>'I',
			'Ï'=>'I','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','Ñ'=>'N','ñ'=>'n',
			'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O','ð'=>'o',
			'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','Ŕ'=>'R',
			'ŕ'=>'r','ß'=>'Ss','Š'=>'S','š'=>'s','Ù'=>'U','Ú'=>'U','Û'=>'U',
			'Ü'=>'U','ù'=>'u','ú'=>'u','û'=>'u','Ý'=>'Y','ý'=>'y','ý'=>'y',
			'ÿ'=>'y','Ž'=>'Z','ž'=>'z'
		);
	}

	/**
		Return an RFC 1738-compliant URL-friendly version of string
			@return string
			@param $_text string
			@param $_maxlen integer
	**/
	public static function slug($_text,$_maxlen=-1) {
		$_text=preg_replace(
			'/[^\w\.!~*\'"(),]/','-',
			trim(strtr($_text,self::diacritics()))
		);
		return $_maxlen>-1?substr($_text,0,$_maxlen):$_text;
	}

	/**
		Strip Javascript/CSS files of extraneous whitespaces and comments;
		Return combined output as a minified string
			@param $_base string
			@param $_files array
			@public
	**/
	public static function minify($_base,array $_files) {
		preg_match('/\.(js|css)*$/',$_files[0],$_ext);
		if (!$_ext[1]) {
			// Not a JavaSript/CSS file
			F3::http404();
			return;
		}
		$_type=array(
			'js'=>'application/x-javascript',
			'css'=>'text/css'
		);
		$_path=F3::$global['GUI'].F3::resolve($_base);
		foreach ($_files as $_file)
			if (!file_exists($_path.$_file)) {
				F3::$global['CONTEXT']=$_file;
				trigger_error(self::TEXT_Minify);
				return;
			}
		$_src='';
		if (PHP_SAPI!='cli' && !headers_sent())
			header(F3::HTTP_Content.': '.$_type[substr($_ext[1],0,3)].'; '.
				'charset='.F3::$global['ENCODING']);
		foreach ($_files as $_file) {
			F3::$global['PROFILE']['FILES']
				['minified'][basename($_file)]=filesize($_path.$_file);
			// Rewrite relative URLs in CSS
			$_src.=preg_replace_callback(
				'/\b(?<=url)\(([\"\'])*([^\1]+?)\1*\)/',
				function($_url) use($_path,$_file) {
					$_fdir=dirname($_file);
					$_rewrite=explode(
						'/',$_path.($_fdir!='.'?$_fdir.'/':'').$_url[2]
					);
					$_i=0;
					while ($_i<count($_rewrite))
						// Analyze each URL segment
						if ($_i>0 &&
							$_rewrite[$_i]=='..' &&
							$_rewrite[$_i-1]!='..') {
							// Simplify URL
							unset($_rewrite[$_i],$_rewrite[$_i-1]);
							$_rewrite=array_values($_rewrite);
							$_i--;
						}
						else
							$_i++;
					// Reconstruct simplified URL
					return
						'('.implode('/',array_merge($_rewrite,array())).')';
				},
				// Retrieve CSS/Javascript file
				file_get_contents($_path.$_file)
			);
		}
		$_ptr=0;
		$_dst='';
		while ($_ptr<strlen($_src)) {
			if ($_src[$_ptr]=='/') {
				// Presume it's a regex pattern
				$_regex=TRUE;
				if ($_ptr>0) {
					// Backtrack and validate
					$_ofs=$_ptr;
					while ($_ofs>0) {
						$_ofs--;
					// Pattern should be preceded by parenthesis,
					// colon or assignment operator
					if ($_src[$_ofs]=='(' || $_src[$_ofs]==':' ||
						$_src[$_ofs]=='=') {
							while ($_ptr<strlen($_src)) {
								$_str=strstr(substr($_src,$_ptr+1),'/',TRUE);
								if (!strlen($_str) && $_src[$_ptr-1]!='/' ||
									strpos($_str,"\n")!==FALSE) {
									// Not a regex pattern
									$_regex=FALSE;
									break;
								}
								$_dst.='/'.$_str;
								$_ptr+=strlen($_str)+1;
								if ($_src[$_ptr-1]!='\\' ||
									$_src[$_ptr-2]=='\\') {
										$_dst.='/';
										$_ptr++;
										break;
								}
							}
							break;
						}
						elseif ($_src[$_ofs]!="\t" && $_src[$_ofs]!=' ') {
							// Not a regex pattern
							$_regex=FALSE;
							break;
						}
					}
					if ($_regex && _ofs<1)
						$_regex=FALSE;
				}
				if (!$_regex || $_ptr<1) {
					if (substr($_src,$_ptr+1,2)=='*@') {
						// Conditional block
						$_str=strstr(substr($_src,$_ptr+3),'@*/',TRUE);
						$_dst.='/*@'.$_str.$_src[$_ptr].'@*/';
						$_ptr+=strlen($_str)+6;
					}
					elseif ($_src[$_ptr+1]=='*') {
						// Multiline comment
						$_str=strstr(substr($_src,$_ptr+2),'*/',TRUE);
						$_ptr+=strlen($_str)+4;
					}
					elseif ($_src[$_ptr+1]=='/') {
						// Single-line comment
						$_str=strstr(substr($_src,$_ptr+2),"\n",TRUE);
						$_ptr+=strlen($_str)+2;
					}
					else {
						// Division operator
						$_dst.=$_src[$_ptr];
						$_ptr++;
					}
				}
				continue;
			}
			if ($_src[$_ptr]=='\'' || $_src[$_ptr]=='"') {
				$_match=$_src[$_ptr];
				// String literal
				while ($_ptr<strlen($_src)) {
					$_str=strstr(substr($_src,$_ptr+1),$_src[$_ptr],TRUE);
					$_dst.=$_match.$_str;
					$_ptr+=strlen($_str)+1;
					if ($_src[$_ptr-1]!='\\' || $_src[$_ptr-2]=='\\') {
						$_dst.=$_match;
						$_ptr++;
						break;
					}
				}
				continue;
			}
			if (ctype_space($_src[$_ptr])) {
				$_last=substr($_dst,-1);
				$_ofs=$_ptr+1;
				while (ctype_space($_src[$_ofs]))
					$_ofs++;
				if (preg_match('/\w[\w'.
					// IE is sensitive about certain spaces in CSS
					($_ext[1]=='css'?'#*\.':'').'$]/',$_last.$_src[$_ofs]))
						$_dst.=$_src[$_ptr];
				$_ptr=$_ofs;
			}
			else {
				$_dst.=$_src[$_ptr];
				$_ptr++;
			}
		}
		echo $_dst;
	}

	/**
		Convert seconds to frequency (in words)
			@return integer
			@param $_secs string
			@public
	**/
	public static function frequency($_secs) {
		$_freq['hourly']=3600;
		$_freq['daily']=86400;
		$_freq['weekly']=604800;
		$_freq['monthly']=2592000;
		foreach ($_freq as $_key=>$_val)
			if ($_secs<=$_val)
				return $_key;
		return 'yearly';
	}

	/**
		Parse each URL recursively and generate sitemap
			@param $_url string
			@public
	**/
	public static function sitemap($_url='/') {
		$_map=&F3::$global['SITEMAP'];
		if (array_key_exists($_url,$_map) && $_map[$_url]['status']!==NULL)
			// Already crawled
			return;
		preg_match('/^http[s]*:\/\/([^\/$]+)/',$_url,$_host);
		if (!empty($_host) && $_host[1]!=$_SERVER['SERVER_NAME']) {
			// Remote URL
			$_map[$_url]['status']=FALSE;
			return;
		}
		$_state=F3::$global['QUIET'];
		F3::$global['QUIET']=TRUE;
		F3::mock('GET '.$_url);
		F3::run();
		// Check if an error occurred or no HTTP response
		if (F3::$global['ERROR'] || !F3::$global['RESPONSE']) {
			$_map[$_url]['status']=FALSE;
			// Reset error flag for next page
			unset(F3::$global['ERROR']);
			return;
		}
		$_doc=new XMLTree('1.0',F3::$global['ENCODING']);
		// Suppress errors caused by invalid HTML structures
		libxml_use_internal_errors($_ishtml);
		if ($_doc->loadHTML(F3::$global['RESPONSE'])) {
			// Valid HTML; add to sitemap
			if (!$_map[$_url]['level'])
				// Web root
				$_map[$_url]['level']=0;
			$_map[$_url]['status']=TRUE;
			$_map[$_url]['mod']=time();
			$_map[$_url]['freq']=0;
			// Cached page
			$_hash='url.'.F3::hashCode('GET '.$_url);
			$_cached=Cache::cached($_hash);
			if ($_cached) {
				$_map[$_url]['mod']=$_cached['time'];
				$_map[$_url]['freq']=$_SERVER['REQUEST_TTL'];
			}
			// Parse all links
			$_links=$_doc->getElementsByTagName('a');
			foreach ($_links as $_link) {
				$_ref=$_link->getAttribute('href');
				$_rel=$_link->getAttribute('rel');
				if (!$_ref || $_rel && preg_match('/nofollow/',$_rel))
					// Don't crawl this link!
					continue;
				if (!array_key_exists($_ref,$_map))
					$_map[$_ref]=array(
						'level'=>$_map[$_url]['level']+1,
						'status'=>NULL
					);
			}
			// Parse each link
			array_walk(array_keys($_map),'self::sitemap');
		}
		unset($_doc);
		if (!$_map[$_url]['level']) {
			// Finalize sitemap
			$_depth=1;
			while ($_ref=current($_map))
				// Find depest level while iterating
				if (!$_ref['status'])
					// Remove remote URLs and pages with errors
					unset($_map[key($_map)]);
				else {
					$_depth=max($_depth,$_ref['level']+1);
					next($_map);
				}
			// Create XML document
			$_xml=simplexml_load_string(
				'<?xml version="1.0" encoding="'.
					F3::$global['ENCODING'].'"?>'.
				'<urlset xmlns="'.
					'http://www.sitemaps.org/schemas/sitemap/0.9'.
				'"/>'
			);
			$_host='http://'.$_SERVER['SERVER_NAME'];
			foreach ($_map as $_key=>$_ref) {
				// Add new URL
				$_item=$_xml->addChild('url');
				// Add URL elements
				$_item->addChild('loc',$_host.$_key);
				$_item->addChild('lastMod',gmdate('c',$_ref['mod']));
				$_item->addChild('changefreq',
					self::frequency($_ref['freq']));
				$_item->addChild('priority',
					sprintf('%1.1f',1-$_ref['level']/$_depth));
			}
			// Send output
			F3::$global['QUIET']=$_state;
			if (PHP_SAPI!='cli' && !headers_sent())
				header(F3::HTTP_Content.': application/xhtml+xml; '.
					'charset='.F3::$global['ENCODING']);
			$_xml=dom_import_simplexml($_xml)->ownerDocument;
			$_xml->formatOutput=TRUE;
			echo $_xml->saveXML();
		}
	}

	/**
		Send HTTP/S request to another host; Forward headers received (if
		QUIET variable is FALSE) and return content; Respect HTTP 30x
		redirects if last argument is TRUE
			@return mixed
			@param $_pattern string
			@param $_query string
			@param $_reqhdrs array
			@param $_follow boolean
			@public
	**/
	public static function
		http($_pattern,$_query='',$_reqhdrs=array(),$_follow=TRUE) {
		// Check if valid route pattern
		list($_method,$_route)=F3::checkRoute($_pattern);
		// Content divider
		$_div=chr(0);
		// Determine if page is in cache
		$_hash='url.'.F3::hashCode($_pattern);
		$_cached=Cache::cached($_hash);
		if ($_cached) {
			// Retrieve from cache
			$_buffer=Cache::fetch($_hash);
			$_rcvhdrs=strstr($_buffer,$_div,TRUE);
			$_response=substr(strstr($_buffer,$_div),1);
			// Find out if cache is stale
			$_expires=NULL;
			foreach (explode(self::EOL,$_rcvhdrs) as $_hdr)
				if (preg_match('/^'.F3::HTTP_Expires.':(.+)/',
					$_hdr,$_match)) {
						$_expires=strtotime($_match[1]);
						break;
				}
			if (!is_null($_expires) && time()<$_expires) {
				// Cached page is still fresh
				foreach (explode(self::EOL,$_rcvhdrs) as $_hdr) {
					F3::$global['HEADERS'][]=$_hdr;
					if (preg_match('/'.F3::HTTP_Content.'/',$_hdr))
						// Forward HTTP header
						header($_hdr);
				}
				return $_response;
			}
		}
		$_url=parse_url($_route);
		if (!$_url['path'])
			// Set to Web root
			$_url['path']='/';
		if ($_method!='GET') {
			if ($_url['query']) {
				// Non-GET method; Query is distinct from URI
				$_query=$_url['query'];
				$_url['query']='';
			}
		}
		else {
			if ($_query) {
				// GET method; Query is integral part of URI
				$_url['query']=$_query;
				$_query='';
			}
		}
		// Set up host name and TCP port for socket connection
		if (preg_match('/https/',$_url['scheme'])) {
			if (!$_url['port'])
				$_url['port']=443;
			$_target='ssl://'.$_url['host'].':'.$_url['port'];
		}
		else {
			if (!$_url['port'])
				$_url['port']=80;
			$_target=$_url['host'].':'.$_url['port'];
		}
		$_socket=@fsockopen($_target,$_url['port'],$_errno,$_text);
		if (!$_socket) {
			// Can't establish connection
			trigger_error($_text);
			return FALSE;
		}
		// Send HTTP request
		fputs($_socket,
			$_method.' '.$_url['path'].
				($_url['query']?('?'.$_url['query']):'').' '.
					'HTTP/1.0'.self::EOL.
				F3::HTTP_Host.': '.$_url['host'].self::EOL.
				F3::HTTP_Agent.': Mozilla/5.0 ('.
					'compatible;'.F3::TEXT_AppName.' '.F3::TEXT_Version.
				')'.self::EOL.
				($_reqhdrs?
					(implode(self::EOL,$_reqhdrs).self::EOL):'').
				($_method!='GET'?(
					'Content-Type: '.
						'application/x-www-form-urlencoded'.self::EOL.
					'Content-Length: '.strlen($_query).self::EOL):'').
				F3::HTTP_AcceptEnc.': gzip'.self::EOL.
					($_cached?
						(F3::HTTP_Cache.': max-age=86400'.self::EOL):'').
				F3::HTTP_Connect.': close'.self::EOL.self::EOL.
			$_query.self::EOL.self::EOL
		);
		$_found=FALSE;
		$_expires=FALSE;
		$_gzip=FALSE;
		// Set connection timeout parameters
		stream_set_blocking($_socket,TRUE);
		stream_set_timeout($_socket,ini_get('default_socket_timeout'));
		$_info=stream_get_meta_data($_socket);
		// Get headers and response
		while (!feof($_socket) && !$_info['timed_out']) {
			$_response.=fgets($_socket,4096); // MDFK97
			$_info=stream_get_meta_data($_socket);
			if (!$_found) {
				$_rcvhdrs=strstr($_response,self::EOL.self::EOL,TRUE);
				if ($_rcvhdrs) {
					$_found=TRUE;
					if (PHP_SAPI!='cli' && !headers_sent()) {
						ob_start();
						if ($_follow &&
							preg_match('/HTTP\/1\.\d\s30\d/',$_rcvhdrs)) {
							// Redirection
							preg_match('/'.F3::HTTP_Location.
								':\s*(.+?)/',$_rcvhdrs,$_loc);
							return self::http(
								$_method.' '.$_loc[1],$_query,$_reqhdrs
							);
						}
						foreach (explode(self::EOL,$_rcvhdrs) as $_hdr) {
							F3::$global['HEADERS'][]=$_hdr;
							if (!F3::$global['QUIET'] &&
								preg_match('/'.F3::HTTP_Content.'/',$_hdr))
								// Forward HTTP header
								header($_hdr);
							elseif (preg_match('/^'.F3::HTTP_Encoding.
								':\s*.*gzip/',$_hdr))
								// Uncompress content
								$_gzip=TRUE;
							elseif (preg_match('/^'.F3::HTTP_Expires.
								':\s*.+/',$_hdr))
								// Cache this page
								$_expires=TRUE;
						}
						ob_end_flush();
						if ($_flag)
							Cache::store($_hash,$_rcvhdrs.$_div.$_response);
					}
					// Split content from HTTP response headers
					$_response=substr(
						strstr($_response,self::EOL.self::EOL),4);
				}
			}
		}
		fclose($_socket);
		if ($_info['timed_out']) {
			trigger_error(self::TEXT_Timeout);
			return FALSE;
		}
		if (PHP_SAPI!='cli' && !headers_sent()) {
			if ($_gzip)
				$_response=gzinflate(substr($_response,10));
			if ($_expires)
				Cache::store($_hash,$_rcvhdrs.$_div.$_response);
		}
		// Return content
		return $_response;
	}

	/**
		Transmit a file for downloading by HTTP client; If kilobytes per
		second is specified, output is throttled (bandwidth will not be
		controlled by default); Return TRUE if successful, FALSE otherwise;
		Support for partial downloads is indicated by third argument
			@param $_file string
			@param $_kbps integer
			@param $_partial
			@public
	**/
	public static function send($_file,$_kbps=0,$_partial=TRUE) {
		$_file=F3::resolve($_file);
		if (!file_exists($_file)) {
			F3::http404();
			return FALSE;
		}
		if (PHP_SAPI!='cli' && !F3::$global['QUIET'] && !headers_sent()) {
			header(F3::HTTP_Content.': application/octet-stream');
			header(F3::HTTP_Partial.': '.($_partial?'bytes':'none'));
			header(F3::HTTP_Disposition.': '.
				'attachment; filename='.basename($_file));
			header(F3::HTTP_Length.': '.filesize($_file));
			F3::httpCache(0);
			ob_end_flush();
		}
		$_max=ini_get('max_execution_time');
		$_ctr=1;
		$_handle=fopen($_file,'r');
		$_time=time();
		while (!feof($_handle) && !connection_aborted()) {
			if ($_kbps>0) {
				// Throttle bandwidth
				$_ctr++;
				$_elapsed=microtime(TRUE)-$_time;
				if (($_ctr/$_kbps)>$_elapsed)
					usleep(1e6*($_ctr/$_kbps-$_elapsed));
			}
			// Send 1KiB and reset timer
			echo fread($_handle,1024);
			set_time_limit($_max);
		}
		fclose($_handle);
		return TRUE;
	}

	/**
		Retrieve values from a specified column of a numeric-indexed
		framework array variable
			@return array
			@param $_name string
			@param $_col string
			@public
	**/
	public static function pick($_name,$_col) {
		$_rows=F3::get($_name);
		if (!is_array($_rows)) {
			F3::$global['CONTEXT']=$_name;
			trigger_error(self::TEXT_NotArray);
			return FALSE;
		}
		$_result=array();
		foreach ($_rows as $_row)
			$_result[]=$_row[$_col];
		return $_result;
	}

	/**
		Rotate a two-dimensional framework array variable; Replace contents
		of framework variable if flag is TRUE (default), otherwise, return
		transposed result
			@return array
			@param $_name string
			@param $_flag boolean
			@public
	**/
	public static function transpose($_name,$_flag=TRUE) {
		$_rows=F3::get($_name);
		if (!is_array($_rows)) {
			F3::$global['CONTEXT']=$_name;
			trigger_error(self::TEXT_NotArray);
			return FALSE;
		}
		foreach ($_rows as $_keyx=>$_cols)
			foreach ($_cols as $_keyy=>$_valy)
				$_result[$_keyy][$_keyx]=$_valy;
		if (!$_flag)
			return $_result;
		F3::set($_name,$_result);
	}

	/**
		Return TRUE if string is a valid e-mail address
			@return boolean
			@param $_text string
			@public
	**/
	public static function validEmail($_text) {
		return is_string(filter_var($_text,FILTER_VALIDATE_EMAIL));
	}

	/**
		Return TRUE if string is a valid URL
			@return boolean
			@param $_text string
			@public
	**/
	public static function validURL($_text) {
		return is_string(filter_var($_text,FILTER_VALIDATE_URL));
	}

	/**
		Return TRUE if string and generated CAPTCHA image are identical
			@return boolean
			@param $_text string
			@public
	**/
	public static function validCaptcha($_text) {
		$_result=FALSE;
		if (isset($_SESSION['captcha'])) {
			$_result=($_text==$_SESSION['captcha']);
			unset($_SESSION['captcha']);
		}
		return $_result;
	}

	/**
		Class constructor
			@public
	**/
	public function __construct() {
		// Prohibit use of class as an object
		F3::$global['CONTEXT']=__CLASS__;
		trigger_error(F3::TEXT_Object);
	}

	/**
		Intercept calls to undefined static methods
			@return mixed
			@param $_func string
			@param $_args array
			@public
	**/
	public static function __callStatic($_func,array $_args) {
		F3::$global['CONTEXT']=__CLASS__.'::'.$_func;
		trigger_error(F3::TEXT_Method);
	}

}

?>
