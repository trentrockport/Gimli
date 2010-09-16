<?PHP

class AutoSoap extends TypedGrid {
	protected $_allowWrite = true;
	protected $_allowOverWrite = true;
	protected $_allowRemove = false;

	public function allowWrite(){     return( $this->_allowWrite ); }
	public function allowOverWrite(){ return( $this->allowWrite() && $this->_allowOverWrite ); }
	public function allowRemove(){    return( $this->allowWrite() && $this->_allowRemove    ); }

	public function setAllowWrite( $bool = true ){
		return( $this->_allowWrite = ( $bool ? true : false ) );
	}
	public function setAllowOverWrite( $bool = true ){
		$bool = ( $bool ? true : false );
		if( $bool && !$this->allowWrite() ){
			$this->setAllowWrite();
		}
		return( $this->_allowOverWrite = $bool );
	}
	public function setAllowRemove( $bool = true ){
		$bool = ( $bool ? true : false );
		if( $bool && !$this->allowWrite() ){
			$this->setAllowWrite();
		}
		return( $this->_allowRemove = $bool );
	}

	public function GenerateWSDL(){
		// Construct Torpor grid XML representation
		// Construct Criteria specification
		// Fetch returns a collection of zero or more Torpor Grid XML
		// Write takes a modified collection of Torpor Grid XML (allows for a remote ID to be supplied; but at the header level per-object)
		// Remove takes same criteria as Fetch
	}

	public function HandleSOAPRequest( $request ){
	}

	public function SOAPFetch( $request ){
	}

	public function SOAPWrite( $request ){
	}

	public function SOAPRemove( $request ){
	}
}

?>
