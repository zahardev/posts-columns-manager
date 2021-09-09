jQuery( document ).ready( function( $ ) {

	function init_accordion_settings(){
		let $form   = $( '#pcm-settings' ).find( 'form' ),
			 $titles = $form.find( 'h2' );

		$form.find( 'table' ).hide();
		$titles.addClass( 'collapsed' );

		$titles.click( function() {
			let $title = $( this ),
				 $table = $title.next( 'table' ),
				 $tbody = $table.find('tbody');

			if ( $title.hasClass( 'collapsed' ) ) {
				$table.css({
					display:'block',
					position: 'absolute',
				});

				$tbody.show();

				let height = $tbody.innerHeight();

				$tbody.hide();
				$table.attr('style', 'display:none; height:' + height + 'px');

				$table.slideDown();
				setTimeout(function(  ){
					$tbody.show();
				}, 300);

			} else {
				$tbody.hide();
				$table.slideUp();
			}

			$title.toggleClass( 'collapsed' );
		} );
	}

	function init(){
		init_accordion_settings();
	}

	init();
} );
