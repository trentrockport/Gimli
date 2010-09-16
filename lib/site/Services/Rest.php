<?PHP
class Rest extends SitePage {
	public function doSessionDump( $parameters ){
		print Util::formatSimpleXML( Util::arrayToXML( $_SESSION, 'session' ) );
	}
	public function doServerDump( $parameters ){
		print Util::formatSimpleXML( Util::arrayToXML( $_SERVER, 'server' ) );
	}
	public function doRequestDump( $parameters ){
		unset( $_REQUEST{'__page'} );
		unset( $_REQUEST{'__action'} );
		print Util::formatSimpleXML( Util::arrayToXML( $_REQUEST, 'request' ) );
	}

}
?>
