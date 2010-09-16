<?PHP
$notices = ctrl()->getNotices( null, true );
if( count( $notices ) ){
	$noticesDiv = new XHTMLElement( 'div', 'notices' );
	$closeControl = new XHTMLElement( 'a', 'noticeClose' );
	$closeControl->setHref( 'javascript: void( document.getElementById( \'notices\' ).innerHTML = \'\' );' );
	$closeControl->setContent( 'X' );
	$closeControl->setStyle( 'display: block; float: right; margin: 0.2em 0.2em 1em 1em;' );
	$noticesDiv->addContent( $closeControl );
	foreach(
		array(
			SiteController::NOTICE_ERROR,
			SiteController::NOTICE_URGENT,
			SiteController::NOTICE_STANDARD
		) as $definedType
	){
		if( array_key_exists( $definedType, $notices ) ){
			foreach( $notices{ $definedType } as $notice ){
				$noticeDiv = new XHTMLElement( 'div' );
				$noticeDiv->setClass( $definedType );
				$noticeDiv->setContent( $notice );
				$noticesDiv->addContent( $noticeDiv );
			}
			unset( $notices{ $definedType } );
		}
	}

	foreach( $notices as $noticeType => $typedNotices ){
		// TODO: Sort by type. Error first, urgent second, standard third - and then by anything else.
		foreach( $typedNotices as $notice ){
			$noticeDiv = new XHTMLElement( 'div' );
			$noticeDiv->setClass( $noticeType );
			$noticeDiv->setContent( $notice );
			$noticesDiv->addContent( $noticeDiv );
		}
	}

	print $noticesDiv->Render();
}
?>
