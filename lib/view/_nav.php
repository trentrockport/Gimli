<?PHP
$nav = array_merge(
	array(
		'Home' => '',
		'XHTML' => 'xhtml_test',
		'404\'d!' => 'expect/a/404',
		'Rest::ServerDump' => 'services/rest/serverDump',
		'Rest::sessionDump' => 'services/rest/sessionDump',
		'Rest::requestDump' => 'services/rest/requestDump?a=x&b=y&c=z'
	),
	( ctrl()->getUser() instanceof User
		? array(
			'Auth\'d Page' => 'authenticatedPage',
			'Logout' => 'login/logout'
		)
		: array( 'Login' => 'login' )
	)
);

$navDiv = new XHTMLElement( 'div', 'nav' );
$navDiv->setStyle( 'float: left; border: 2px outset; margin: 0em 1em 1em 0em; background-color: #CCC; width: 130px; height: 100%;' );
$prefix = SitePage::getConfig( 'pathPrefix' );
foreach( $nav as $key => $value ){
	$navEntry = new XHTMLElement( 'a' );
	$navEntry->setInline();
	$navEntry->setStyle( 'display: block; width: 120px; border: 1px solid #000; text-align: center; text-decoration: none; padding: 3px;' );
	$navEntry->setHref( $prefix.( !empty( $value ) && $value{0} != '/' ? '/' : '' ).$value );
	$navEntry->setContent( htmlentities( $key ) );
	$navDiv->addContent( $navEntry );
}
print $navDiv->Render();
?>
