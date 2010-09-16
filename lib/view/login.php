<?PHP
if( ctrl()->getUser() instanceof User ){
	ctrl()->redirect( thisPage()->getConfig( 'pathPrefix' ).thisPage()->getDefaultRedirectPage() );
	exit;
}
?>
<div style="float: left;">
	Login<br/><br/>
	Use any RFC valid formatted email address and a 2+ character password to login.<br/><br/>
	Or not, and see the form validation in action.<br/><br/>
	<?PHP echo thisPage()->getLoginForm()->Render(); ?>
</div>
