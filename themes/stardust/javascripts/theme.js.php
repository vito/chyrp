<?php
    define('JAVASCRIPT', true);
    require_once "../../../includes/common.php";
    error_reporting(0);
    header("Content-Type: application/x-javascript");
?>
<!-- --><script>
$(function(){
    $(".notice, .warning, .message").
        append("<span class=\"sub\"><?php echo __("(click to hide)", "theme"); ?></span>").
        click(function(){
            $(this).fadeOut("fast");
        })
        .css("cursor", "pointer");

    if ($.browser.safari)
        $("input#search").attr({
            placeholder: "<?php echo __("Search...", "theme"); ?>"
        });

    if ($("#debug").size())
        $("#wrapper").css("padding-bottom", $("#debug").height());

    $("#debug .toggle").click(function(){
        if (Cookie.get("hide_debug") == "true") {
            Cookie.destroy("hide_debug");
            $("#debug").animate({ height: "33%" });
        } else {
            Cookie.set("hide_debug", "true", 30);
            $("#debug").animate({ height: 15 });
            $("#debug ul li").each(function(){
                $("<span class=\"sub\"> | "+ $(this).html() +"</span>").appendTo("#debug h5:first");
            })
        }
    })

    if (Cookie.get("hide_debug") == "true") {
        $("#debug").height(15);
        $("#debug ul li").each(function(){
            $("<span class=\"sub\"> | "+ $(this).html() +"</span>").appendTo("#debug h5:first");
        })
    }
})
<!-- --></script>
