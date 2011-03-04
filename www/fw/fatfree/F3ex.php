<?php

/**
	PHP Fat-Free Framework - Less Hype, More Meat.

	Fat-Free is a modular and lightweight PHP 5.3+ Web development framework
	designed to help build dynamic Web sites. This Expansion Pack works
	seamlessly with the Fat-Free Core and includes a CAPTCHA image
	generator, Javascript/CSS compressor, thumbnail image creator, and tools
	for communicating with other Web servers.

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 Fat-Free Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Expansion
		@version 1.2.8
**/

//! Expansion Pack
class F3ex {

	//@{
	//! Locale-specific error/exception messages
	const TEXT_Object='Expansion pack cannot be used in object context';
	const TEXT_Minify='Unable to minify {@CONTEXT}';
	const TEXT_Timeout='Connection timed out';
	const TEXT_Color='Invalid color specified';
	const TEXT_NotArray='{@CONTEXT} is not an array';
	//@}

	//! Carriage return/line feed sequence
	const EOL="\r\n";

	//! PNG compression level
	const PNG_Compress=3;

	/**
		Convert RGB hex triad to array
			@return mixed
			@param $_triad string
			@public
			@static
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
			@static
	**/
	public static function captcha($_dimx,$_dimy,$_len,$_ttfs='cube') {
		$_base=self::rgb(F3::$global['BGCOLOR']);
		$_trans=F3::$global['FGTRANS'];
		// Specify Captcha seed
		$_SESSION['captcha']=substr(md5(uniqid()),0,$_len);
		// Font size
		$_size=min($_dimx/$_len,.6*$_dimy);
		// Load TrueType font file
		$_fonts=explode('|',$_ttfs);
		$_file=F3::$global['FONTS'].
			F3::convSlashes($_fonts[rand(0,count($_fonts)-1)]).'.ttf';
		F3::$profile['FILES']['fonts'][basename($_file)]=filesize($_file);
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
			$_angle=$_maxdeg-rand(0,$_maxdeg*2);
			// Get CAPTCHA character from session cookie
			$_char=$_SESSION['captcha'][$_i];
			$_fg=imagecolorallocatealpha(
				$_captcha,
				rand(0,255-$_trans),rand(0,255-$_trans),rand(0,255-$_trans),
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
		if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
			header(F3::HTTP_Content.': image/png');
		imagepng($_captcha,NULL,self::PNG_Compress,PNG_NO_FILTER);
		// Free resources
		imagedestroy($_captcha);
	}

	/**
		Generate thumbnail image
			@param $_file string
			@param $_dimx integer
			@param $_dimy integer
			@public
			@static
	**/
	public static function thumb($_file,$_dimx,$_dimy) {
		preg_match('/\.(gif|jp[e]*g|png)*$/',$_file,$_ext);
		$_ext[1]=str_replace('jpg','jpeg',$_ext[1]);
		$_file=F3::$global['GUI'].$_file;
		eval('$_img=imagecreatefrom'.$_ext[1].'($_file);');
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
		if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
			header(F3::HTTP_Content.': image/'.$_ext[1]);
		// Send output in same format as original
		eval('image'.$_ext[1].'($_tmp);');
		// Free resources
		imagedestroy($_img);
		imagedestroy($_tmp);
	}

	/**
		Generate identicon from an MD5 hash value
			@param $_hash string
			@param $_size integer
			@public
			@static
	**/
	public static function identicon($_hash,$_size=NULL) {
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
		for ($_i=0;$_i<F3::$global['IBLOCKS']-2;$_i++) {
			$_shapeS[$_i]=hexdec($_hash[9+$_i*2]);
			$_angleS[$_i]=hexdec($_hash[10+$_i*2]%4);
		}
		// Start with NxN blank slate
		$_identicon=imagecreatetruecolor(
			$_size*F3::$global['IBLOCKS'],$_size*F3::$global['IBLOCKS']);
		imageantialias($_identicon,TRUE);
		$_bg=imagecolorallocate($_identicon,$_bgR,$_bgG,$_bgB);
		$_fg=imagecolorallocate($_identicon,$_fgR,$_fgG,$_fgB);
		// Generate corner sprites
		$_corner=imagecreatetruecolor($_size,$_size);
		imagefill($_corner,0,0,$_bg);
		$_sprite=$_dynamic[$_shapeC];
		$_len=count($_sprite);
		for ($_i=0;$_i<$_len;$_i++)
			$_sprite[$_i]=$_sprite[$_i]*$_size;
		imagefilledpolygon($_corner,$_sprite,$_len/2,$_fg);
		for ($_i=0;$_i<$_angleC;$_i++)
			$_corner=imagerotate($_corner,90,$_bg);
		// Generate side sprites
		for ($_i=0;$_i<F3::$global['IBLOCKS']-2;$_i++) {
			$_side[$_i]=imagecreatetruecolor($_size,$_size);
			imagefill($_side[$_i],0,0,$_bg);
			$_sprite=$_dynamic[$_shapeS[$_i]];
			$_len=count($_sprite);
			for ($_j=0;$_j<$_len;$_j++)
				$_sprite[$_j]=$_sprite[$_j]*$_size;
			imagefilledpolygon($_side[$_i],$_sprite,$_len/2,$_fg);
			for ($_j=0;$_j<$_angleS[$_i];$_j++)
				$_side[$_i]=imagerotate($_side[$_i],90,$_bg);
		}
		// Generate center sprites
		for ($_i=0;$_i<F3::$global['IBLOCKS']-2;$_i++) {
			$_center[$_i]=imagecreatetruecolor($_size,$_size);
			imagefill($_center[$_i],0,0,$_bg);
			$_sprite=$_dynamic[$_shapeX];
			if (F3::$global['IBLOCKS']%2>0 && $_i==F3::$global['IBLOCKS']-3)
				// Odd center sprites
				$_sprite=$_static[$_shapeX%8];
			$_len=count($_sprite);
			if ($_len) {
				for ($_j=0;$_j<$_len;$_j++)
					$_sprite[$_j]=$_sprite[$_j]*$_size;
				imagefilledpolygon($_center[$_i],$_sprite,$_len/2,$_fg);
			}
			if ($_i<(F3::$global['IBLOCKS']-3))
				for ($_j=0;$_j<$_angleS[$_i];$_j++)
					$_center[$_i]=imagerotate($_center[$_i],90,$_bg);
		}
		// Paste sprites
		for ($_i=0;$_i<4;$_i++) {
			imagecopy($_identicon,$_corner,0,0,0,0,$_size,$_size);
			for ($_j=0;$_j<F3::$global['IBLOCKS']-2;$_j++) {
				imagecopy($_identicon,$_side[$_j],
					$_size*($_j+1),0,0,0,$_size,$_size);
				for ($_k=$_j;$_k<F3::$global['IBLOCKS']-3-$_j;$_k++)
					imagecopy($_identicon,$_center[$_k],
						$_size*($_k+1),$_size*($_j+1),0,0,$_size,$_size);
			}
			$_identicon=imagerotate($_identicon,90,$_bg);
		}
		if (F3::$global['IBLOCKS']%2>0)
			// Paste odd center sprite
			imagecopy($_identicon,$_center[F3::$global['IBLOCKS']-3],
				$_size*(floor(F3::$global['IBLOCKS']/2)),
				$_size*(floor(F3::$global['IBLOCKS']/2)),0,0,$_size,$_size);
		// Resize
		$_resized=imagecreatetruecolor($_size,$_size);
		imagecopyresampled($_resized,$_identicon,0,0,0,0,$_size,$_size,
			$_size*F3::$global['IBLOCKS'],$_size*F3::$global['IBLOCKS']);
		// Make the background transparent
		imagecolortransparent($_resized,$_bg);
		if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
			header(F3::HTTP_Content.': image/png');
		imagepng($_resized,NULL,self::PNG_Compress,PNG_NO_FILTER);
		// Free resources
		imagedestroy($_identicon);
		imagedestroy($_resized);
		imagedestroy($_corner);
		for ($_i=0;$_i<F3::$global['IBLOCKS']-2;$_i++) {
			imagedestroy($_side[$_i]);
			imagedestroy($_center[$_i]);
		}
	}

	/**
		Generate a blank image for use as a placeholder
			@param $_dimx integer
			@param $_dimy integer
			@param $_bg string
			@public
			@static
	**/
	public static function fakeImage($_dimx,$_dimy,$_bg='EEE') {
		list($_r,$_g,$_b)=self::rgb($_bg);
		$_img=imagecreatetruecolor($_dimx,$_dimy);
		$_bg=imagecolorallocate($_img,$_r,$_g,$_b);
		imagefill($_img,0,0,$_bg);
		if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
			header(F3::HTTP_Content.': image/png');
		imagepng($_img,NULL,self::PNG_Compress,PNG_NO_FILTER);
		// Free resources
		imagedestroy($_img);
	}

	/**
		Strip Javascript/CSS files of extraneous whitespaces and comments;
		Return combined output as a minified string
			@param $_base string
			@param $_files array
			@public
			@static
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
		if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
			header(F3::HTTP_Content.': '.$_type[substr($_ext[1],0,3)]);
		$_path=F3::$global['GUI'].$_base;
		ob_start();
		foreach ($_files as $_file) {
			if (!file_exists($_path.$_file)) {
				trigger_error(self::TEXT_Minify);
				return;
			}
			F3::$profile['FILES']
				['minified'][basename($_file)]=filesize($_path.$_file);
			// Rewrite relative URLs in CSS
			echo preg_replace_callback(
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
		$_src=ob_get_contents();
		ob_end_clean();
		$_ptr=0;
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
								echo '/'.$_str;
								$_ptr+=strlen($_str)+1;
								if ($_src[$_ptr-1]!='\\' ||
									$_src[$_ptr-2]=='\\') {
										echo '/';
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
						echo '/*@'.$_str.$_src[$_ptr].'@*/';
						$_ptr+=strlen($_str)+6;
					}
					elseif ($_src[$_ptr+1]=='*') {
						// Multiline comment
						$_str=strstr(substr($_src,$_ptr+2),'*/',TRUE);
						$_ptr+=strlen($_str)+4;
					}
					elseif ($_src[$_ptr+1]=='/') {
						// Multiline comment
						$_str=strstr(substr($_src,$_ptr+2),"\n",TRUE);
						$_ptr+=strlen($_str)+2;
					}
					else {
						// Division operator
						echo $_src[$_ptr];
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
					echo $_match.$_str;
					$_ptr+=strlen($_str)+1;
					if ($_src[$_ptr-1]!='\\' || $_src[$_ptr-2]=='\\') {
						echo $_match;
						$_ptr++;
						break;
					}
				}
				continue;
			}
			if ($_src[$_ptr]!="\n" && $_src[$_ptr]!="\r" && (
				$_src[$_ptr]!="\t" && $_src[$_ptr]!=' ' ||
				// IE has trouble with removal of some spaces in CSS
				preg_match('/[\w\$\.'.($_ext[1]=='css'?'\(\)':'').']{2}/',
					$_src[$_ptr-1].$_src[$_ptr+1])))
					// Ignore whitespaces
					echo strtr($_src[$_ptr],"\t",' ');
			$_ptr++;
		}
	}

	/**
		Convert seconds to frequency (in words)
			@return integer
			@param $_secs string
			@public
			@static
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
			@static
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
		$_doc=new XMLTree;
		$_doc->encoding=F3::$global['ENCODING'];
		if ($_doc->loadHTML(F3::$global['RESPONSE'])) {
			// Valid HTML; add to sitemap
			if (!$_map[$_url]['level'])
				// Web root
				$_map[$_url]['level']=0;
			$_map[$_url]['status']=TRUE;
			$_map[$_url]['mod']=F3::$global['TIME'];
			$_map[$_url]['freq']=0;
			if (F3::$global['TTL']) {
				// Cached page
				$_map[$_url]['mod']=filemtime(
					F3::$global['CACHE'].'url-'.F3::hashCode('GET '.$_url)
				);
				$_map[$_url]['freq']=F3::$global['TTL'];
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
			foreach (array_keys($_map) as $_ref)
				// Parse each link
				self::sitemap($_ref);
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
				$_item->addChild('lastMod',date('c',$_ref['mod']));
				$_item->addChild('changefreq',
					self::frequency($_ref['freq']));
				$_item->addChild('priority',
					sprintf('%1.1f',1-$_ref['level']/$_depth));
			}
			// Send output
			F3::$global['QUIET']=FALSE;
			if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
				header(F3::HTTP_Content.': application/xhtml+xml');
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
			@static
	**/
	public static function
		http($_pattern,$_query='',$_reqhdrs=array(),$_follow=TRUE) {
		// Check if valid route pattern
		list($_method,$_route)=F3::checkRoute($_pattern);
		// Valid URI pattern
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
		$_socket=@fsockopen($_target,$_url['port'],$_ecode,$_text);
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
					'compatible;'.F3::TEXT_Version.')'.self::EOL.
					($_reqhdrs?
						(implode(self::EOL,$_reqhdrs).self::EOL):'').
					($_method!='GET'?(
						'Content-Type: '.
							'application/x-www-form-urlencoded'.self::EOL.
						'Content-Length: '.strlen($_query).self::EOL):'').
				F3::HTTP_AcceptEnc.': deflate'.self::EOL.
				F3::HTTP_Connect.': close'.self::EOL.self::EOL.
			$_query.self::EOL.self::EOL
		);
		$_found=FALSE;
		// Set connection timeout parameters
		stream_set_blocking($_socket,TRUE);
		stream_set_timeout($_socket,ini_get('default_socket_timeout'));
		$_info=stream_get_meta_data($_socket);
		// Get response
		while (!feof($_socket) && !$_info['timed_out']) {
			$_response.=fgets($_socket,4096); // MDFK97
			$_info=stream_get_meta_data($_socket);
			if (!$_found) {
				$_rcvhdrs=strstr($_response,self::EOL.self::EOL,TRUE);
				if ($_rcvhdrs) {
					$_found=TRUE;
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
		if ($_follow && preg_match('/HTTP\/1\.\d\s30\d/',$_rcvhdrs)) {
			// Redirection
			preg_match('/'.F3::HTTP_Location.':\s(.+?)\r\n/',
				$_rcvhdrs,$_location);
			return self::http(
				$_method.' '.$_location[1],$_query,$_reqhdrs
			);
		}
		if (!preg_match('/cli/',F3::$global['MODE']) && !headers_sent())
			// Forward HTTP response headers
			foreach (explode(self::EOL,$_rcvhdrs) as $_header)
				if (!F3::$global['QUIET'] &&
					preg_match('/'.F3::HTTP_Content.'/',$_header))
						header($_header);
				elseif (preg_match('/'.F3::HTTP_Encoding.'.+gzip/',
					$_header))
						$_response=gzinflate($_response);
		// Return content
		return $_response;
	}

	/**
		Mock environment for command-line use and/or unit testing
			@param $_regex string
			@param $_params array
	**/
	public static function mock($_regex,array $_params=NULL) {
		// Check if valid route pattern
		list($_method,$_uri)=F3::checkRoute($_regex);
		if (is_array($_params))
			foreach ($_params as $_var=>$_val)
				F3::set('REQUEST.'.$_var,$_val);
		// Override for use in unit testing; during command-line mode,
		// these globals don't exist anyway
		$_SERVER['REQUEST_METHOD']=$_method;
		$_SERVER['REQUEST_URI']=$_uri;
	}

	/**
		Perform test and append result to TEST global variable
			@return string
			@param $_cond boolean
			@param $_pass string
			@param $_fail string
			@public
			@static
	**/
	public static function expect($_cond,$_pass,$_fail) {
		if (is_string($_cond))
			$_cond=F3::resolve($_cond);
		$_result=$_cond?$_pass:$_fail;
		$_text=is_string($_result)?F3::resolve($_result):$_result;
		F3::$global['TEST'][]=
			array('result'=>(int)(boolean)$_cond,'text'=>$_text);
		return $_text;
	}

	/**
		Rotate a two-dimensional framework array variable; Replace contents
		of framework variable if flag is TRUE (default), otherwise, return
		transposed result
			@return array
			@param $_name string
			@param $_flag boolean
			@public
			@static
	**/
	public static function transpose($_name,$_flag=TRUE) {
		$_rows=F3::get($_name);
		if (!is_array($_rows)) {
				F3::set('CONTEXT',$_name);
				trigger_error(F3::resolve(self::TEXT_NotArray));
				return FALSE;
		}
		foreach ($_rows as $_keyx=>$_valx)
			foreach ($_valx as $_keyy=>$_valy)
				$_result[$_keyy][$_keyx]=$_valy;
		if (!$_flag)
			return $_result;
		F3::set($_name,$_result);
	}

	/**
		Return TRUE if string is a valid URL
			@return boolean
			@param $_text string
			@public
			@static
	**/
	public static function validURL($_text) {
		return preg_match(
			'/^(ht|f)tp[s]*:\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?'.
				'(\/|\/([\w#!:.?+=&%@!\-\/]))?/',$_text);
	}

	/**
		Expansion Pack bootstrap code
			@public
			@static
	**/
	public static function onLoad() {
		$_global=&F3::$global;
		$_global['BGCOLOR']='FFF';
		$_global['FGTRANS']=32;
		$_global['IBLOCKS']=4;
		$_global['IPIXELS']=64;
		$_global['SITEMAP']=array();
	}

	/**
		Intercept calls to undefined static methods
			@return mixed
			@param $_func string
			@param $_args array
			@public
			@static
	**/
	public static function __callStatic($_func,array $_args) {
		F3::$global['CONTEXT']=$_func;
		trigger_error(F3::resolve(F3::TEXT_Method));
	}

	/**
		Class constructor
			@public
	**/
	public function __construct() {
		// Prohibit use of Expansion Pack as an object
		trigger_error(self::TEXT_Object);
	}

}

?>
