<?PHP
class SiteController extends Controller {
	public static function getInstance(){
		if( is_null( self::$instance ) ){
			self::$instance = new SiteController();
		}
		return( self::$instance );
	}

	public function setUser( User $user ){
		parent::setUser( $user );
		$_SESSION[self::SESSION_PREFIX.'user_id'] = $user->getId();
	}

	public function getUser(){
		if( array_key_exists( self::SESSION_PREFIX.'user_id', $_SESSION ) ){
			if( is_null( $this->user ) ){
				$this->user = new User( $_SESSION[self::SESSION_PREFIX.'user_id'] );
			} else if(
				$this->user instanceof User
				&& $this->user->getId()
				&& $this->user->getId() != $_SESSION[self::SESSION_PREFIX.'user_id']
			){
				$_SESSION[self::SESSION_PREFIX.'user_id'] = $this->user->getId();
			}
		}
		return( $this->user );
	}

	public function clearUser(){
		parent::clearUser();
		unset( $_SESSION[self::SESSION_PREFIX.'user_id'] );
	}
}
?>
