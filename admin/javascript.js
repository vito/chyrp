$(function(){
	$(".help").click(function(){
		window.open($(this).attr("href"), "help", "status=0, height=350, width=300")
		return false;
	})
})