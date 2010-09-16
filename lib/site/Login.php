<?PHP
class Login extends SitePage {

	protected $loginForm = null;
	protected $defaultRedirectPage = '/authenticatedPage';

	public function doLogin( $parameters = null ){
		// TODO: Test required parameters.  Maybe use the Form as a helper to do so?
		// [/] Process Form on the server side, provide error messages and content back to the UI.
		// [ ] Do this in a way that does not create a bug - the redirect should be retained if
		//     specified, or set explicitly from the referrer so that next time it *will* be specified.
		// [ ] Create a user collection using appropriate criteria: email address and some hash of the
		//     password.  Users may always login, but they may not do everything if their account is not
		//     active.
		$ctrl = $this->getController();
		// $ctrl->log( 'Woohoo!' );
		$ctrl->log( $parameters );
		$form = $this->getLoginForm();
		$form->fill( $parameters );
		if( $form->validate() ){
			// TODO: Search for the user specified in the parameters
			$ctrl->setUser( new User( 1 ) );
			$ctrl->log( 'HERE!' );
			// If redirection has been specified, do that.
			$ctrl->redirect( $this->getConfig( 'pathPrefix' ).$this->getDefaultRedirectPage() );
		} else {
			// Set a message with the errors?
			$ctrl->addNotice( 'Login failed, yo.', SiteController::NOTICE_URGENT );
			$this->requireContent();
		}
	}

	public function doLogout( $parameters ){
		$ctrl = $this->getController();
		if( $ctrl->getUser() instanceof User ){
			$ctrl->clearUser();
			$ctrl->addNotice( 'You have successfully logged out.' );
			$this->requireContent( 'default' );
		} else {
			$ctrl->redirect( $this->getConfig( 'pathPrefix' ).'/' );
		}
	}

	public function getLoginForm( $reset = false, array $hiddenFields = null ){
		if( is_null( $this->loginForm ) || $reset ){
			$this->loginForm = Form::quickForm(
				'login',
				$this->getConfig( 'pathPrefix' ).'/login/login',
				array(
					'Email Address' => 'email',
					'Password' => 'password'
				)
			);
		}
		if( is_array( $hiddenFields ) ){
			foreach( $hiddenFields as $name => $value ){
				$hidden = new FormHidden( $name );
				$hidden->setValue( $value );
				$this->loginForm->addField( $hidden );
			}
		}
		return( $this->loginForm );
	}

	public function requireContent( $page = null, $indent = 0, $suppressTemplate = null ){
		if( $this->getController()->getUser() instanceof User ){
			$this->getController()->redirect( $this->getDefaultRedirectPage() );
		} else {
			return( parent::requireContent( $page, $indent, $suppressTemplate ) );
		}
	}
}
?>
