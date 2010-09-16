<?PHP

class FormField extends XHTMLElement {
	const TYPE_BUTTON   = 'button';
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_HIDDEN   = 'hidden';
	const TYPE_IMAGE    = 'image';
	const TYPE_INPUT    = 'input';
	const TYPE_PASSWORD = 'password';
	const TYPE_RADIO    = 'radio';
	const TYPE_RESET    = 'reset';
	const TYPE_SELECT   = 'select';
	const TYPE_SUBMIT   = 'submit';
	const TYPE_TEXT     = 'text';

	protected $tag  = self::TYPE_INPUT; // Defaults
	protected $name = '';
	protected $type = self::TYPE_TEXT;

	public function __construct( $name = null ){
		// TODO: getters?
		parent::__construct( $this->tag );
		$this->setType( $this->type );
		if( !empty( $name ) ){
			$this->setName( $name );
			$this->setId( $this->getJSName() );
		}
	}

	protected $readOnly = false;
	protected $required = false;
	protected $validationRegex = null;
	protected $validationFunction = null;
	protected $validationMessage = null;
	protected $handlers = null;

	public function setRequired( $bool = true ){ return( $this->required = ( $bool ? true : false ) ); }
	public function isRequired(){
		return( $this->required );
	}

	public function setReadOnly( $bool = true ){ return( $this->required = ( $bool ? true : false ) ); }
	public function isReadOnly(){
		return( $this->required );
	}

	public function clearValidation(){
		$this->setValidationJSFunction( null );
		$this->setValidationRegex( null );
	}
	public function getValidationFunction(){
		return( $this->validationFunction );
	}
	public function setValidationFunction( $jsFunction ){
		return( $this->validationFunction = $jsFunction );
	}
	public function getValidationRegex(){
		return( $this->validationRegex );
	}
	public function setValidationRegex( $test ){
		return( $this->validationRegex = $test );
	}
	public function getJSValidation(){
		$return = null;
		if( $func = $this->getValidationFunction() ){
			$return = '\''.$func.'( this.getField( "'.$this->getJSName().'" ) )\'';
		} else if( $regex = $this->getValidationRegex() ){
			$return = $regex;
		}
		return( $return );
	}

	public function clearValidationMessage(){
		return( $this->setValidationMessage( null ) );
	}
	public function setValidationMessage( $message ){
		return( $this->validationMessage = $message );
	}
	public function getValidationMessage(){
		return( $this->validationMessage );
	}

	public function validate(){
		$pass = true;
		$value = $this->getValue();
		if( $this->isRequired() && empty( $value ) ){
			$pass = false;
		}
		if(
			$pass 
			&& !is_null( $regex = $this->getValidationRegex() )
			&& (
				(
					empty( $value )
					&& !$this->isRequired()
				) || ( !empty( $value ) )
			)
		){
			$pass = preg_match( $regex, $value );
		}
		return( $pass );
	}

	public function getJSName( $fromName = null ){
		return(
			(
				!is_null( $fromName )
				? Form::makeJSName( $fromName )
				: $this->getAttribute( 'name' )
			)
		);
	}

	public function getName(){ return( $this->name ); }
	public function setName( $name ){
		$this->name = $name;
		$this->setAttribute( 'name', Form::makeJSName( $name ) );
	}
}

?>
