<?PHP
class FormEmail extends FormField {
	protected $validationFunction = 'isValidEmailAddress';
	protected $validationRegex = '/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';
	protected $attributes = array( 'size' => 50 );
}

?>
