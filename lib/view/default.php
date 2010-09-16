<?PHP $prefix = thisPage()->getConfig( 'pathPrefix' ); ?>
<div style="margin-left: 140px;">
	<h1>How to test basic elements</h1>
	<ol>
		<li>
			Attempt to access an <a href="<?PHP echo $prefix; ?>/authenticatedPage">authenticated page</a> without authentication, and see the Login page content.<br/>
			Log in using any RFC formatted email address and any password of 2 characters or more.  Or not, and see the form field validation in action.
		</li>
		<li>Log-out and repeat access attempt to confirm appropriate restriction.</li>
		<li>
			Go to a <a href="<?PHP echo $prefix; ?>/xhtml_test">controllerless page</a>, which sets user notices that will become apparent on any subsequent page view.<br/>
			XHTML Element Test looks pretty dull, unless you can see the PHP source.
		</li>
		<li>View source on anything, and notice that it's all auto-indented.</li>
		<li>View URLs and note the construction: /(controller|content)(/(action|target))?</li>
		<li>
			Play around with the REST services to see what they provide (not much at the moment) - you'll want to view source in order to see the output.
			XML is not guaranteed to be well formed at this point.
			<ul>
				<li><a href="<?PHP echo $prefix; ?>/services/rest/serverDump">serverDump</a>: wraps <code>$_SERVER</code> in auto-generated XML.</li>
				<li><a href="<?PHP echo $prefix; ?>/services/rest/sessionDump">sessionDump</a>: wraps <code>$_SESSION</code> in XML too - empty unless you've loggedin.</li>
				<li><a href="<?PHP echo $prefix; ?>/services/rest/requestDump?a=x&b=y&c=z">requestDump</a>: wraps <code>$_REQUEST</code> in XML too - play with the GET string.</li>
			</ul>
		</li>
	</ol>
</div>
