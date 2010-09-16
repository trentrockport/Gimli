<?PHP
$br = new XHTMLElement( 'br' );

$x = new XHTMLElement( 'div', 'someDiv' );
$x->setAutoClose( false );
$x->style = 'background-color: #CFC;';
$x->addContent( 'The toast says MOOOOO' );
$x->addContent( $br );
$x->addContent( 'More stuff.' );
$x->addContent( $br );

$y = new XHTMLElement( 'span' );
$y->setInline();
$y->setStyle( 'font-weight: bold; font-variant: small-caps;' );
$y->setContent( 'Emphasis, to the rescue!' );

$x->addContent( $y );

print $x->Render();
?>
