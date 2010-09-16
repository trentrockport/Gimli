<?PHP

class Form extends XHTMLElement {
	const VALIDATION_REQUIRED_FIELDS = 'One or more required fields are empty.';
	const VALIDATION_TEST_FIELDS     = 'The following errors are preventing this form\s submission:';
	const METHOD_POST = 'post';
	const METHOD_GET  = 'get';

	protected $tag = 'form';
	protected $fields = array();
	
	public function __construct( $name, $action = null, $method = self::METHOD_POST ){
		if( !empty( $name ) ){ $this->setName( $name ); }
		else { throw( new Exception( 'Form name cannot be empty' ) ); }
		if( !empty( $action ) ){ $this->setAction( $action ); }
		$this->setMethod(
			( in_array( $method, array( self::METHOD_POST, self::METHOD_GET ) ) ? $method : self::METHOD_POST )
		);
	}

	public function setName( $name ){
		parent::setName( $name );
		$this->setOnsubmit( 'return window.'.$this->makeFormHandlerName().'.validate();' );
	}

	public function &getFields(){
		return $this->fields;
	}

	public function getField( $fieldName ){
		$return = null;
		$fields = &$this->getFields();
		if( array_key_exists( $fieldName, $fields ) ){
			$return = $fields{ $fieldName };
		} else if( array_key_exists( self::makeJSName( $fieldName ), $fields ) ){
			$return = $fields{ self::makeJSName( $fieldName ) };
		}
		return( $return );
	}

	public function addField(){
		$fields = Util::arrayFlatten( func_get_args() );
		foreach( $fields as $fieldObj ){
			if( !( $fieldObj instanceof FormField ) ){
				trigger_error( 'Field should be of type FormField', E_USER_WARNING );
				continue;
			}
			$this->fields{ $fieldObj->getJSName() } = $fieldObj;
		}
	}

	public function deleteField( $fieldName ){
		$fields = &$this->getFields();
		if( array_key_exists( $fieldName, $fields ) ){
			unset( $fields{ $fieldName } );
		}
	}

	public function fill( array $formSubmission ){
		$fields = &$this->getFields();
		foreach( $formSubmission as $name => $value ){
			if( array_key_exists( $name, $fields ) ){
				$fields{ $name }->setValue( $value );
			}
		}
	}

	public function validate( array $formSubmission = null ){
		if( !empty( $formSubmission ) ){
			$this->fill( $formSubmission );
		}
		$pass = true;
		foreach( $this->getFields() as $field ){
			if( !$field->validate() ){
				$pass = false;
				break;
			}
		}
		return( $pass );
	}

	public function getValidationMessages( array $formSubmission = null ){
		
/*
OK, some thinking here.  The validation routine needs to closely mirror that in the formHandler.js,
but it doesn't have the same context.  For example, providing immediate feedback regarding which
fields need to be provided but haven't in the form of color queues.  The best option is to provide
a complete list of which required fields were left absent, and which per-field regexes failed, etc.

The best option, which may also take the longest, would be a combination: setting error classes on
the affected fields such that rendering the field results in a display of the error indicators.
This should be pretty easy, actually, since the validate() function is run on the per-field instance.

*/
	}

	public function Render( $indentLevel = 0 /* here just for compatibility; ignored. */ ){
		$br = new XHTMLElement( 'br' );
		$out = $this->renderOpen();
		foreach( $this->getFields() as $field ){
			$out.= Util::indentString( 2, $field->Render().$br->Render() );
		}
		$out.= Util::indentString( 2, $this->renderSubmit() );
		$out.= $this->renderClose();
		return( $out );
	}

	public function renderOpen(){
		// ender open form tag
		// render open fieldSet tag
		$noticesDiv = new XHTMLElement( 'div', $this->getJSName().'_notices' );
		$noticesDiv->setAutoClose( false );
		$fieldSet = new XHTMLElement( 'fieldset' );
		$html = $noticesDiv->Render()
			.$this->RenderOpenTag()."\n"
			.Util::indentString( 1, $fieldSet->RenderOpenTag( true ) )."\n";
		return( $html );
	}

	public function renderField( $fieldName ){
		$return = null;
		$field = $this->getField( $fieldName );
		if( $field instanceof FormField ){
			$return = $field->Render();
		}
		return( $return );
	}

	public function renderSubmit(){
		$submit = new FormSubmit();
		$reset = new FormButton();
		$reset->setValue( 'Reset' );
		$reset->setOnclick( 'this.form.reset(); window.'.$this->makeFormHandlerName().'.clearError();' );
		return( $submit->Render().$reset->Render() );
	}

	public function renderClose(){
		$fieldSet = new XHTMLElement( 'fieldset' );
		$html = Util::indentString( 1, $fieldSet->RenderCloseTag() )."\n"
			.$this->RenderCloseTag()."\n"
			.$this->renderValidationScript();
		return( $html );
	}

	public function renderValidationScript(){
		$script = new XHTMLElement( 'script' );
		$script->setAttribute( 'type', 'text/javascript' );
		$script->setAutoClose( false );
		$script->addContent(
			'//'.$this->CData(
				"\n".Util::indentString( 1, $this->renderHandlerJS() ).'//'
			)
		);
		return( $script->Render() );
	}

	public function renderHandlerJS(){
		$required = array();
		$tests = array();
		foreach( $this->getFields() as $field ){
			if( $field->isRequired() ){
				$required[] = $field->getJSName();
			}
			if( $validation = $field->getJSValidation() ){
				$tests[] = '{ test: '.(
					preg_match( '/\/[^\/]+\/i?/', $validation )
					? "'".$validation.'.test( this.getField( "'.$field->getJSName().'" ) )\''
					: $validation
				).",\n\t\t  message: '".(
					$msg = $field->getValidationMessage()
					? addslashes( $msg )
					: 'Invalid '.addslashes( $field->getName() )
				)."' }";
			}
		}
		$script = 'window.'.$this->makeFormHandlerName()." = new formHandler ( {\n"
			."\tform: '".addslashes( $this->getName() )."'"
			.( count( $required ) > 0 ? ",\n\trequired_fields: [ '".implode( "', '", $required )."' ]" : '' )
			.( count( $tests ) > 0 ? ",\n\tvalidation: [\n\t\t".implode( ",\n\t\t", $tests )."\n\t]" : '' )
			."\n} );\n";
		return( $script );
	}

	public function getJSName(){
		return( self::makeJSName( $this->getName() ) );
	}

	public function makeFormHandlerName(){
		return( 'fc'.ucfirst( self::makeJSName( $this->getName(), true ) ).'Handler' );
	}

	public static function makeJSName( $name, $stripSpaces = false ){
		return(
			strtolower(
				preg_replace(
					'/[^a-zA-Z0-9_]/', '', 
					preg_replace( '/ /', ( $stripSpaces ? '' : '_' ), $name )
				)
			)
		);
	}

	// Useful for small forms where everything's required and defaults are acceptable.
	public static function quickForm( $name, $action, array $fields ){
		$form = new Form( $name, $action );
		foreach( $fields as $name => $type ){
			$fieldType = 'Form'.ucwords( $type );
			if( !class_exists( $fieldType ) ){
				throw( new Exception( 'Unrecognized form field type "'.$type.'" requested' ) );
			}
			$field = new $fieldType( $name );
			$field->setRequired();
			$form->addField( $field );
		}
		return( $form );
	}
}

?>
