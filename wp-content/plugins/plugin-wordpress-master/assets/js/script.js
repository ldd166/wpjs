jQuery( document ).ready( function( $ ) {
	if ( 'undefined' === typeof window.ajaxurl ) {
		window.ajaxurl = '/wp-admin/admin-ajax.php';
	}

	/**
	 * jsDelivr CDN Admin Helper
	 *
	 * @type {*|Window.jsdelivrcdnHelper}
	 */
	window.jsdelivrcdnHelper = {
		$form: '',
		$table: '',
		$tableBody: '',

		/**
		 * Init
		 *
		 * @returns {Window.jsdelivrcdnHelper}
		 */
		init: function() {
			var _self = this;

			_self
				.initVars()
				.initButtons()
				.loadTableData();

			return _self;
		},

		/**
		 * Init vars
		 *
		 * @returns {Window.jsdelivrcdnHelper}
		 */
		initVars: function() {
			var _self = this;

			_self.$form = $( '#jsdelivrcdn_settings_form' );
			_self.$table = $( '#jsdelivrcdn-settings-table' );
			_self.$tableBody = _self.$table.find( 'tbody' );

			return _self;
		},

		/**
		 * Init Buttons
		 *
		 * @returns {Window.jsdelivrcdnHelper}
		 */
		initButtons: function() {
			var _self = this;

			//Select all rows
			$( '#select_all' ).on( 'click', function( e ) {
				e.stopPropagation();
				e.preventDefault();

				$( 'input[name*=source_list]' ).prop( 'checked', true );
			});

			//Unselect all rows
			$( '#unselect_all' ).on( 'click', function( e ) {
				e.stopPropagation();
				e.preventDefault();

				$( 'input[name*=source_list]' ).prop( 'checked', false );
			});

			//Remove jsDelivr url from all rows
			$( '#clear_source_list' ).on( 'click', function( e ) {
				var $dashicons;

				e.stopPropagation();
				e.preventDefault();

				$dashicons = $( this ).find( '.dashicons' );

				if ( window.confirm( 'Do you want to remove the stored jsDelivr URLs?' ) ) {
					$dashicons.removeClass( 'hidden' );
					$.post( window.ajaxurl, {
						'action': 'clear_source_list',
						'security': _self.$form.data( 'ajaxnonce' )
					}, function( response ) {
						if ( 'OK' === response.result ) {
							_self.$tableBody.find( 'td.jsdelivr_url' ).text( '' );
						}
						$dashicons.addClass( 'hidden' );
					}, 'json' );
				}
			});

			//Remove all stored data
			$( '#delete_source_list' ).on( 'click', function( e ) {
				var $dashicons;

				e.stopPropagation();
				e.preventDefault();

				$dashicons = $( this ).find( '.dashicons' );

				if ( window.confirm( 'Do you want to remove the stored source?' ) ) {
					$dashicons.removeClass( 'hidden' );
					$.post( window.ajaxurl, {
						'action': 'delete_source_list',
						'security': _self.$form.data( 'ajaxnonce' )
					}, function( response ) {
						if ( 'OK' === response.result ) {
							_self.$tableBody.empty();
						}
						$dashicons.addClass( 'hidden' );
					}, 'json' );
				}
			});

			//Clear jsdelivr url from row
			$( document ).on( 'click', '.clear_source', function( e ) {
				var $dashicons, $tr, handle;

				e.stopPropagation();
				e.preventDefault();

				$dashicons = $( this ).find( '.dashicons' );
				$tr = $( this ).closest( 'tr' );
				handle = $tr.prop( 'id' );

				if ( window.confirm( 'Do you want to remove the stored jsDelivr URL?' ) ) {
					$dashicons.removeClass( 'hidden' );
					$.post( window.ajaxurl, {
						'action': 'clear_source',
						'security': _self.$form.data( 'ajaxnonce' ),
						'handle': handle
					}, function( response ) {
						if ( 'OK' === response.result ) {
							$tr.find( 'td.jsdelivr_url' ).text( '' );
						}
						$dashicons.addClass( 'hidden' );
					}, 'json' );
				}
			});

			//Analyze data and load jsdelivr urls
			$( '#jsdelivr_analyze' ). on( 'click', function( e ) {
				var $dashicons;

				e.stopPropagation();
				e.preventDefault();

				$dashicons = $( this ).find( '.dashicons' );
				$dashicons.removeClass( 'hidden' );

				$.post( window.ajaxurl, {
					'action': 'jsdelivr_analyze',
					'security': _self.$form.data( 'ajaxnonce' )
				}, function( response ) {
					if ( 'OK' === response.result ) {
						$.each( response.data, function( index, url ) {
							_self.$tableBody.find( 'tr#' + index ).find( 'td.jsdelivr_url' ).text( url );
						});
					}
					$dashicons.addClass( 'hidden' );
				}, 'json' );
			});

			//Reload table rows
			$( '#reload' ).on( 'click', function( e ) {
				var $dashicons;

				e.stopPropagation();
				e.preventDefault();

				$dashicons = $( this ).find( '.dashicons' );
				$dashicons.removeClass( 'hidden' );

				_self.loadTableData( function() {
					$dashicons.addClass( 'hidden' );
				});
			});

			//Switch 'Advanced mode' option
			$( '#advanced_mode' ).on( 'change', function() {
				$.post( window.ajaxurl, {
					'action': 'advanced_mode_switch',
					'advanced_mode': $( this ).is( ':checked' ),
					'security': _self.$form.data( 'ajaxnonce' )
				}, function( response ) {
					if ( 'OK' === response.result ) {
						location.reload();
					}
				}, 'json' );
			});

			//Switch 'Autoenable' option
			$( '#autoenable' ).on( 'change', function() {
				$.post( window.ajaxurl, {
					'action': 'autoenable_switch',
					'autoenable': $( this ).is( ':checked' ),
					'security': _self.$form.data( 'ajaxnonce' )
				}, function( response ) {
					if ( 'OK' !== response.result ) {
						$( this ).prop( 'checked', ! $( this ).is( ':checked' ) );
					}
				}, 'json' );
			});

			//Save Active\Inactive table rows
			$( '#submit' ).on( 'click', function( e ) {
				var $dashicons;

				e.stopPropagation();
				e.preventDefault();

				$dashicons = $( this ).find( '.dashicons' );
				$dashicons.removeClass( 'hidden' );
				$.post( window.ajaxurl, {
					'action': 'save_form',
					'source_list': $.map( $( '[name*=source_list]:checked' ), function( item ) {
						return item.dataset.index;
					}).join( ',' ),
					'security': _self.$form.data( 'ajaxnonce' )
				}, function( response ) {
					if ( 'OK' === response.result ) {
						$dashicons.addClass( 'hidden' );
					} else {
						_self.loadTableData( function() {
							$dashicons.addClass( 'hidden' );
						});
					}
				}, 'json' );
			});

			return _self;
		},

		/**
		 * Load table
		 *
		 * @returns {Window.jsdelivrcdnHelper}
		 */
		loadTableData: function( callback ) {
			var _self = this;
			var $tr, $td, html;

			_self.$tableBody.empty();

			$.post( window.ajaxurl, {
				'action': 'get_source_list',
				'security': _self.$form.data( 'ajaxnonce' )
			}, function( response ) {
				var i = 0;

				if ( 'OK' === response.result ) {
					$.each( response.data, function( index, data ) {
						i++;
						$tr = $( '<tr></tr>' )
							.attr( 'id', index )
							.appendTo( _self.$tableBody );

						//Number
						$td = $( '<td></td>' )
							.addClass( 'check-column text-center' )
							.append( i )
							.appendTo( $tr );

						//Active
						html = '<input name="source_list[' + index + ']" data-index="' + index + '" type="checkbox" ' +
							'title="Active" value="1" ' + ( data.active ? 'checked' : '' ) + '>';
						$td = $( '<td></td>' )
							.addClass( 'check-column text-center' )
							.append( html )
							.appendTo( $tr );

						if ( 'undefined' !== typeof data.handle ) {

							//Name
							$td = $( '<td></td>' )
								.text( data.handle )
								.appendTo( $tr );
						}

						if ( 'undefined' !== typeof data.ver ) {

							//Version
							$td = $( '<td></td>' )
								.text( data.ver ? data.ver : '' )
								.appendTo( $tr );
						}

						//Origin
						$td = $( '<td></td>' )
							.text( data.original_url )
							.appendTo( $tr );

						//jsDelivr
						$td = $( '<td></td>' )
							.addClass( 'jsdelivr_url' )
							.text( data.jsdelivr_url )
							.appendTo( $tr );

						if ( 'undefined' !== typeof data.ver ) {
							html = '<button class="button button-primary clear_source" ><span class="dashicons dashicons-update spin hidden"></span> Clear</button>';
							$td = $( '<td></td>' )
								.append( html )
								.appendTo( $tr );
						}

					});
				}

				if ( 'function' === typeof callback ) {
					callback();
				}
			}, 'json' );

			return _self;
		}

	}.init();
});
