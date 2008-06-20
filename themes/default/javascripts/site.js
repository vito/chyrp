$(function(){
	$("#sidebar ul").each(function(){
		$(this).find("li:even").css("background", "#e0e0e0")
	})
	$(".info").each(function(){
		$(this).find("li:odd").css("background", "#e5e5e5")
	})
	$(".post .content, .page .content, .comments li blockquote").each(function(){
		$(this).find("p:last").css("marginBottom", 0)
	})
	$(".post .content:last").css("marginBottom", "1em")
	$(".post:has(h1), .page").each(function(){
		$(this).find(".content").css({
			"-webkit-border-top-left-radius": 0,
			"-webkit-border-top-right-radius": 0
		})
	})
	$(".info").each(function(){
		$(this).css({
			width: $(this).width() + $(this).offset().left,
			position: "absolute",
			left: 0,
			top: $(this).offset().top,
			marginLeft: 0,
			marginTop: 0
		})
	})

	// Uncomment for debugging.
	// $("body").addClass("showgrid")
	// $("#page").css("background", "rgba(255,0,0,.25)")
	// $("#header a").css("background", "rgba(0,255,0,.25)")
	// $("#header p").css("background", "rgba(0,0,255,.25)")
	// $("#sidebar h2").css("background", "rgba(255,255,0,.25)")
})