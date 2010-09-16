<?PHP
error_reporting( E_ALL | E_STRICT );

$paths = array(
	get_include_path(),
	'lib/inc',
	'lib/site',
	// 'lib/inc/torpor' // Optional use of Torpor, typically either symlinked or externally contained
);
set_include_path(
	preg_replace(
		'/'.PATH_SEPARATOR.PATH_SEPARATOR.'/',
		PATH_SEPARATOR, 
		implode( PATH_SEPARATOR, $paths )
	)
);
ini_set( 'display_errors', 'stdout' ); // I don't remember why I did this.

function __autoload( $className ){
	$paths = explode( PATH_SEPARATOR, get_include_path() );
	foreach( $paths as $path ){
		if( file_exists( $file = $path.DIRECTORY_SEPARATOR.$className.'.php' ) ){
			require( $file );
			return;
		}
	}
	// For use with Torpor in TypedGrid mode.
	// if( preg_match( '/^[a-zA-Z]/', $className ) && Torpor::typedGridClassCheck( $className ) ){ return; }
}

function ctrl(){
	global $controller;
	return( $controller );
}

function thisPage(){
	$ctrl = ctrl();
	return( ( $ctrl instanceof Controller ? $ctrl->getPageObject() : null ) );
}


session_start();

// Set to "dev" environment to log everything straight to the page.
SiteController::setEnvironment( siteController::ENV_DEV );
if( getenv( 'GIMLI_PATH_PREFIX' ) ){
	SitePage::config( array( 'pathPrefix'  => getenv( 'GIMLI_PATH_PREFIX' ) ) );
}

set_exception_handler( 'SiteController::log' );
// Set up and execute SiteController instance
$controller = new SiteController();
$controller->Go();

?>
