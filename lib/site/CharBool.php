<?PHP

class CharBool extends Column {
	public function getPersistData( $localOnly = false ){
		$return = null;
		$data = $this->getData( $localOnly );
		if( !is_null( $data ) ){
			$return = ( $data ? 'Y' : 'N' );
		}
		return( $return );
	}
	public function validatePersistData( $data ){
		return( ( strtoupper( $data ) == 'Y' ? true : false ) );
	}
}

?>
