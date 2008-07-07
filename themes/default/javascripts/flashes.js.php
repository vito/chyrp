<?php
	define('JAVASCRIPT', true);
	require_once "../../../includes/common.php";
	error_reporting(0);
	header("Content-Type: application/x-javascript");
?>
<!-- --><script>
$(function(){
	$(".notice, .success, .failure").
		append("<span class=\"sub\"><?php echo __("(click to hide)", "theme"); ?></span>").
		click(function(){
			$(this).fadeOut("fast")
		})
})
<!-- --></script>