$(document).ready(function() {
    $(".tabsContSec").hide();
	$("ul.tabs li:first a").addClass("active").show();
	$(".tabsContSec:first").show();
	$("ul.tabs li a").click(function() {
		$("ul.tabs li a").removeClass("active");
		$(this).addClass("active");
		$(".tabsContSec").hide();
		var activeTab = $(this).attr("href");
		$(activeTab).fadeIn(500);
		return false;
	});


	$('.customer-convenience-owl').owlCarousel({
	    loop:false,
	    margin:25,
	    nav:true,
	    dots:false,
	    smartSpeed:500,
	    responsive:{
		    0:{
		        items:1,
		    },
		    500:{
		        items:2,
		    },
		    700:{
		        items:2,
		    },
		    1100:{
		        items:3,
		    },
		    1300:{
		        items:3,
		    }
	    }
	});

});
	