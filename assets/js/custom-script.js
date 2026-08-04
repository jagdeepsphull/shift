( function ( $ )
{
	"use strict"
	$( document ).ready( function ()
	{
		//Loader
		setTimeout( () =>
		{
			$( '#ctn-preloader' ).addClass( 'loaded' );
		}, 1000 )

		//Currency Selection...
		$( ".currency-selection" ).select2( {
			templateSelection : function formatState ( state )
			{
				if ( ! state.id )
				{
					return state.text;
				}

				var baseUrl = "assets/images/resources/flags";
				var $state = $(
					'<span><img class="img-flag" /> <span></span></span>'
				);

				// Use .text() instead of HTML string concatenation to avoid script injection issues
				$state.find( "span" ).text( state.text );
				$state.find( "img" ).attr( "src", baseUrl + "/" + state.element.value.toLowerCase() + ".png" );

				return $state;
			},
			width             : '105px'
		} );
		$( ".filter-selection" ).select2( {
			width                   : '230px',
			minimumResultsForSearch : - 1
		} );
		$( ".country_select" ).select2( {
			placeholder             : "Country",
			width                   : '100%',
			minimumResultsForSearch : - 1
		} );
		$( ".city_select" ).select2( {
			placeholder             : "City",
			width                   : '100%',
			minimumResultsForSearch : - 1
		} );
		$( ".state_select" ).select2( {
			placeholder             : "State / Province",
			width                   : '100%',
			minimumResultsForSearch : - 1
		} );
		$( "input[type='number']" ).inputSpinner();

		$( '.mobile-menu-icon' ).click( function ()
		{
			$( 'html' ).addClass( "html active" );
		} );
		$( '.cross-btn' ).click( function ()
		{
			$( 'html' ).removeClass( "html active" );
		} );

		$( window ).scroll( function ()
		{
			if ( $( window ).scrollTop() > 200 )
			{
				$( ".header-sticky" ).addClass( "sticky" );
			}
			else
			{
				$( ".header-sticky" ).removeClass( "sticky" );
			}
		} )

		// Mobile Menu..
		$( '.mobile-menu-icon' ).on( 'click', function ()
		{
			$( '.navigation-wrapper' ).addClass( 'active' );
			return false;
		} );
		// Stop Propagation on Menu Click
		$( '.navigation-wrapper' ).on( 'click', function ( event )
		{
			event.stopPropagation();
		} );
		// Toggle Submenu
		$( 'nav li.menu-item-has-children > a' ).on( 'click', function ()
		{
			$( this ).parent().siblings( 'li' ).children( 'ul' ).slideUp();
			$( this ).parent().siblings( 'li' ).removeClass( 'active' );
			$( this ).parent().children( 'ul' ).slideToggle();
			$( this ).parent().toggleClass( 'active' );
			return false;
		} );
		// Handling ".menu-close" Button Click
		$( '.cross-btn' ).on( 'click', function ()
		{
			$( '.navigation-wrapper' ).removeClass( 'active' );
			return false;
		} );

		//Tooltip ..
		const tooltipTriggerList = document.querySelectorAll( '[data-toggle="tooltip"]' )
		const tooltipList = [ ...tooltipTriggerList ].map( tooltipTriggerEl => new bootstrap.Tooltip( tooltipTriggerEl ) )

		//Grid To List...
		$( "#view_style > button" ).on( "click", function ()
		{
			let viewID = $( "#post_view" );
			$( "#view_style > button" ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			viewID.removeClass( 'grid' );
			viewID.removeClass( 'list' );
			viewID.addClass( $( this ).attr( 'data-view' ) );
		} );

		// Counter Script...
		const days = $( '#days' );
		const hours = $( '#hours' );
		const minutes = $( '#minutes' );
		const seconds = $( '#seconds' );

		const currentYear = new Date().getFullYear();
		const newYearTime = new Date( `January 01 ${ currentYear + 1 } 00:00:00` );

		function updateCountdown ()
		{
			const currentTime = new Date();
			const diff = newYearTime - currentTime;

			const d = Math.floor( diff / 1000 / 60 / 60 / 24 );
			const h = Math.floor( diff / 1000 / 60 / 60 ) % 24;
			const m = Math.floor( diff / 1000 / 60 ) % 60;
			const s = Math.floor( diff / 1000 ) % 60;

			days.text( d );
			hours.text( h < 10 ? '0' + h : h );
			minutes.text( m < 10 ? '0' + m : m );
			seconds.text( s < 10 ? '0' + s : s );
		}

		setInterval( updateCountdown, 1000 );

		//Newsletter Auto Modal...
		/* setTimeout( () => {$( '#Newsletter_Modal' ).modal( 'show' );}, 6000 ) */

		// Slider Script...
		$( '.banner-slider' ).slick( {
			autoplay      : true,
			autoplaySpeed : 5000,
			speed         : 1000,
			arrows        : false,
			dots          : true,
			onhover       : true,
			fade          : true,
			appendDots    : $( '.banner-dots' )
		} );

		$( '.banner-slider2' ).slick( {
			autoplay      : true,
			autoplaySpeed : 5000,
			speed         : 1000,
			arrows        : false,
			dots          : true,
			onhover       : true,
			appendDots    : $( '.banner-dots' )
		} );

		$( '.product-slider' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : false,
			dots           : true,
			slidesToShow   : 5,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [ {
				breakpoint : 1200,
				settings   : {
					slidesToShow   : 3,
					slidesToScroll : 1,
					infinite       : true
				}
			},
							   {
								   breakpoint : 992,
								   settings   : {
									   slidesToShow   : 3,
									   slidesToScroll : 1,
									   infinite       : true
								   }
							   },
							   {
								   breakpoint : 767,
								   settings   : {
									   slidesToShow   : 2,
									   slidesToScroll : 1
								   }
							   },
							   {
								   breakpoint : 576,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   },
							   {
								   breakpoint : 480,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   }
			]
		} );
		$( '.product-slider2' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : true,
			dots           : false,
			prevArrow      : '.prev-btn',
			nextArrow      : '.nxt-btn',
			slidesToShow   : 3,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [ {
				breakpoint : 1200,
				settings   : {
					slidesToShow   : 3,
					slidesToScroll : 1,
					infinite       : true
				}
			},
							   {
								   breakpoint : 992,
								   settings   : {
									   slidesToShow   : 2,
									   slidesToScroll : 1,
									   infinite       : true
								   }
							   },
							   {
								   breakpoint : 767,
								   settings   : {
									   slidesToShow   : 2,
									   slidesToScroll : 1
								   }
							   },
							   {
								   breakpoint : 500,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   }

			]
		} );

		$( '.product-slider3' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : false,
			dots           : true,
			slidesToShow   : 4,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [
				{
					breakpoint : 1200,
					settings   : {
						slidesToShow   : 3,
						slidesToScroll : 1,
						infinite       : true
					}
				},
				{
					breakpoint : 767,
					settings   : {
						slidesToShow   : 2,
						slidesToScroll : 1
					}
				},
				{
					breakpoint : 500,
					settings   : {
						slidesToShow   : 1,
						slidesToScroll : 1
					}
				}
			]
		} );
		$( 'button[data-bs-toggle="tab"]' ).on( 'click', function ()
		{
			let active_tab = $( this ).attr( "data-bs-target" );
			let slideset = setInterval( () =>
			{
				if ( $( `${ active_tab } .slick-track` ).width() <= 0 )
				{
					$( '#ctn-preloader' ).removeClass( 'loaded' );
				}
				else
				{
					setTimeout(()=>{$( '#ctn-preloader' ).addClass( 'loaded' );}, 1000)
					clearInterval( slideset );
				}
			}, 100 )
		} );

		$( '.season-slider' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : false,
			dots           : false,
			slidesToShow   : 3,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [ {
				breakpoint : 1200,
				settings   : {
					slidesToShow   : 3,
					slidesToScroll : 1,
					infinite       : true
				}
			},
							   {
								   breakpoint : 992,
								   settings   : {
									   slidesToShow   : 2,
									   slidesToScroll : 1,
									   infinite       : true
								   }
							   },
							   {
								   breakpoint : 480,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   }
			]
		} );

		$( '.testimonial-review' ).slick( {
			infinite      : true,
			speed         : 800,
			autoplaySpeed : 3000,
			autoplay      : true,
			arrows        : false,
			dots          : false,
			slidesToShow  : 1,
			fade          : true,
			onhover       : true,
			appendDots    : $( '.testimonial-dots' )
		} );

		$( '.testimonial-slider2' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : false,
			dots           : true,
			slidesToShow   : 2,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [ {
				breakpoint : 1200,
				settings   : {
					slidesToShow   : 2,
					slidesToScroll : 1,
					infinite       : true
				}
			},
							   {
								   breakpoint : 992,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1,
									   infinite       : true
								   }
							   },
							   {
								   breakpoint : 480,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   }
			]
		} );

		$( '.testimonial-slider3' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : false,
			dots           : true,
			slidesToShow   : 1,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [ {
				breakpoint : 1200,
				settings   : {
					slidesToShow   : 1,
					slidesToScroll : 1,
					infinite       : true
				}
			},
							   {
								   breakpoint : 992,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1,
									   infinite       : true
								   }
							   },
							   {
								   breakpoint : 480,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   }
			]
		} );

		$( '.product-sider-simple' ).slick( {
			infinite       : true,
			speed          : 800,
			autoplaySpeed  : 4500,
			autoplay       : true,
			arrows         : false,
			dots           : false,
			slidesToShow   : 3,
			slidesToScroll : 1,
			onhover        : true,
			responsive     : [ {
				breakpoint : 1200,
				settings   : {
					slidesToShow   : 3,
					slidesToScroll : 1,
					infinite       : true
				}
			},
							   {
								   breakpoint : 992,
								   settings   : {
									   slidesToShow   : 2,
									   slidesToScroll : 1,
									   infinite       : true
								   }
							   },
							   {
								   breakpoint : 375,
								   settings   : {
									   slidesToShow   : 1,
									   slidesToScroll : 1
								   }
							   }
			]
		} );

		$( '.product-detail-for' ).slick( {
			slidesToShow   : 1,
			slidesToScroll : 1,
			arrows         : false,
			fade           : true,
			asNavFor       : '.product-detail-nav'
		} );

		$( '.product-detail-nav' ).slick( {
			slidesToShow    : 3,
			slidesToScroll  : 1,
			asNavFor        : '.product-detail-for',
			dots            : false,
			centerMode      : false,
			vertical        : true,
			verticalSwiping : true,
			focusOnSelect   : true,
			arrows          : false,
			responsive      : [ {
				breakpoint : 768,
				settings   : {
					slidesToShow : 3
				}
			},
								{
									breakpoint : 576,
									settings   : {
										vertical : false
									}
								}
			]

		} );

		$( '.product-detail-for-horizontal' ).slick( {
			slidesToShow   : 1,
			slidesToScroll : 1,
			arrows         : false,
			fade           : true,
			asNavFor       : '.product-detail-nav-horizontal'
		} );

		$( '.product-detail-nav-horizontal' ).slick( {
			slidesToShow    : 3,
			slidesToScroll  : 1,
			asNavFor        : '.product-detail-for-horizontal',
			dots            : false,
			centerMode      : false,
			verticalSwiping : true,
			focusOnSelect   : true,
			arrows          : false,
			responsive      : [ {
				breakpoint : 768,
				settings   : {
					slidesToShow : 3
				}
			},
								{
									breakpoint : 576,
									settings   : {
										vertical : false
									}
								}
			]

		} );

		const $progress = $( ".range-bar" );
		const $value = $( ".value" );
		const $progressbar = $( ".progress-bar" );

		if ( $progress.length && $value.length && $progressbar.length )
		{
			// Initialize value
			$value.text( $progress.val() );
			$progressbar.val( $progress.val() );

			// Update values on input
			$progress.on( "input", function ()
			{
				$value.text( $( this ).val() );
				$progressbar.val( $( this ).val() );
			} );
		}

		AOS.init( {
			once : true
		} );
	} );

} )( jQuery );
