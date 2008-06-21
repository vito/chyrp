$(function(){
	$(".post .content, .page .content, .comments li blockquote").each(function(){
		$(this).find("p:last").css("marginBottom", 0)
	})
	$(".post .content:last").css("marginBottom", "1em")

	// Uncomment for debugging.
	// $("body").addClass("showgrid")
	// $("#page").css("background", "rgba(255,0,0,.25)")
	// $("#header a").css("background", "rgba(0,255,0,.25)")
	// $("#header p").css("background", "rgba(0,0,255,.25)")
	// $("#sidebar h2").css("background", "rgba(255,255,0,.25)")
})