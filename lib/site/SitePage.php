<?PHP
class SitePage extends Page {
	public function getController(){
		if( is_null( $this->controller ) ){
			$this->setController( SiteController::getInstance() );
		}
		return( $this->controller );
	}
}
?>
