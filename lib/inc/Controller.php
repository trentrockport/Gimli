<?PHP
// Thoughts:
// Meaningful content will always come in via POST, whereas GET is reserved for indicating the page and the parameters.

class Controller {
	const GET_ARG_ACTION = '__action';
	const GET_ARG_PAGE   = '__page';
	const SESSION_PREFIX = 'mvcms::';

	const ENV_DEV = 'dev';
	const ENV_STAGE = 'stage';
	const ENV_PROD = 'prod';

	const NOTICE_ERROR = 'error';
	const NOTICE_STANDARD = 'standard';
	const NOTICE_URGENT = 'urgent';

	const SESSKEY_NOTICES = 'notices';
	const ERROR_RECURSION = 'Recursive redirect detected, halting processing.  Sorry.';

	protected $user = null;
	protected $session = null;
	protected $page = null;
	protected $action = null;
	protected $actionParameters = array();

	protected static $environment = self::ENV_PROD;
	protected static $instance = null;
	protected static $pageObj = null;
	protected static $recursionPages = array();

	protected static $extendedPaths = array();

	public function __construct( $page = null, $action = null, $user = null, $replaceInstance = true ){
		// Setting Action before Page, because Action might actually be the Page value we want if subfolders are being used.
		$this->setAction( ( !empty( $action ) ? $action : ( array_key_exists( self::GET_ARG_ACTION, $_GET ) ? $_GET[self::GET_ARG_ACTION] : null ) ) );
		$this->setPage( ( !empty( $page ) ? $page : ( array_key_exists( self::GET_ARG_PAGE, $_GET ) ? $_GET[self::GET_ARG_PAGE] : null ) ) );
		foreach( $_REQUEST as $key => $value ){
			if(
				in_array(
					$key,
					array(
						self::GET_ARG_ACTION,
						self::GET_ARG_PAGE,
						session_name()
					)
				)
			){ continue; }
			// NOTE: This may be brittle logic.  It might make more sense to introspect the query string itself and see if no-argument
			// parameters have been passed there, vs. inferring that from the fact that it ended up with no value in the parsed array.
			$this->setActionParameter( $key, ( empty( $value ) ? true : $value ) );
		}
		if( $user instanceof User ){
			$this->setUser( $user );
		}
		if( $replaceInstance ){ self::$instance = $this; }
		return( $this );
	}
	public static function getInstance(){
		if( is_null( self::$instance ) ){
			self::$instance = new Controller();
		}
		return( self::$instance );
	}

	public function Go(
		$page = null,
		$action = null,
		array $params = null
	){
		$top = false;
		$class = null;
		if( is_null( $page ) ){
			$top = true;
			$page = $this->getPage();
		}
		if( !in_array( ( is_null( $page ) ? 'default' : $page ), self::$recursionPages, true ) ){
			self::$recursionPages[] = $page;
		} else { throw( new Exception( self::ERROR_RECURSION ) ); }

		if( !empty( $page ) ){
			$testClass = preg_replace( '/[^a-zA-Z\-_\.0-9]*/', '', ucwords( $page ) );
			if(
				!empty( $testClass )
				&& class_exists( $testClass )
				&& is_subclass_of( $testClass, 'Page' )
			){
				$class = $testClass;
			}
		}
		$baseProcessor = false;
		if( is_null( $class ) ){
			$class = ( class_exists( 'SitePage' ) ? 'SitePage' : 'Page' );
			$baseProcessor = true;
		}
		$pageObj = new $class( $this );

		// Handle recursion stack for internal includes
		$oldPageObj      = $this->getPageObject();
		$oldAction       = $this->getAction();
		$oldActionParams = $this->getActionParameters();

		$this->setPageObject( $pageObj );
		if( !$top ){
			$this->setAction( $action );
			if( is_array( $params ) ){ $this->setActionParameters( $params ); }
		}
		
		if( $baseProcessor ){
			$pageObj->Render( $page );
		} else {
			$pageObj->Render();
		}

		// Unwind recursion stack
		if( !empty( $oldPageObj ) && !$top ){
			$this->setPageObject( $oldPageObj );
			$this->setAction( $oldAction );
			if( is_array( $oldActionParams ) ){ $this->setActionParameters( $oldActionParams ); }
			array_pop( self::$recursionPages );
		}
	}

	public function isAuthenticatedUser(){
		return( $this->getUser() instanceof User );
	}

	public function setPage( $page ){
		// Look at all members of page/action and see if any of them are sub-directories
		// of site, left shifting as she goes and appending the result to the include path.
		$paths = explode( PATH_SEPARATOR, get_include_path() );
		$sitePath = false;
		foreach( $paths as $path ){
			if( preg_match( '/\Wsite$/', $path ) ){
				$sitePath = $path;
				break;
			}
		}
		$extendedPath = '';
		if( $sitePath !== false ){
			if( $this->getAction() ){
				$page.= '/'.preg_replace( '/\?.*$/', '', $this->getAction() );
			}
			$bits = explode( '/', $page );
			$newPath = $sitePath;
			while( $subdir = ucwords( array_shift( $bits ) ) ){
				if( is_readable( $newPath.'/'.$subdir ) ){
					$newPath = $newPath.'/'.$subdir;
					$extendedPath.= ( !empty( $extendedPath ) ? '/' : '' ).$subdir;
				} else {
					$page = $subdir; // Abuse of nomenclature.  Sorry.
					/* Why did I do that?
					if( $page == ucwords( $this->getAction() ) ){
						$this->setAction( null );
					}
					*/
					break;
				}
			}
			if( $newPath != $sitePath ){
				set_include_path( get_include_path().PATH_SEPARATOR.$newPath );
				$this->addExtendedPath( $extendedPath );
			}
		}
		return( $this->page = $page );
	}
	public function getPage(){ return( $this->page ); }

	public static function setPageObject( Page $pageObj ){
		return( self::$pageObj = $pageObj );
	}

	public static function getPageObject(){
		return( self::$pageObj );
	}

	public function setAction( $action ){
		return( $this->action = $action );
	}
	public function getAction(){ return( $this->action ); }

	public function setActionParameter( $key, $value ){
		return( $this->actionParameters{ $key } = $value );
	}
	public function removeActionParameter( $key ){
		if( array_key_exists( $key, $this->actionParameters ) ){
			unset( $this->actionParameters{ $key } );
		}
	}
	public function setActionParameters( array $parameters ){ return( $this->actionParameters = $parameters ); }
	public function getActionParameter( $key ){
		return( ( array_key_exists( $key, $this->actionParameters ) ? $this->actionParameters{ $key } : null ) );
	}
	public function getActionParameters(){ return( $this->actionParameters ); }

	public function setUser( User $user ){
		return( $this->user = $user );
	}
	public function getUser(){ return( $this->user ); }
	public function User(){ return( $this->getUser() ); }

	public function addNotice( $notice, $type = self::NOTICE_STANDARD, $html = true ){
		if( !array_key_exists( self::SESSKEY_NOTICES, $_SESSION ) ){ $_SESSION[ self::SESSKEY_NOTICES ] = array(); }
		if( !array_key_exists( $type, $_SESSION[ self::SESSKEY_NOTICES ] ) ){ $_SESSION[ self::SESSKEY_NOTICES ][ $type ] = array(); }
		$_SESSION{ self::SESSKEY_NOTICES }{ $type }[] = ( $html ? $notice : htmlentities( $notice ) );
	}

	public function getNotices( $type = null, $clear = false ){
		$return = array();
		if( array_key_exists( self::SESSKEY_NOTICES, $_SESSION ) ){
			if( !is_null( $type ) ){
				if( array_key_exists( $type, $_SESSION[ self::SESSKEY_NOTICES ] ) ){
					$return = $_SESSION[ self::SESSKEY_NOTICES ][ $type ];
				}
			} else {
				$return = $_SESSION[ self::SESSKEY_NOTICES ];
			}
			if( $clear ){ $this->clearNotices( $type ); }
		}

		return( $return );
	}

	public function clearNotices( $type = null ){
		if( array_key_exists( self::SESSKEY_NOTICES, $_SESSION ) ){
			if( !is_null( $type ) ){
				if( array_key_exists( $type, $_SESSION[ self::SESSKEY_NOTICES ] ) ){
					unset( $_SESSION[ self::SESSKEY_NOTICES ][ $type ] );
				}
			} else {
				unset( $_SESSION[ self::SESSKEY_NOTICES ] );
			}
		}
	}

	public static function redirect( $page = null, $action = null ){
		if( !headers_sent() ){
			header( 'Location: '.$page );
			exit;
		} else {
			if( !in_array( $page, self::$recursionPages, true ) ){
				self::$recursionPages[] = $page;
				$controller = self::getInstance();
				$controller->setAction( $action );
				$controller->setPage( $page );
				$controller->Go();
			} else {
				throw( new Exception( self::ERROR_RECURSION ) );
			}
		}
	}

	public function clearUser(){
		$this->user = null;
	}

	public static function setEnvironment( $env ){
		if(
			is_null( $env )
			|| in_array(
				$env,
				array(
					self::ENV_DEV,
					self::ENV_STAGE,
					self::ENV_PROD
				)
			)
		){
			self::$environment = $env;
			return( true );
		}
		return( false );
	}
	// Override this method so that "getEnvironment" can determine operating conditions
	// dynamically, rather than just get/set.
	public static function getEnvironment(){
		return( self::$environment );
	}

	public static function getExtendedPaths(){
		return( self::$extendedPaths );
	}
	public static function addExtendedPath( $path ){
		$return = null;
		if( !in_array( $path, self::$extendedPaths, true ) ){
			$return = self::$extendedPaths[] = $path;
		}
		return( $return );
	}
	public static function clearExtendedPaths(){
		self::$extendedPaths = array();
	}
	

	public static function log( $msg, $html = true ){
		$logMsg = '';
		if( $msg instanceof Exception ){
			$logMsg = ( $html ? '<pre><b>' : '' )
				.'Caught unhandled exception in '.$msg->getFile().' on line '.$msg->getLine().':'.( $html ? '</b>' : '' )."\n"
				.$msg->getMessage()."\n\n"
				.( $html ? '<b>' : '' ).'Stack trace:'.( $html ? '</b>' : '' )."\n".$msg->getTraceAsString()."\n"
				.( $html ? '</pre>' : '' );
		} else {
			$stack = debug_backtrace();
			$file = preg_replace( '/^.*\/([^\/]+)$/', '\1', $stack[0]['file'] );
			$logMsg = ( $html ? '<pre>' : '' ).$file.':'.$stack[0]['line'].': '.var_export( $msg, true ).( $html ? '</pre>' : '' )."\n";
		}
		switch( self::getEnvironment() ){
			case self::ENV_PROD:
			case self::ENV_STAGE:
				error_log( $logMsg );
				break;
			case self::ENV_DEV:
				print( $logMsg );
				break;
		}
	}
}

?>
