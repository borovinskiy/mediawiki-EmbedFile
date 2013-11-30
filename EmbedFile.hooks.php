<?php
/**
 * Wrapper class for encapsulating EmbedFile related parser methods
 */
abstract class EmbedFile {

	protected static $initialized = false;

	/**
	 * Sets up parser functions.
	 */
	public static function setup() {
		// Setup parser hooks. ev is the primary hook, evp is supported for
		// legacy purposes
		global $wgVersion;
		$prefix = version_compare($wgVersion, '1.7', '<') ? '#' : '';
		self::addMagicWord($prefix, "файл", "EmbedFile::parserFunction");
		//self::addMagicWord($prefix, "file", "EmbedFile::parserFunction_ev");
		return true;
	}

	private static function addMagicWord($prefix, $word, $function) {
		global $wgParser;
		$wgParser->setFunctionHook($prefix . $word, $function, SFH_NO_HASH);
	}

	/**
	 * Adds magic words for parser functions.
	 * @param array  $magicWords
	 * @param string $langCode
	 *
	 * @return bool Always true
	 */
	public static function parserFunctionMagic(&$magicWords, $langCode='en') {
	//	$magicWords['file'] = array(0, 'file');
		$magicWords['файл']  = array(1, 'файл');
		return true;
	}





	/**
	 * Embeds video of the chosen service
	 * @param Parser $parser Instance of running Parser.
	 * @param String $service Which online service has the video.
	 * @param String $id Identifier of the chosen service
	 * @param String $width Width of video (optional)
	 * @param String $desc description to show (optional)
	 * @param String $align alignment of the video (optional)
	 * @return String Encoded representation of input params (to be processed later)
	 */
	public static function parserFunction($parser, $url = null, $type = null, $width = null, $align = null, $desc = null) {
		global $wgScriptPath;

 		$opts = array();

                // Argument 0 is $parser, so begin iterating at 1
                for ( $i = 1; $i < func_num_args(); $i++ ) {
                        $opts[] = func_get_arg( $i );
                }

		$enumOpts = array("ширина","описание","высота","тип","слева","справа","центр");

                //The $opts array now looks like this:
                //      [0] => 'foo=bar'
                //      [1] => 'apple=orange'

                //Now we need to transform $opts into a more useful form...
                $options = self::extractOptions( $opts, $enumOpts );

		$type = isset($options[1]) ? $options[1] : null;
                $width = isset($options[2]) ? $options[2] : null;
		$align = isset($options[3]) ? $options[3] : null;
		$desc = isset($options[4]) ? $options[4] : null;

                if (isset($options['ширина'])) { $width = $options['ширина']; }
                if (isset($options['описание'])) { $desc = $options['описание']; }
                if (isset($options['высота'])) { $height = $options['высота']; }
		if (isset($options['тип'])) { $type = $options['тип']; }

		$align = isset($options['слева'])  ? "left" : 
		$align = isset($options['справа']) ? "right" : 
		$align = isset($options['центр'])  ? "center" : "auto";

		if (func_num_args() == 3 && count($options) == 0) {
			$desc = $type;	// Если задано в виде {{ file:http://example.com/file.ext | просто некоторое описание }}
		}

		// Initialize things once
		if (!self::$initialized) {
			self::VerifyWidthMinAndMax();
			self::$initialized = true;
		}

		$url = trim($url);

		$file = wfParseUrl($url);	
		if (!$file) {
			return self::errNotValidUrl($url);
		}
		$ext = self::getFileExtension($url,$file);
		if (!$ext or strlen($ext)==0) {
			return self::errNotValidUrl($url);
		}
		
		$desc = $parser->recursiveTagParse($desc);

		$service = $ext;

		$entry = self::getServiceEntry($service);
		if (!$entry) {
			return self::errBadService($service);
		}

		if (!self::sanitizeWidth($entry, $width)) {
			return self::errBadWidth($width);
		}
		$height = self::getHeight($entry, $width);

		$hasalign = ($align !== null || $align == 'auto');

		if ($hasalign) {
			$align = trim($align);
			if ( !self::validateAlignment($align) ) {
				return self::errBadAlignment($align);
			}

			$desc = self::getDescriptionMarkup($desc);
		}


		// If service is Yandex -> use own parser
		//if ($service == 'yandex' || $service == 'yandexvideo') {
		//$url = self::getYandex($id);
		//$url = htmlspecialchars_decode($url);
		//}

		// if the service has it's own custom extern declaration, use that instead
		if ($type && array_key_exists ($type,$entry) &&  array_key_exists ('extern',$entry[$type]) && ($clause = $entry[$type]['extern'] != NULL)) {		//Если задан type в описании сервиса (чтобы кроме имени сервиса можно было еще выводить по определенному типу
			if (is_array($entry[$type]['extern']) && array_key_exists('url_replace_pattern',$entry[$type]['extern']) && $entry[$type]['extern']['url_replace_pattern'] != NULL) {		// Вычленяем из url идентификатор файла
				$url = preg_replace($entry[$type]['extern']['url_replace_pattern']['pattern'], $entry[$type]['extern']['url_replace_pattern']['replacement'],$url);
			}
			$clause = wfMsgReplaceArgs($clause, array($wgScriptPath, $url, $width, $height, $desc));
                        if ($hasalign) {
                                $clause = self::generateAlignExternClause($clause, $align, $desc, $width, $height);
                        }
                        return array($clause, 'noparse' => true, 'isHTML' => true);
		}
		else if (array_key_exists ('extern', $entry) && ($clause = $entry['extern']) != NULL) {
			if (array_key_exists('url_replace_pattern',$entry[$type]['extern']) && $entry[$type]['extern']['url_replace_pattern'] != NULL) {          // Вычленяем из url идентификатор файла
                                $url = preg_replace($entry[$type]['extern']['url_replace_pattern']['pattern'], $entry[$type]['extern']['url_replace_pattern']['replacement'],$url);
                        }
			$clause = wfMsgReplaceArgs($clause, array($wgScriptPath, $url, $width, $height, $desc));
			if ($hasalign) {
				$clause = self::generateAlignExternClause($clause, $align, $desc, $width, $height);
			}
			return array($clause, 'noparse' => true, 'isHTML' => true);
		}

		// Build URL and output embedded flash object
		$url = wfMsgReplaceArgs($entry['url'], array($url, $width, $height));
		$clause = "";
		
		// If service is RuTube -> use own parser
		//if ($service == 'rutube'){
		//$url = self::getRuTube($id);
		//}
		if ($hasalign) {
			$clause = self::generateAlignClause($url, $width, $height, $align, $desc);
		}
		else {
			$clause = self::generateNormalClause($url, $width, $height);
		}
		return array($clause, 'noparse' => true, 'isHTML' => true);
	}

	/**
	 * Return the HTML necessary to embed the video normally.
	 *
	 * @param string $url
	 * @param int    $width
	 * @param int    $height
	 *
	 * @return string
	 */
	private static function generateNormalClause($url, $width, $height) {
		$clause = "<object width=\"{$width}\" height=\"{$height}\">" .
			"<param name=\"movie\" value=\"{$url}\"></param>" .
			"<param name=\"wmode\" value=\"transparent\"></param>" .
			"<embed src=\"{$url}\" type=\"application/x-shockwave-flash\"" .
			" wmode=\"transparent\" width=\"{$width}\" height=\"{$height}\">" .
			"</embed></object>";
		$clause = "<span style='max-width: {$width}px;'><a href='".$url."'>'.$url.'</a></span>";
		return $clause;
	}

	private static function isValidFileUrl($file=array()) {
		if (strlen($file['path']) && strlen($file['host']) > 0 && strlen($file['scheme'])>0) {
			return true;
		}
		return false;
	}

	private static function getFileExtension($url,$file) {
		if (self::isValidFileUrl($file) && self::isValidUrl($url)) {
			$path_parts = pathinfo($url);
                        if (isset($path_parts['extension'])) {
				return $path_parts['extension'];
                        }
                        else {
                                return false;
                        }
		}
		return false;
	}

	/**
	 * The HTML necessary to embed the video with a custom embedding clause,
	 * specified align and description text
	 *
	 * @param string $clause
	 * @param string $align
	 * @param string $desc
	 * @param int    $width
	 * @param int    $height
	 *
	 * @return string
	 */
	private static function generateAlignExternClause($clause, $align, $desc, $width, $height)
	{
		$alignClass = self::getAlignmentClass($align);
		$clause = "<div class=\"thumb {$alignClass}\">" .
			$clause .
			"</div>";
		return $clause;
	}

	/**
	 * Generate the HTML necessary to embed the video with the given alignment
	 * and text description
	 *
	 * @param string $url
	 * @param int    $width
	 * @param int    $height
	 * @param string $align
	 * @param string $desc
	 *
	 * @return string
	 */
	private static function generateAlignClause($url, $width, $height, $align, $desc) {
		$alignClass = self::getAlignmentClass($align);
/*
		$clause = "<div class=\"thumb {$alignClass}\">" .
			"<div class=\"thumbinner\" style=\"width: {$width}px;\">" .
			"<object width=\"{$width}\" height=\"{$height}\">" .
			"<param name=\"movie\" value=\"{$url}\"></param>" .
			"<param name=\"wmode\" value=\"transparent\"></param>" .
			"<embed src=\"{$url}\" type=\"application/x-shockwave-flash\"" .
			" wmode=\"transparent\" width=\"{$width}\" height=\"{$height}\"></embed>" .
			"</object>" .
			"<div class=\"thumbcaption\">" .
			$desc .
			"</div></div></div>";
*/		
		$clause = "<div class=\"thumb {$alignClass}\">" .
                        "<div class=\"thumbinner\" style=\"max-width: {$width}px;\">" . 
			"<a href='".$url."'>'.$desc.'</a>" .
			"</div></div>";

		return $clause;
	}

	/**
	 * Get the entry for the specified service, by name
	 *
	 * @param string $service
	 *
	 * @return $string
	 */
	private static function getServiceEntry($service) {
		// Get the entry in the list of services
		global $wgEmbedFileServiceList;
		return $wgEmbedFileServiceList[$service];
	}

	/**
	 * Get the width. If there is no width specified, try to find a default
	 * width value for the service. If that isn't set, default to 425.
	 * If a width value is provided, verify that it is numerical and that it
	 * falls between the specified min and max size values. Return true if
	 * the width is suitable, false otherwise.
	 *
	 * @param string $service
	 *
	 * @return mixed
	 */
	private static function sanitizeWidth($entry, &$width) {
		global $wgEmbedFileMinWidth, $wgEmbedFileMaxWidth;
		if ($width === null || $width == '*' || $width == '') {
			if (isset($entry['default_width'])) {
				$width = $entry['default_width'];
			}
			else {
				$width = 425;
			}
			return true;
		}
		if (!is_numeric($width)) {
			return false;
		}
		return $width >= $wgEmbedFileMinWidth && $width <= $wgEmbedFileMaxWidth;
	}

	/**
	 * Validate the align parameter.
	 *
	 * @param string $align The align parameter
	 *
	 * @return {\code true} if the align parameter is valid, otherwise {\code false}.
	 */
	private static function validateAlignment($align) {
		return ($align == 'left' || $align == 'right' || $align == 'center' || $align == 'auto');
	}

	private static function getAlignmentClass($align) {
		switch ($align) {
			case "left": 
				return "tleft pull-left";
			case "right":
				return "tright pull-right";
		}
//		if ( $align == 'left' || $align == 'right' ) {
//			return 't' . $align;
//		}

		return $align;
	}

	/**
	 * Calculate the height from the given width. The default ratio is 450/350,
	 * but that may be overridden for some sites.
	 *
	 * @param int $entry
	 * @param int $width
	 *
	 * @return int
	 */
	private static function getHeight($entry, $width) {
		$ratio = 4 / 3;
		if (isset($entry['default_ratio'])) {
			$ratio = $entry['default_ratio'];
		}
		return round($width / $ratio);
	}

	/**
	 * If we have a textual description, get the markup necessary to display
	 * it on the page.
	 *
	 * @param string $desc
	 *
	 * @return string
	 */
	private static function getDescriptionMarkup($desc) {
		if ($desc !== null) {
			return "<div class=\"thumbcaption\">$desc</div>";
		}
		return "";
	}

	/**
	 * Get an error message if the width is bad
	 *
	 * @param int $width
	 *
	 * @return string
	 */
	private static function errBadWidth($width) {
		$msg = wfMsgForContent('embedfile-illegal-width', @htmlspecialchars($width));
		return '<div class="errorbox">' . $msg . '</div>';
	}

	/**
	 * Get an error message if there are missing parameters
	 *
	 * @param string $service
	 * @param string $id
	 *
	 * @return string
	 */
	private static function errMissingParams($service, $id) {
		return '<div class="errorbox">' . wfMsg('embedfile-missing-params') . '</div>';
	}

        /**
         * Get an error message if there url not valid
         *
         * @param string $url
         *
         * @return string
         */

	private static function errNotValidUrl($url) {
		return '<div class="errorbox">' . wfMsg('embedfile-notvalid-url') . '</div>';
	}

	/**
	 * Get an error message if the service name is bad
	 *
	 * @param string $service
	 *
	 * @return string
	 */
	private static function errBadService($service) {
		$msg = wfMsg('embedfile-unrecognized-service', @htmlspecialchars($service));
		return '<div class="errorbox">' . $msg . '</div>';
	}

	/**
	 * Get an error message for an invalid align parameter
	 *
	 * @param string $align The given align parameter.
	 *
	 * @return string
	 */
	private static function errBadAlignment($align) {
		$msg = wfMsg('embedfile-illegal-alignment', @htmlspecialchars($align));
		return '<div class="errorbox">' . $msg . '</div>';
	}


	/**
	 * Verify that the min and max values for width are sane.
	 *
	 * @return void
	 */
	private static function VerifyWidthMinAndMax() {
		global $wgEmbedFileMinWidth, $wgEmbedFileMaxWidth;
		if (!is_numeric($wgEmbedFileMinWidth) || $wgEmbedFileMinWidth < 100) {
			$wgEmbedFileMinWidth = 100;
		}
		if (!is_numeric($wgEmbedFileMaxWidth) || $wgEmbedFileMaxWidth > 1024) {
			$wgEmbedFileMaxWidth = 1024;
		}
	}

	/**
         * Converts an array of values in form [0] => "name=value" into a real
         * associative array in form [name] => value
         *
         * @param array string $options
         * @return array $results
         */
        private static function extractOptions( array $options, $enumOptions = array() ) {
                $results = array();
		$k = 0;
                foreach ( $options as $i=>$option ) {
                        $pair = explode( '=', $option );
                        if ( count( $pair ) == 2 ) {			// Если опция задана как key=value
                                $name = strtolower( trim( $pair[0] ) );
                                $value = trim( $pair[1] );
                                $results[$name] = $value;
                        }
//			else if (strlen($option)>0) {
			else if (in_array($option,$enumOptions)) {	// Иначе, видимо, это сразу {{ .. | valuue | }}	 и value есть в массиве $enumOptions
				$results[$option] = true;			
				//$results[$i] = $option;
			}
			else {						// иначе это просто нумерованная опция, но мы сместим ее номер с $i на $k (т.е. как будто перед нумерованной опцией именнованных нет
				$results[$k] = $option;
				$k++;
			}
                }
                //Now you've got an array that looks like this:
                //      [foo] => bar
                //      [apple] => orange

                return $results;
        }

	private static function isValidUrl($url,$absolute = FALSE) {
		if ($absolute) {
			return (bool) preg_match("
      				/^                                                      # Start at the beginning of the text
      				(?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
      				(?:                                                     # Userinfo (optional) which is typically
        				(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
        				(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
      				)?
      				(?:
        				(?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
        				|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
      				)
      				(?::[0-9]+)?                                            # Server port number (optional)
      					(?:[\/|\?]
        				(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
      				*)?
    				$/xi", $url);
  		}
  		else {
    			return (bool) preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
  		}
	}

//	private static function UrlReplace($url,$
	
}
