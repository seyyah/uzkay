<?php

/**
	Graphics plugin for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Graphics
		@version 1.3.24
**/

//! Graphics plugin
class Graphics {

	//! Minimum framework version required to run
	const F3_Minimum='1.3.24';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_Color='Invalid color specified';
	//@}

	//! PNG compression level
	const PNG_Compress=1;

	/**
		Convert RGB hex triad to array
			@return mixed
			@param $_triad string
			@public
	**/
	public static function rgb($_triad) {
		$_len=strlen($_triad);
		if ($_len==3 || $_len==6) {
			$_color=str_split($_triad,$_len/3);
			foreach ($_color as &$_hue)
				$_hue=hexdec(str_repeat($_hue,6/$_len));
			return $_color;
		}
		trigger_error(self::TEXT_Color);
		return FALSE;
	}

	/**
		Generate CAPTCHA image
			@param $_dimx integer
			@param $_dimy integer
			@param $_len integer
			@param $_ttfs string
			@public
	**/
	public static function captcha($_dimx,$_dimy,$_len,$_ttfs='cube') {
		$_base=self::rgb(F3::$global['BGCOLOR']);
		$_trans=F3::$global['FGTRANS'];
		// Specify Captcha seed
		if (!strlen(session_id()))
			session_start();
		$_SESSION['captcha']=substr(md5(uniqid()),0,$_len);
		F3::$global['SESSION']=&$_SESSION;
		// Font size
		$_size=min($_dimx/$_len,.6*$_dimy);
		// Load TrueType font file
		$_fonts=explode('|',$_ttfs);
		$_file=F3::$global['FONTS'].
			F3::fixSlashes($_fonts[mt_rand(0,count($_fonts)-1)]).'.ttf';
		F3::$global['PROFILE']['FILES']
			['fonts'][basename($_file)]=filesize($_file);
		$_maxdeg=15;
		// Compute bounding box metrics
		$_bbox=imagettfbbox($_size,$_angle,$_file,$_SESSION['captcha']);
		$_wimage=.9*(max($_bbox[2],$_bbox[4])-max($_bbox[0],$_bbox[6]));
		$_himage=max($_bbox[1],$_bbox[3])-max($_bbox[5],$_bbox[7]);
		// Create blank image
		$_captcha=imagecreatetruecolor($_dimx,$_dimy);
		list($_r,$_g,$_b)=$_base;
		$_bg=imagecolorallocate($_captcha,$_r,$_g,$_b);
		imagefill($_captcha,0,0,$_bg);
		$_width=0;
		// Insert each Captcha character
		for ($_i=0;$_i<$_len;$_i++) {
			// Random angle
			$_angle=$_maxdeg-mt_rand(0,$_maxdeg*2);
			// Get CAPTCHA character from session cookie
			$_char=$_SESSION['captcha'][$_i];
			$_fg=imagecolorallocatealpha(
				$_captcha,
				mt_rand(0,255-$_trans),
				mt_rand(0,255-$_trans),
				mt_rand(0,255-$_trans),
				$_trans
			);
			imagettftext(
				$_captcha,$_size,$_angle,
				($_dimx-$_wimage)/2+$_i*$_wimage/$_len,
				($_dimy-$_himage)/2+.9*$_himage,
				$_fg,$_file,$_char
			);
			imagecolordeallocate($_captcha,$_fg);
		}
		// Make the background transparent
		imagecolortransparent($_captcha,$_bg);
		// Send output as PNG image
		if (PHP_SAPI!='cli' && !headers_sent())
			header(F3::HTTP_Content.': image/png');
		imagepng($_captcha,NULL,self::PNG_Compress,PNG_NO_FILTER);
	}

	/**
		Generate thumbnail image
			@param $_file string
			@param $_dimx integer
			@param $_dimy integer
			@public
	**/
	public static function thumb($_file,$_dimx,$_dimy) {
		preg_match('/\.(gif|jp[e]*g|png)*$/',$_file,$_ext);
		$_ext[1]=str_replace('jpg','jpeg',$_ext[1]);
		$_file=F3::$global['GUI'].$_file;
		$_img=imagecreatefromstring(file_get_contents($_file));
		// Get image dimensions
		$_oldx=imagesx($_img);
		$_oldy=imagesy($_img);
		// Adjust dimensions; retain aspect ratio
		$_ratio=$_oldx/$_oldy;
		if ($_dimx<$_oldx)
			// Adjust height
			$_dimy=$_dimx/$_ratio;
		elseif ($_dimy<$_oldy)
			// Adjust width
			$_dimx=$_dimy*$_ratio;
		else {
			// Retain size if dimensions exceed original image
			$_dimx=$_oldx;
			$_dimy=$_oldy;
		}
		// Create blank image
		$_tmp=imagecreatetruecolor($_dimx,$_dimy);
		list($_r,$_g,$_b)=self::rgb(F3::$global['BGCOLOR']);
		$_bg=imagecolorallocate($_tmp,$_r,$_g,$_b);
		imagefill($_tmp,0,0,$_bg);
		// Resize
		imagecopyresampled($_tmp,$_img,0,0,0,0,$_dimx,$_dimy,$_oldx,$_oldy);
		// Make the background transparent
		imagecolortransparent($_tmp,$_bg);
		if (PHP_SAPI!='cli' && !headers_sent())
			header(F3::HTTP_Content.': image/'.$_ext[1]);
		// Send output in same graphics format as original
		eval('image'.$_ext[1].'($_tmp);');
	}

	/**
		Generate identicon from an MD5 hash value
			@param $_hash string
			@param $_size integer
			@public
	**/
	public static function identicon($_hash,$_size=NULL) {
		$_blox=F3::$global['IBLOCKS'];
		if (is_null($_size))
			$_size=F3::$global['IPIXELS'];
		// Rotatable shapes
		$_dynamic=array(
			array(.5,1,1,0,1,1),
			array(.5,0,1,0,.5,1,0,1),
			array(.5,0,1,0,1,1,.5,1,1,.5),
			array(0,.5,.5,0,1,.5,.5,1,.5,.5),
			array(0,.5,1,0,1,1,0,1,1,.5),
			array(1,0,1,1,.5,1,1,.5,.5,.5),
			array(0,0,1,0,1,.5,0,0,.5,1,0,1),
			array(0,0,.5,0,1,.5,.5,1,0,1,.5,.5),
			array(.5,0,.5,.5,1,.5,1,1,.5,1,.5,.5,0,.5),
			array(0,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1),
			array(0,.5,.5,1,1,.5,.5,0,1,0,1,1,0,1),
			array(.5,0,1,0,1,1,.5,1,1,.75,.5,.5,1,.25),
			array(0,.5,.5,0,.5,.5,1,0,1,.5,.5,1,.5,.5,0,1),
			array(0,0,1,0,1,1,0,1,1,.5,.5,.25,.5,.75,0,.5,.5,.25),
			array(0,.5,.5,.5,.5,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1),
			array(0,0,1,0,.5,.5,.5,0,0,.5,1,.5,.5,1,.5,.5,0,1)
		);
		// Fixed shapes (for center sprite)
		$_static=array(
			array(),
			array(0,0,1,0,1,1,0,1),
			array(.5,0,1,.5,.5,1,0,.5),
			array(0,0,1,0,1,1,0,1,0,.5,.5,1,1,.5,.5,0,0,.5),
			array(.25,0,.75,0,.5,.5,1,.25,1,.75,.5,.5,
				.75,1,.25,1,.5,.5,0,.75,0,.25,.5,.5),
			array(0,0,.5,.25,1,0,.75,.5,1,1,.5,.75,0,1,.25,.5),
			array(.33,.33,.67,.33,.67,.67,.33,.67),
			array(0,0,.33,0,.33,.33,.67,.33,.67,0,1,0,1,.33,.67,.33,
				.67,.67,1,.67,1,1,.67,1,.67,.67,.33,.67,.33,1,0,1,
				0,.67,.33,.67,.33,.33,0,.33)
		);
		// Parse MD5 hash
		$_hash=F3::resolve($_hash);
		list($_bgR,$_bgG,$_bgB)=self::rgb(F3::$global['BGCOLOR']);
		list($_fgR,$_fgG,$_fgB)=self::rgb(substr($_hash,0,6));
		$_shapeC=hexdec($_hash[6]);
		$_angleC=hexdec($_hash[7]%4);
		$_shapeX=hexdec($_hash[8]);
		for ($_i=0;$_i<$_blox-2;$_i++) {
			$_shapeS[$_i]=hexdec($_hash[9+$_i*2]);
			$_angleS[$_i]=hexdec($_hash[10+$_i*2]%4);
		}
		// Start with NxN blank slate
		$_identicon=imagecreatetruecolor($_size*$_blox,$_size*$_blox);
		imageantialias($_identicon,TRUE);
		$_bg=imagecolorallocate($_identicon,$_bgR,$_bgG,$_bgB);
		$_fg=imagecolorallocate($_identicon,$_fgR,$_fgG,$_fgB);
		// Generate corner sprites
		$_corner=imagecreatetruecolor($_size,$_size);
		imagefill($_corner,0,0,$_bg);
		$_sprite=$_dynamic[$_shapeC];
		for ($_i=0,$_len=count($_sprite);$_i<$_len;$_i++)
			$_sprite[$_i]=$_sprite[$_i]*$_size;
		imagefilledpolygon($_corner,$_sprite,$_len/2,$_fg);
		for ($_i=0;$_i<$_angleC;$_i++)
			$_corner=imagerotate($_corner,90,$_bg);
		// Generate side sprites
		for ($_i=0;$_i<$_blox-2;$_i++) {
			$_side[$_i]=imagecreatetruecolor($_size,$_size);
			imagefill($_side[$_i],0,0,$_bg);
			$_sprite=$_dynamic[$_shapeS[$_i]];
			for ($_j=0,$_len=count($_sprite);$_j<$_len;$_j++)
				$_sprite[$_j]=$_sprite[$_j]*$_size;
			imagefilledpolygon($_side[$_i],$_sprite,$_len/2,$_fg);
			for ($_j=0;$_j<$_angleS[$_i];$_j++)
				$_side[$_i]=imagerotate($_side[$_i],90,$_bg);
		}
		// Generate center sprites
		for ($_i=0;$_i<$_blox-2;$_i++) {
			$_center[$_i]=imagecreatetruecolor($_size,$_size);
			imagefill($_center[$_i],0,0,$_bg);
			$_sprite=$_dynamic[$_shapeX];
			if ($_blox%2>0 && $_i==$_blox-3)
				// Odd center sprites
				$_sprite=$_static[$_shapeX%8];
			$_len=count($_sprite);
			if ($_len) {
				for ($_j=0;$_j<$_len;$_j++)
					$_sprite[$_j]=$_sprite[$_j]*$_size;
				imagefilledpolygon($_center[$_i],$_sprite,$_len/2,$_fg);
			}
			if ($_i<($_blox-3))
				for ($_j=0;$_j<$_angleS[$_i];$_j++)
					$_center[$_i]=imagerotate($_center[$_i],90,$_bg);
		}
		// Paste sprites
		for ($_i=0;$_i<4;$_i++) {
			imagecopy($_identicon,$_corner,0,0,0,0,$_size,$_size);
			for ($_j=0;$_j<$_blox-2;$_j++) {
				imagecopy($_identicon,$_side[$_j],
					$_size*($_j+1),0,0,0,$_size,$_size);
				for ($_k=$_j;$_k<$_blox-3-$_j;$_k++)
					imagecopy($_identicon,$_center[$_k],
						$_size*($_k+1),$_size*($_j+1),0,0,$_size,$_size);
			}
			$_identicon=imagerotate($_identicon,90,$_bg);
		}
		if ($_blox%2>0)
			// Paste odd center sprite
			imagecopy($_identicon,$_center[$_blox-3],
				$_size*(floor($_blox/2)),$_size*(floor($_blox/2)),0,0,
				$_size,$_size);
		// Resize
		$_resized=imagecreatetruecolor($_size,$_size);
		imagecopyresampled($_resized,$_identicon,0,0,0,0,$_size,$_size,
			$_size*$_blox,$_size*$_blox);
		// Make the background transparent
		imagecolortransparent($_resized,$_bg);
		if (PHP_SAPI!='cli' && !headers_sent())
			header(F3::HTTP_Content.': image/png');
		imagepng($_resized,NULL,self::PNG_Compress,PNG_NO_FILTER);
	}

	/**
		Generate a blank image for use as a placeholder
			@param $_dimx integer
			@param $_dimy integer
			@param $_bg string
			@public
	**/
	public static function fakeImage($_dimx,$_dimy,$_bg='EEE') {
		// GD extension required
		if (!extension_loaded('gd')) {
			F3::$global['CONTEXT']='gd';
			trigger_error(F3::TEXT_PHPExt);
			return;
		}
		list($_r,$_g,$_b)=self::rgb($_bg);
		$_img=imagecreatetruecolor($_dimx,$_dimy);
		$_bg=imagecolorallocate($_img,$_r,$_g,$_b);
		imagefill($_img,0,0,$_bg);
		if (PHP_SAPI!='cli' && !headers_sent())
			header(F3::HTTP_Content.': image/png');
		imagepng($_img,NULL,self::PNG_Compress,PNG_NO_FILTER);
	}

	/**
		Bootstrap code
			@public
	**/
	public static function onLoad() {
		// GD extension required
		if (!extension_loaded('gd')) {
			// Unable to continue
			F3::$global['CONTEXT']='gd';
			trigger_error(F3::TEXT_PHPExt);
			return;
		}
		F3::$global['BGCOLOR']='FFF';
		F3::$global['FGTRANS']=32;
		F3::$global['IBLOCKS']=4;
		F3::$global['IPIXELS']=64;
		F3::$global['HEADERS']=array();
		F3::$global['SITEMAP']=array();
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
