jQuery(function ($) {
    $(document).ready(function(){
    	// Slider inhouse ads Analytics
		$('.elm-easy-slider-main li a').click(function(e) {
			if (e.currentTarget.nodeName.toLowerCase() === 'a') {
				var href = e.currentTarget.href;
				if (href && href.length > 1) {
					// Clean last slash
					if (href.lastIndexOf('/') === (href.length-1))
						href = href.substring(0, href.length -1);
					
					href = href.substring(href.lastIndexOf('/')+1, href.length).toLowerCase();
					
					ga('send', {
						hitType: 'event',
						eventCategory: 'uxmeasure',
						eventAction: 'inhouse-ad-click',
						eventLabel: href
					});
				}
			}
		});
		
		$('.main-search-form input.searchsubmit').click(function(e) {
			ga('send', {
				hitType: 'event',
				eventCategory: 'uxmeasure',
				eventAction: 'search-click'
			});
		});
		
		$('#menu-main-menu .menu-item a').click(function(e) {
			if (e.currentTarget.nodeName.toLowerCase() === 'a') {
				var href = e.currentTarget.href;
				if (href && href.length > 1) {
					// Clean last slash
					if (href.lastIndexOf('/') === (href.length-1))
						href = href.substring(0, href.length -1);
					
					href = href.substring(href.lastIndexOf('/')+1, href.length).toLowerCase();
					
					ga('send', {
						hitType: 'event',
						eventCategory: 'uxmeasure',
						eventAction: 'main-menu-click',
						eventLabel: href
					});
				}
			}	
		});
		
		// Newsletter signup from bottom
		$('#mc4wp-form-1 input[type="submit"]').click(function(e) {
			ga('send', {
				hitType: 'event',
				eventCategory: 'formsubmit-measure',
				eventAction: 'newsletter-send'
			});
		});
		
		// Measure submit in contact page
		if (window.location.pathname.indexOf('contact') > -1) {
			$('.input-submit input.input-required').click(function(e) {
				ga('send', {
					hitType: 'event',
					eventCategory: 'formsubmit-measure',
					eventAction: 'contact-send'
				});
			});
		}
    })
})
