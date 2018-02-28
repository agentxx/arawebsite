/*
 * AIT WordPress Theme
 *
 * Copyright (c) 2012, Affinity Information Technology, s.r.o. (http://ait-themes.com)
 */
var burgerMenuData = [{selectors: ['.header-container'], reservedSelectors: ['li.menu-item-wrapper']}, {selectors: ['.sticky-menu .grid-main'], reservedSelectors: ['li.menu-item-wrapper', '.sticky-menu .site-logo']}];

/* Main Initialization Hook */
jQuery(document).ready(function(){
	gm_authFailure = function(){
		var apiBanner = document.createElement('div');
		var a = document.createElement('a');
		var linkText = document.createTextNode("Read more");
		a.appendChild(linkText);
		a.title = "Read more";
		a.href = "https://www.ait-themes.club/knowledge-base/google-maps-api-error/";
		a.target = "_blank";

		apiBanner.className = "alert alert-info";
		var bannerText = document.createTextNode("Please check Google API key settings");
		apiBanner.appendChild(bannerText);
		apiBanner.appendChild(document.createElement('br'));
		apiBanner.appendChild(a);

		jQuery(".google-map-container").html(apiBanner);
	};

	/* menu.js initialization */
	desktopMenu();
	responsiveMenu();
	relocateSiteTools();

	
	/* menu.js initialization */

	/* portfolio-item.js initialization */
	portfolioSingleToggles();
	/* portfolio-item.js initialization */

	/* custom.js initialization */
	touchFriendlyHover([
		".reviews-container .review-rating-overall"
	]);

	enableResponsiveToggleAreas(true);

	renameUiClasses();
	removeUnwantedClasses();

	initWPGallery();
	initColorbox();
	initRatings();
	initInfieldLabels();
	initSelectBox();

	notificationClose();
	initCustomScroll();
	/* custom.js initialization */

	/* Theme Dependent Functions */
	// telAnchorMobile();
	headerLayoutSize();
	/* Theme Dependent Functions */
});
/* Main Initialization Hook */

jQuery(window).load(function(){
	//prepareFitMenu();
	//fitMenu();
	prepareBurgerMenus(burgerMenuData);
	burgerMenus(burgerMenuData);
});

/* Window Resize Hook */
jQuery(window).resize(function(){
	headerLayoutSize();
	relocateSiteTools();

	burgerMenus(burgerMenuData);
	//fitMenu();
});
/* Window Resize Hook */

/* Theme Dependenent Functions */


function getLatLngFromAddress(address){
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({'address': address}, function(results, status){
		console.log(status);
		console.log(results[0].geometry.location);
		return results[0].geometry.location;
	});

}

// function telAnchorMobile(){
// 	if (isUserAgent('mobile')) {
// 		jQuery("a.phone").each(function() {
// 			this.href = this.href.replace(/^callto/, "tel");
// 		});
// 	}
// }

function headerLayoutSize(){
	// check for search form version
	if(jQuery('body').hasClass('search-form-type-3')){
		var $container = jQuery('.header-layout');
		var $elementWrap = $container.find('.header-element-wrap');
		var $searchWrap = $container.find('.header-search-wrap');

		if($searchWrap.height() > $elementWrap.height()){
			$container.addClass('search-collapsed');
		} else {
			$container.removeClass('search-collapsed');
		}
	}

	if(jQuery('body').hasClass('search-form-type-4')){
		var $container = jQuery('.header-layout');
		var $elementWrap = $container.find('.header-element-wrap');
		var $searchWrap = $container.find('.header-search-wrap > .elm-search-form-main');

		if($searchWrap.height() > $elementWrap.height()){
			$container.addClass('search-collapsed');
		} else {
			$container.removeClass('search-collapsed');
		}
	}
}

/* Hide WooCommerce cart when other header buttons clicked and vice versa */
jQuery(document).on('touchFriendlyHover_HideOthers', function(){
	jQuery('#ait-woocommerce-cart').css({
		display: 'none',
		opacity: '0'
	});
});
jQuery('#ait-woocommerce-cart-wrapper').hover(function(){
	jQuery(this).parents('.top-bar-tools').find('.hover').removeClass('hover');
});

/* Theme Dependenent Function */
