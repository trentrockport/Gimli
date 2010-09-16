function formHandler( aCollection ){
	// BEGIN CONSTRUCTOR OPERATION
	this.collection = ( aCollection ? aCollection : {} );
	this.form = {
		elements: Array()
	};
	if( this.collection.form ){
		if( this.collection.form.elements ){
			this.form = this.collection.form;
		} else if( this.collection.form.charAt || !isNaN( this.collection.form ) ){
			this.form = document.forms[ this.collection.form ];
		}
	}
	// END CONSTRUCTOR OPERATION
	// BEGIN MEMBER METHOD DEFINITION
	// HELPER FUNCTIONS
	this.enc = function( aValue ){
		return( escape( aValue ).replace( /%20/g, '+' ) );
	}

	this.getField = function( fieldName ){
		var retVal;
		var elem = this.form.elements[ fieldName ];
		if( !elem ){
			return retVal;
		}
		if( elem.length && !elem.type ){
			elem = elem[0];
		}
		switch( elem.type ){
			case 'button':
			case 'hidden':
			case 'password':
			case 'text':
			case 'textarea':
			case 'image':
			case 'select-one':
				retVal = elem.value;
				break;
			case 'radio':
				elem = this.form.elements[ fieldName ];
				if ( elem.length ) {
					for( var i = 0; i < elem.length; i++ ){
						if( elem[i].checked ){
							retVal = elem[i].value;
							break;
						}
					}
				} else {
					if( elem.checked ){
						retVal = elem.value;
					}
				}
				break;
			case 'checkbox':
				if( this.form.elements[ fieldName ].length && this.form.elements[ fieldName ].length > 1 ){
					retVal = new Array();
					elem = this.form.elements[ fieldName ];
					for( var i = 0; i < elem.length; i++ ){
						if( elem[i].checked && !elem[i].disabled  ){
							retVal.push( ( elem[i].value.length > 0 ? elem[i].value : 'on' ) );
						}
					}
				} else {
					if( elem.checked && !elem.disabled ){
						retVal = ( elem.value.length > 0 ? elem.value : 'on' );
					}
				}
				break;
			case 'select-multiple':
				retVal = new Array();
				for( var i = 0; i < elem.options.length; i++ ){
					if( elem.options[i].selected ){
						retVal.push( elem.options[i].value );
					}
				}
				break;
			default:
				break;
		}
		return ( retVal ? retVal : '' );
	}

	this.setField = function( fieldName, aValue ){
		var elem = this.form.elements[ fieldName ];
		if( !elem ){
			return;
		}
		if( elem.length && !elem.type ){
			elem = elem[0];
		}
		if( typeof( aValue ) == 'undefined' ){
			aValue = '';
		}
		switch( elem.type ){
			case 'button':
			case 'hidden':
			case 'image':
			case 'password':
			case 'text':
			case 'textarea':
				elem.value = aValue;
				break;
			case 'checkbox':
				aValue = ( aValue == '' ? false : aValue );
				elem.value = ( typeof( aValue ) != 'boolean' ? aValue : elem.value );
				elem.checked = ( aValue ? true : false );
				break;
			case 'radio':
				elem = this.form.elements[ fieldName ];
				if( elem.length ){
					for( var i = 0; i < elem.length; i++ ){
						elem[i].checked = false;
					}
					for( var i = 0; i < elem.length; i++ ){
						if( elem[i].value == aValue ){
							elem[i].checked = true;
						}
					}
				} else {
					if( elem.value == aValue ){
						elem[i].checked = true;
					}
				}
				break;
			case 'select-one':
				for( var i = 0; i < elem.options.length; i++ ){
					if( elem.options[i].value == aValue ){
						elem.selectedIndex = i;
						break;
					}
				}
				break;
			case 'select-multiple':
				// Clear existing settings
				for( var i = 0; i < elem.options.length; i++ ){
					elem.options[i].selected = false;
				}
				// Handle an array
				if( aValue.push ){
					for( var i = 0; i < aValue.length; i++ ){
						for( var j = 0; j < elem.options.length; j++ ){
							if( elem.options[j].value == aValue[i] ){
								elem.options[j].selected = true;
							}
						}
					}
				} else {
					// Handle a single value
					for( var i = 0; i < elem.options.length; i++ ){
						if( elem.options[i].value == aValue ){
							elem.selectedIndex = i;
						}
					}
				}
				break;
			default:
				break;
		}
	}
	
	this.gatherForm = function( subElem ){
		var elems = this.form.elements;
		var content = new Array();
		var loop_content;
		var handled_radios = new Array();
		for( var i = 0; i < elems.length; i++ ){
			if( elems[i].type == 'image' || !elems[i].type ){
				continue;
			}
			if( elems[i].type == 'radio' || elems[i].type == 'checkbox' ){
				var radio_skip = false;
				for( var j = 0; j < handled_radios.length; j++ ){
					if( handled_radios[j] == elems[i].name ){
						radio_skip = true;
					}
				}
				if( radio_skip ){
					continue;
				} else {
					handled_radios.push( elems[i].name );
				}
			}
			loop_content = this.getField( elems[i].name );
			if( typeof( loop_content ) == 'undefined' || ( loop_content == '' && ( elems[i].type == 'checkbox' || elems[i].type == 'radio' ) ) ){
				continue;
			}
			if( loop_content && loop_content.push ){
				for( var j = 0; j < loop_content.length; j++ ){
					content.push( this.enc( elems[i].name ) + "=" + this.enc( loop_content[j] ) );
				}
			} else {
				content.push( this.enc( elems[i].name ) + "=" + this.enc( loop_content ) );
			}
		}
		if( subElem ){
			loop_content = this.enc( subElem.name );
			switch( subElem.type ){
				case 'image':
					var eName = loop_content;
					loop_content+= '.x=3';
					content.push( loop_content );
					lopp_content = eName + '.y=5';
					content.push( loop_content );
					if( subElem.value ){
						loop_content = eName + '=' + this.enc( subElem.value );
						content.push( loop_content );
					}
					break;
				default:
					loop_content+= '=' + this.enc( subElem.value );
					content.push( loop_content );
					break;
			}
		}
		return content;
	}

	// OPERATIONAL MEMBER METHODS
	this.clearError = function(){
		var noticeDiv = document.getElementById( this.form.name + '_notices' );
		if( noticeDiv ){
			noticeDiv.innerHTML = '';
			noticeDiv.style.display = 'none';
		}
	}
	this.reportError = function( errMsg ){
		var noticeDiv = document.getElementById( this.form.name + '_notices' );
		if( noticeDiv ){
			noticeDiv.innerHTML = '<h3>The following errors are preventing this form\'s submission:</h3><ul><li>' + errMsg.join( '</li><li>' ) + '</li></ul>';
			noticeDiv.style.display = 'block';
		} else {
			alert( "The following errors are preventing this\nform from submitting:\n- " + errMsg.join( "\n- " ) );
		}
	}

	this.validate = function(){
		var passFail = true;
		var errMsg = Array();
		this.clearError();
		if( this.collection.required_fields && this.collection.required_fields.length ){
			var reqd = this.collection.required_fields;
			var reqd_pass = true;
			for( var i = 0; i < reqd.length; i++ ){
				var field_value = this.getField( reqd[i] );
				if( field_value == '' ){
					reqd_pass = false;
					break;
				}
			}
			if( !reqd_pass ){
				passFail = false;
				errMsg.push( "One or more required fields are empty." );
			}
		}
		if( passFail ){
			if( this.collection.validation && this.collection.validation.length ){
				var vtests = this.collection.validation;
				for( var i = 0; i < vtests.length; i++ ){
					var loop_pass = true;
					if( typeof( vtests[i].test ) == 'function' ){
						loop_pass = vtests[i].test( this );
					} else {
						loop_pass = eval( vtests[i].test );
					}
					if( !loop_pass ){
						passFail = false;
						errMsg.push( vtests[i].message );
					}
				}
			}
		}
		if( !passFail ){
			this.reportError( errMsg );
		}
		return passFail;
	}
	// An array returned from the preSubmit function
	// in the collection will be added to the contents
	// of the fauxSubmit() returned array.
	this.preSubmit = function(){
		if( this.collection.preSubmit ){
			return this.collection.preSubmit( this );
		}
	}
	// Check for preSubmit() contents and merge or
	// ignore as appropriate.
	this.fauxSubmit = function( subElem ){
		if( this.validate() ){
			var retVal = this.preSubmit();
			// Must be an array to be merged.
			if(
				typeof( retVal ) != 'undefined'
				&& typeof( retVal.push ) == 'function'
			){
				var gathered = this.gatherForm( subElem );
				for( var i = 0; i < gathered.length; i++ ){
					retVal.push( gathered[i] );
				}
			} else {
				retVal = this.gatherForm( subElem );
			}
			return retVal;
		} else {
			return false;
		}
	}
	this.submit = function(){
		if( this.validate() ){
			this.preSubmit();
			return this.form.submit();
		}
	}
	// END MEMBER METHOD DEFINITION
}

var aFormCollection = {
	validation: [
		{
			test: '/x/.test( this.getField( "x" )',
			message: "- Field X failed regex."
		}
	],
	required_fields: [
		''
	],
	preSubmit: function(){
		//alert( "Here I am!" );
	}
}
