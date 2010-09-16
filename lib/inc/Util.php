<?PHP
// Holds static routines that are too darned useful to leave in obscurity.
class Util {

	public static function indentInclude( $file, $indent = 0, $return = false, $required = false ){
		ob_start();
		if( $required ) require( $file );
		else include( $file );
		$content = self::indentString( $indent, ob_get_contents() );
		ob_end_clean();
		if( substr( $content, -1 ) != "\n" ){ $content.= "\n"; }
		if( !$return ){
			print( $content );
		}
		return( $content );
	}

	public static function indentRequire( $file, $indent = 0, $return = false ){
		return( self::indentInclude( $file, $indent, $return, true ) );
	}

	public static function indentString( $indentLevel, $string, $padChar = "\t", $splitChar = "\n" ){
		if( !is_null( $indentLevel ) && is_numeric( $indentLevel ) && trim( $string ) != '' ){
			$pad = str_repeat( $padChar, $indentLevel );
			$string = $pad.str_replace( $splitChar, $splitChar.$pad, $string );
			// If the very end of the string is an instance of splitchar, don't replace it!
			if( $indentLevel > 0 && substr( $string, ( -1 * strlen( $splitChar.$pad ) ) ) == $splitChar.$pad ){
				$string = substr( $string, 0, ( -1 * strlen( $pad ) ) );
			}
		}
		return( $string );
	}

	// This removes arrays from inside arrays
	public static function arrayFlatten( /* array, int, int, array, string, etc. */ ){   
		$outList = array();
		$inList = func_get_args();
		while( count( $inList ) ){   
			$frontElement = array_shift( $inList );
			if ( is_array( $frontElement ) ){
				$inList = array_merge( $frontElement, $inList );
			} else {
				array_push( $outList, $frontElement );
			}
		}   
		return $outList;
	}  

	public static function arrayToXML( $data, $rootNodeName = 'ResultSet', &$xml=null ) {
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if ( ini_get('zend.ze1_compatibility_mode') == 1 ) ini_set ( 'zend.ze1_compatibility_mode', 0 );
		if ( is_null( $xml ) ) $xml = simplexml_load_string( '<'.$rootNodeName.'/>' );

		// loop through the data passed in.
		foreach( $data as $key => $value ) {
			// no numeric keys in our xml please!
			$numeric = false;
			if ( is_numeric( $key ) ) {
				$numeric = true;
				$key = $rootNodeName;
			}
			// delete any char not allowed in XML element names
			$key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

			// if there is another array found recrusively call this function
			if ( is_array( $value ) ) {
				$node = self::is_assoc( $value ) || $numeric ? $xml->addChild( $key ) : $xml;

				// recrusive call.
				if ( $numeric ) $key = 'anon';
				self::arrayToXml( $value, $key, $node );
			} else {

				// add single node.
				$value = htmlentities( $value );
				$xml->addChild( $key, $value );
			}
		}
		return $xml;
	}


	public static function xmlToArray( $xml ) {
		if ( is_string( $xml ) ) $xml = new SimpleXMLElement( $xml );
		$children = $xml->children();
		if ( !$children ) return (string) $xml;
		$arr = array();
		foreach ( $children as $key => $node ) {
			$node = self::xmlToArray( $node );

			// support for 'anon' non-associative arrays
			if ( $key == 'anon' ) $key = count( $arr );

			// if the node is already set, put it into an array
			if ( isset( $arr[$key] ) ) {
				if ( !is_array( $arr[$key] ) || $arr[$key][0] == null ) $arr[$key] = array( $arr[$key] );
				$arr[$key][] = $node;
			} else {
				$arr[$key] = $node;
			}
		}
		return $arr;
	}

	public static function formatSimpleXML( SimpleXMLElement $xml ){
		$dom = dom_import_simplexml( $xml )->ownerDocument;
		$dom->formatOutput = true;
		return( $dom->saveXML() );
	}

	// determine if a variable is an associative array
	public static function is_assoc( $array ) {
		return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
	}
}
?>
