<?PHP

class Page {
	const HTTP_ERROR_404 = 'HTTP/1.1 404 Not Found';

	const ERROR_CONTENT_CONTEXT = 'Cannot be called in utility context';
	const ERROR_UTILITY_CONTEXT = 'Cannot be called in content context';
	const ERROR_GENERIC         = 'An error has occurred.  Please try again later or contact technical support.';
	const ERROR_LOGIN           = 'You must log in to view this page.';
	const ERROR_PRIVILEGE       = 'You have insufficient privileges to view this page.';

	const TEMPLATE_HEADER  = 'header';
	const TEMPLATE_NAV     = 'nav';
	const TEMPLATE_NOTICES = 'notices';
	const TEMPLATE_PAGE    = 'page';
	const TEMPLATE_FOOTER  = 'footer';

	protected $contentOnly = false; // Indicates whether it's allowed to call any methods on this page, or only provides content.
	protected $utilityOnly = false; // Indicates whether or not this page has any base content, or should be used "methods only"
	protected $methods = null;
	protected $authenticated = false;
	protected $accessRole = null;
	protected $controller = null;
	protected $suppressTemplate = false;
	protected $defaultRedirectPage = '';

	// Should be defined or extended in SitePage inheritance pattern
	protected static $pathPrefix = '';
	protected static $contentPath = array( '.', 'lib/view' );
	protected static $mergedContentPath = null; // Used for cache only
	protected static $contentExtensions = array( 'php', 'phtml', 'html', 'xhtml', 'xml' );

	// Page libraries
	protected static $loginPage = 'login';
	protected static $insufficientPrivilegesPage = 'insufficientPrivileges';
	protected static $errorPage = 'error';
	protected static $defaultPage = 'default';
	protected static $notFoundPage = '404';

	// Wrapper Template
	protected static $templatesIncluded = false;
	protected static $template_content = array(
		self::TEMPLATE_HEADER  => '_header',
		self::TEMPLATE_NAV     => '_nav',
		self::TEMPLATE_NOTICES => '_notices',
		self::TEMPLATE_PAGE    => null,
		self::TEMPLATE_FOOTER  => '_footer'
	);

	public function __construct( Controller $controller = null ){
		if( !is_null( $controller ) ){
			$this->setController( $controller );
		}
	}

	public function Render( $page = null ){
		if( $controller = $this->getController() ){
			if( $this->authenticated && !$controller->isAuthenticatedUser() ){
				return( $this->loginPage() );
			}
			$action = $controller->getAction();
			if( !empty( $action ) ){
				$action = $this->makeVerb( $action );
				if( in_array( $action, $this->getMethods() ) ){
					return( $this->$action( $controller->getActionParameters() ) );
				} else if(
					!in_array(
						strtolower( $controller->getAction() ),
						array(
							strtolower( $page ),
							strtolower( get_class( $this ) )
						)
					)
				){
					return( $this->notFound() );
				}
			} else if( $this->utilityOnly && empty( $action ) ){
				return( $this->errorPage() );
			} else if( $this->contentOnly ){
				if( $controller->getAction() ){ return( $this->errorPage() ); } // Error, or ignore?
			}
			$this->requireContent( $page );
		} else {
			// Test options available without a controller.
			if( $this->utilityOnly ){
				return( $this->errorPage() );
			} else if( $this->authenticated ){
				return( $this->loginPage() );
			} else {
				return( $this->requireContent( $page ) );
			}
		}
	}

	public static function getContentPath( $refresh = false ){
		if( is_null( self::$mergedContentPath ) || $refresh ){
			self::$mergedContentPath = $paths = ( is_array( self::$contentPath ) ? self::$contentPath : array( self::$contentPath ) );
			$extendedPaths = ( class_exists( 'SiteController' ) ? SiteController::getExtendedPaths() : Controller::getExtendedPaths() );
			if( count( $extendedPaths ) > 0 ){
				foreach( $paths as $path ){
					foreach( $extendedPaths as $extendedPath ){
						self::$mergedContentPath[] = $path.DIRECTORY_SEPARATOR.$extendedPath;
					}
				}
			}
		}
		return( self::$mergedContentPath );
	}

	public function getContentFile( $classPage = null ){
		$file = false;
		$classPage = ( empty( $classPage ) ? get_class( $this ) : $classPage );
		foreach( self::$contentExtensions as $ext ){
			if( file_exists( $testFile = self::getFile( $classPage.'.'.$ext ) ) ){
				$file = $testFile;
				break;
			}
		}
		return( $file );
	}

	public static function getFile( $file, $path = null ){
		$foundFile = false;
		if( empty( $file ) ) return( false );
		if( is_null( $path ) ){
			$path = self::getContentPath();
		}
		$paths = ( is_array( $path ) ? $path : array( $path ) );
		foreach( $paths as $path ){
			$testFile = $path.DIRECTORY_SEPARATOR.$file;
			if( file_exists( $testFile ) ){
				$foundFile = $testFile;
				break;
			}
		}
		return( $foundFile );
	}

	protected function requireContent( $page = null, $indent = 0, $suppressTemplate = null ){
		if( is_null( $suppressTemplate ) ){
			$suppressTemplate = self::$templatesIncluded;
			self::$templatesIncluded = true;
		}
		$templates = ( $suppressTemplate || $this->suppressTemplate ? array( self::TEMPLATE_PAGE => $page ) : self::$template_content );
		if( is_subclass_of( $this, 'SitePage' ) || !empty( $page ) ){
			$templates[ self::TEMPLATE_PAGE ] = (
				is_null( $page )
				? strtolower( substr( get_class( $this ), 0, 1 ) ).substr( get_class( $this ), 1 ) // Pre PHP 5.3 "lcfirst"
				: strtolower( substr( $page, 0, 1 ) ).substr( $page, 1 ) // Pre PHP 5.3 "lcfirst"
			);
		}
		if( empty( $templates[ self::TEMPLATE_PAGE ] ) ){
			$templates[ self::TEMPLATE_PAGE ] = self::$defaultPage;
		}
		if(
			!$this->getContentFile( $templates[ self::TEMPLATE_PAGE ] )
			|| $templates[ self::TEMPLATE_PAGE ] == self::$notFoundPage
		){
			if( !headers_sent() ){ header( self::HTTP_ERROR_404, true, 404 ); }
			$templates[ self::TEMPLATE_PAGE ] = self::$notFoundPage;
		}
		$tmpArray = array_keys( $templates );
		$first = array_shift( $tmpArray );
		$last = array_pop( $tmpArray );
		foreach( $templates as $template => $content_page ){
			$content_file = $this->getContentFile( $content_page );
			if( empty( $content_file ) ){ continue; }
			Util::indentRequire(
				$content_file,
				(
					   $template != $first
					&& $template != $last
					? $indent + 2
					: $indent
				)
			);
		}
	}

	protected function setController( Controller $controller ){
		$this->controller = $controller;
	}
	public function getController(){
		if( is_null( $this->controller ) ){
			$this->setController( Controller::getInstance() );
		}
		return( $this->controller );
	}

	public static function staticPage( $page, $message = null ){
		if( !empty( $message ) ){
			ctrl()->addNotice( $message, SiteController::NOTICE_URGENT );
		}
		ctrl()->Go( $page );
		exit();
	}

	public static function notFound( $message = null ){
		self::staticPage( self::$notFoundPage, $message );
	}
	public static function errorPage( $message = self::ERROR_GENERIC ){
		self::staticPage( self::$errorPage, $message );
	}
	public static function loginPage( $message = self::ERROR_LOGIN ){
		self::staticPage( self::$loginPage, $message );
	}
	public static function insufficientPrivilegesPage( $message = self::ERROR_PRIVILEGE ){
		self::staticPage( self::$insufficientPrivilegesPage, $message );
	}
	public static function defaultPage( $message = null ){
		self::staticPage( self::$defaultPage, $message );
	}
	public static function makeVerb( $action ){
		return( 'do'.ucfirst( $action ) );
	}

	public function getMethods(){
		if( !is_array( $this->methods ) ){
			$methods = array();
			foreach( get_class_methods( $this ) as $method ){
				if(
					preg_match( '/^do[A-Z]/', $method )
					&& !in_array( $method, $methods )
				){
					$methods[] = $method;
				}
			}
			$this->methods = $methods;
		}
		return( $this->methods );
	}

	public function getDefaultRedirectPage(){
		return( $this->defaultRedirectPage );
	}

	public static function config( array $config ){
		foreach( $config as $key => $value ){
			self::$$key = $value;
		}
	}

	public static function getConfig( $key ){
		return( ( isset( self::$$key ) ? self::$$key : null ) );
	}
}

?>
