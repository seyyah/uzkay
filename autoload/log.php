<?php

/**
	Custom Log for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Log
		@version 1.3.22
**/

//! Custom log plugin
class Log {

	//! Minimum framework version required to run
	const F3_Minimum='1.3.21';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_LogOpen='Unable to open log file',
		TEXT_LogLock='Unable to gain exclusive access to log file';
	//@}

	//! Seconds before framework gives up trying to lock resource
	const LOG_Timeout=30;

	//! Maximum log file size
	const LOG_Size='2M';

	//@{
	//! Log file properties
	private $filename;
	private $handle;
	//@}

	/**
		Return TRUE if log file is locked before timer expires
			@return boolean
			@private
	**/
	private function ready() {
		$_time=microtime(TRUE);
		while (!flock($this->handle,LOCK_EX)) {
			if ((microtime(TRUE)-$_time)>self::LOG_Timeout)
				// Give up
				return FALSE;
			usleep(mt_rand(1,3000));
		}
		return TRUE;
	}

	/**
		Write specified text to log file
			@param $_text string
			@public
	**/
	public function write($_text) {
		if (!self::ready()) {
			// Lock attempt failed
			trigger_error(self::TEXT_LogLock);
			return;
		}
		$_path=F3::$global['LOGS'];
		clearstatcache();
		if (filesize($_path.$this->filename)>F3::bytes(self::LOG_Size)) {
			// Perform log rotation sequence
			if (file_exists($_path.$this->filename.'.1'))
				copy($_path.$this->filename.'.1',
					$_path.$this->filename.'.2');
			copy($_path.$this->filename,$_path.$this->filename.'.1');
			ftruncate($this->handle,0);
		}
		// Prepend text with timestamp, source IP, file name and
		// line number for tracking origin
		$_trace=debug_backtrace(FALSE);
		fwrite(
			$this->handle,
			date('r').' ['.$_SERVER['REMOTE_ADDR'].'] '.
				F3::fixSlashes($_trace[0]['file']).':'.
				$_trace[0]['line'].' '.
				preg_replace('/\s+/',' ',$_text)."\n"
		);
		flock($this->handle,LOCK_UN);
	}

	/**
		Logger constructor; requires path/file name as argument (location
		relative to path pointed to by LOGS global variable)
			@public
	**/
	public function __construct() {
		// Reconstruct arguments lost during autoload
		$_trace=debug_backtrace(FALSE);
		if (count($_trace[0]['args'])) {
			if (!file_exists(F3::$global['LOGS'])) {
				if (!is_writable(dirname(F3::$global['LOGS'])) &&
					function_exists('posix_getpwuid')) {
						$_uid=posix_getpwuid(posix_geteuid());
						self::$global['CONTEXT']=array(
							$_uid['name'],
							realpath(dirname(F3::$global['LOGS']))
						);
						trigger_error(F3::TEXT_Write);
						return;
				}
				// Create log folder
				mkdir(F3::$global['LOGS'],0755);
			}
			$this->filename=$_trace[0]['args'][0];
			$this->handle=fopen(
				F3::$global['LOGS'].$this->filename,'a+'
			);
		}
		if (!is_resource($this->handle)) {
			// Unable to open file
			trigger_error(self::TEXT_LogOpen);
			return;
		}
	}

	/**
		Logger destructor
			@public
	**/
	public function __destruct() {
		if (is_resource($this->handle))
			fclose($this->handle);
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
