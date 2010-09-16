<?PHP
class XHTMLElement {
	protected $inline = false;
	protected $outputEmptyAttributes = false;
	protected $tag = 'element';
	protected $autoClose = true;
	protected $attributes = array();
	protected $content = array();

	protected static $recursionStack = array();

	public function __construct( $tag = null, $id = null ){
		if( !empty( $tag ) ){
			$this->setTag( $tag );
		}
		if( !empty( $id ) ){
			$this->setId( $id );
		}
	}

	public function &getAttributes(){
		return $this->attributes;
	}
	public function getAttribute( $attribute ){
		$attributes = &$this->getAttributes();
		return( ( array_key_exists( $attribute, $attributes ) ? $attributes{ $attribute } : null ) );
	}
	public function setAttribute( $attribute, $value ){
		return( $this->attributes{ $attribute } = $value );
	}
	public function deleteAttribute( $attribute ){
		$attributes = &$this->getAttributes();
		if( array_key_exists( $attribute, $attributes ) ){
			unset( $attributes{ $attribute } );
		}
	}

	public function clearContent(){ $this->content = array(); }
	public function addContent( $content ){ return( $this->content[] = $content ); }
	public function setContent( $content ){
		$this->clearContent();
		return( $this->addContent( $content ) );
	}
	public function &getContent(){ return $this->content; }

	public function getTag(){ return( $this->tag ); }
	public function setTag( $tag ){ return( $this->tag = (string)$tag ); }

	public function getInline(){ return( $this->inline ); }
	public function setInline( $bool = true ){ return( $this->inline = ( $bool ? true : false ) ); }

	public function getAutoClose(){ return( $this->autoClose ); }
	public function setAutoClose( $bool = true ){ return( $this->autoClose = ( $bool ? true : false ) ); }

	public function getOutputEmptyAttributes(){ return( $this->outputEmptyAttributes ); }
	public function setOutputEmptyAttributes( $bool = true ){ return( $this->outputEmptyAttributes = ( $bool ? true : false ) ); }

	public function RenderOpenTag( $keepOpen = false ){
		$tagHTML = '<'.$this->getTag();
		$attributes = $this->getAttributes();
		ksort( $attributes );
		foreach( $attributes as $attribute => $value ){
			if(
				$this->getOutputEmptyAttributes()
				|| (
					!is_null( $value ) 
					&& $value !== ''
				)
			){
				// This is potentially a problem.  Javascript events and hrefs will want to not be escaped!
				// TODO: get a more complete event list to compare against.
				$tagHTML.= ' '.$attribute.'="'.(	
					preg_match( '/^on(blur|focus|change|click|key(down|up|press))$/i', $attribute )
					|| (
						strtolower( $attribute ) == 'href'
						&& preg_match( '/^javascript:/', $value )
					)
					? $value
					: addslashes( htmlentities( $value ) )
				).'"';
			}
		}
		$tagHTML.= ( !$keepOpen && $this->getAutoClose() ? '/' : '' ).'>';
		return( $tagHTML );
	}

	public function RenderCloseTag(){
		return( '</'.$this->getTag().'>' );
	}

	public function Render( $indentLevel = 0 ){
		// Anti-recursion
		if( in_array( $this, self::$recursionStack, true ) ){
			trigger_error( 'XHTMLElement render recursion detected', E_USER_ERROR );
		}
		self::$recursionStack[] = $this;
		if( $indentLevel === false || $this->getInline() ){ $indentLevel = null; }

		$string = ( !is_null( $indentLevel ) ? str_repeat( "\t", $indentLevel ) : '' )
			.$this->RenderOpenTag( count( $this->content ) );
		if( count( $this->content ) > 0 || !$this->getAutoClose() ){
			$string.= ( !is_null( $indentLevel ) ? "\n" : '' );
			foreach( $this->getContent() as $content ){
				$string.= (
					$content instanceof XHTMLElement
					&& !$content->getInline()
					? $content->Render( ( !is_null( $indentLevel ) ? $indentLevel + 1 : false ) )
					: Util::indentString( ( !is_null( $indentLevel ) ? $indentLevel + 1 : null ), $content )
				).(
					!is_null( $indentLevel )
					&& (
						!( $content instanceof XHTMLElement )
						|| (
							$content instanceof XHTMLElement
							&& $content->getInline()
						)
					) ? "\n" : '' );
			}
			$string.= ( !is_null( $indentLevel ) ? str_repeat( "\t", $indentLevel ) : '' )
				.$this->RenderCloseTag();
		}
		// Pop ourselves off the stack.
		unset( self::$recursionStack[ array_search( $this, self::$recursionStack, true ) ] );
		return( $string.( !is_null( $indentLevel ) ? "\n" : '' ) );
	}

	public static function CData( $data ){
		return( '<![CDATA['.$data.']]>' );
	}

	public function __call( $func, $args ){
		$operation = substr( $func, 0, 3 );
		if( !preg_match( '/^([gs]et|del)/', $operation ) ){
			throw( new Exception( 'Unrecognized operation.' ) );
		}
		$attribute = substr( $func, ( $func == 'del' && preg_match( '/^delete/', $func ) ? strlen( 'delete' ) : strlen( $operation ) ) );
		$attribute{0} = strtolower( $attribute{0} );
		switch( $operation ){
			case 'get':
				return( $this->getAttribute( $attribute ) );
				break;
			case 'set':
				return( $this->setAttribute( $attribute, array_shift( $args ) ) );
				break;
			case 'del':
				return( $this->deleteAttribute( $attribute ) );
				break;
		}
	}

	public function __get( $attribute ){ return( $this->getAttribute( $attribute ) ); }
	public function __set( $attribute, $value ){ return( $this->setAttribute( $attribute, $value ) ); }

	public function __toString(){ return( $this->Render() ); }
}
