<?PHP
class FormPassword extends FormField {
	protected $type = FormField::TYPE_PASSWORD;
	protected $validationRegex = '/^[ -~]{2,}$/';
}

?>
