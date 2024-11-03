/* --------------------------------------------- 
* Filename:     custom.js
* Version:      1.0.0 (2016-08-06)
				1.0.2 (2024-01-09)
* Website:      https://www.zymphonies.com
* Description:  Global Script
* Author:       Zymphonies Team
                support@zymphonies.com
-----------------------------------------------*/

jQuery(document).ready(function($){

	$('.flexslider').flexslider({
    	animation: "slide"	
    });
	
	//Mobile menu toggle
	$('.navbar-toggle').click(function(){
		$('.region-primary-menu').slideToggle();
	});

	$('li.dropdown').click(function(){
		$(this).toggleClass('openSubMenu');
	  	$(this).find('ul').toggle();
	});
	
});