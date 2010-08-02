<?php

/**
	I18n extension for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package I18n
		@version 1.3.21
**/

// PHP intl extension required
if (!extension_loaded('intl'))
	// Unable to continue
	return;

//! I18n extension
class I18n extends Locale {

	//! Minimum framework version required to run
	const F3_Minimum='1.3.21';

	//! Dictionary
	public static $dict=array();

	/**
		Auto-detect default locale; Override parent class
			@return boolean
			@param $_lang string
			@public
	**/
	public static function setDefault($_lang=NULL) {
		if (!$_lang) {
			$_header=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
			if (F3::exists('LANGUAGE'))
				// Framework variable defined
				$_lang=F3::get('LANGUAGE');
			elseif (isset($_header))
				// Found in HTTP header
				$_lang=self::acceptFromHttp($_header);
			else
				// Use default_locale
				$_lang=self::getDefault();
		}
		// Set default language
		$_ok=parent::setDefault($_lang);
		if ($_ok) {
			F3::set('LANGUAGE',$_lang);
			self::$dict=array();
		}
		return $_ok;
	}

	/**
		Load appropriate language dictionaries
			@public
	**/
	public static function loadDict() {
		// Build up list of languages
		$_list=array();
		foreach (func_get_args() as $_lang) {
			$_list[]=$_lang;
			$_list[]=self::getPrimaryLanguage($_lang);
		}
		// Add default language to list
		$_list[]=self::getDefault();
		$_list[]=self::getPrimaryLanguage(self::getDefault());
		// Use generic English as fallback
		$_list[]='en';
		foreach (array_reverse(array_unique($_list)) as $_dict) {
			$_file=F3::$global['DICTIONARY'].$_dict.'.php';
			if (file_exists($_file) &&
				!in_array(realpath($_file),get_included_files())) {
				$_xl8=include($_file);
				// Combine all translations
				self::$dict=array_merge(self::$dict,$_xl8);
			}
		}
	}

	/**
		Template directive handler
			@param $_tree DOMDocument
			@public
	**/
	public static function locale($_tree) {
		$_node=&$_tree->nodeptr;
		foreach ($_node->attributes as $_attr)
			$_vars[$_attr->name]=$_attr->value;
		$_vars=is_array($_vars)?array_map('F3::resolve',$_vars):array();
		if (!count(self::$dict))
			// Load default dictionary
			self::loadDict();
		$_msg=msgfmt_create(
			self::getDefault(),self::$dict[$_node->nodeValue]
		);
		$_block=$_msg?F3::xmlEncode($_msg->format($_vars),TRUE):'';
		$_len=strlen($_block);
		if ($_len) {
			$_tree->fragment->appendXML($_block);
			// Insert fragment before current node
			$_node->parentNode->
				insertBefore($_tree->fragment,$_node);
		}
	}

	/**
		Bootstrap for I18n extension
			@return boolean
			@public
	**/
	public static function onLoad() {
		F3::$global['DICTIONARY']=F3::$global['BASE'].'dict/';
		self::setDefault();
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
