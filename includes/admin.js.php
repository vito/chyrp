<?php
    define('JAVASCRIPT', true);
    require_once "common.php";
    error_reporting(0);
    header("Content-Type: application/x-javascript");
    $route = Route::current(MainController::current());
?>
<!-- --><script>
var Route = {
    action: "<?php echo fix($_GET['action']); ?>"
}

var site_url = "<?php echo $config->chyrp_url; ?>"

$(function(){
    // Scan AJAX responses for errors.
    $(document).ajaxComplete(function(event, request){
        var response = request ? request.responseText : null
        if (isError(response))
            alert(response.replace(/(HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW|<([^>]+)>\n?)/gm, ""))
    })<?php echo "\n\n\n\n\n"; # Balance out the line numbers in this script and in the output to help debugging. ?>

    // Handle typing "\ct" to insert a <tab>
    $("textarea").keyup(function(event){
        if ($(this).val().match(/([^\\]|^)\\ct/gm))
            $(this).val($(this).val().replace(/([^\\]|^)\\ct/gm, "  "))
    })

    $(".flexnav").flexNav({ 'animationSpeed' : 'fast' });
    
    <?php if (Config::current()->enable_wysiwyg) : ?>
    // Use RedactorJS for <textarea> elements.
    var fullStack = ["#body_field", "#body"]
    $.each(fullStack, function(index, element) {
        $(element).redactor({
            toolbarFixedBox: true,
            minHeight: 140,
            focus: true,
            imageUpload: "../includes/uploader.php",
            imageGetJson: "../includes/uploaded.php"
        })
    })

    var miniStack = ["#quote_field", "#description_field", "#caption_field", "#dialogue_field"]
    $.each(miniStack, function(index, element) {
        $(element).redactor({
            toolbarFixedBox: true,
            minHeight: 140,
            focus: true,
            buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|',
                      'unorderedlist', 'orderedlist', 'link']
        })
    })
    <?php endif; ?>

    // "Help" links should open in popup windows.
    $(".help").live("click", function(){
        window.open($(this).attr("href"), "help", "status=0, scrollbars=1, location=0, menubar=0, "+
                                                  "toolbar=0, resizable=1, height=450, width=400")
        return false
    })

    // SVG fallback for browsers that do not support SVG images
    $("img").fixsvg()

    // Auto-expand input fields
    $(".expand").expand()

    // Checkbox toggling.
    togglers()

    // Core admin behaviour
    Admin.init()

    if (Route.action == "delete_group")
        $("form.confirm").submit(function(){
            if (!confirm("<?php echo __("You are a member of this group. Are you sure you want to delete it?"); ?>"))
                return false
        })

    // Remove things that only exist for JS-disabled users.
    $(".js_disabled").remove()
    $(".js_enabled").css("display", "block")
})

function togglers() {
    var all_checked = true

    $(document.createElement("label")).attr("for", "toggle").text("<?php echo __("Toggle All"); ?>").appendTo("#toggler")
    $(document.createElement("input")).attr({
        type: "checkbox",
        name: "toggle",
        id: "toggle",
        "class": "checkbox"
    }).appendTo("#toggler, .toggler")

    $("#toggle").click(function(){
        $("form#new_group, form#group_edit, table").find(":checkbox").not("#toggle").each(function(){
            this.checked = document.getElementById("toggle").checked
        })

        $(this).parent().parent().find(":checkbox").not("#toggle").each(function(){
            this.checked = document.getElementById("toggle").checked
        })
    })

    // Some checkboxes are already checked when the page is loaded
    $("form#new_group, form#group_edit, table").find(":checkbox").not("#toggle").each(function(){
        if (!all_checked) return
        all_checked = this.checked
    })

    $(":checkbox:not(#toggle)").click(function(){
        var action_all_checked = true

        $("form#new_group, form#group_edit, table").find(":checkbox").not("#toggle").each(function(){
            if (!action_all_checked) return
            action_all_checked = this.checked
        })

        $("#toggle").parent().parent().find(":checkbox").not("#toggle").each(function(){
            if (!action_all_checked) return
            action_all_checked = this.checked
        })

        document.getElementById("toggle").checked = action_all_checked
    })

    if ($("#toggler").size())
        document.getElementById("toggle").checked = all_checked

    $("td:has(:checkbox)").click(function(e){
        $(this).find(":checkbox").each(function(){
            if (e.target != this)
                this.checked = !(this.checked)
        })
    })
}

var Admin = {
    init: function(){
        if (/(write)_/.test(Route.action))
            this.bookmarklet_link()

        this.prepare_previewer()
        this.more_options()
        this.watch_slug()

        if (Route.action == "edit_group")
            this.confirm_group()
    },
    bookmarklet_link: function(){
        // Add the list item
        $(document.createElement("li")).addClass("bookmarklet right").text("Bookmarklet: ").prependTo(".write_post_nav")

        // Add the link
        $(document.createElement("a"))
            .text("<?php echo __("Chyrp!"); ?>")
            .addClass("no_drag")
            .attr("href", "javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,"+
                          "s=(e?e():(k)?k():(x?x.createRange().text:0)),f=\'"+site_url+"/admin/?action=bookmarklet\',"+
                          "l=d.location,e=encodeURIComponent,p=\'&url=\'+e(l.href)+\'&title=\'+e(d.title)+\'&selection=\'+"+
                          "e(s),u=f+p;a=function(){if(!w.open(u,\'t\',\'toolbar=0,resizable=1,status=1,width=450,"+
                          "height=430\'))l.href=u;};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();void(0)")
            .appendTo(".bookmarklet")
    },
    prepare_previewer: function() {
        if (!$(".preview_me").size())
            return

        var feather = ($("#feather").size()) ? $("#feather").val() : ""

        $(".preview_me").each(function(){
            var id = $(this).attr("id")
            $(document.createElement("div"))
                .css("display", "none")
                .attr("id", "preview_"+ id)
                .insertBefore("#write_form, #edit_form")
        })

        $(document.createElement("button"))
            .append("<?php echo __("Preview &rarr;"); ?>").attr("accesskey", "p")
            .click(function(){
                $(".preview_me").each(function(){
                    var id = $(this).attr("id")
                    $("#preview_"+ id).load("<?php echo $config->chyrp_url; ?>/includes/ajax.php", {
                        action: "preview",
                        content: $("#"+ id).val(),
                        feather: feather,
                        field: id
                    }, function(){
                        $(this).fadeIn("fast")
                    })
                })
                return false
            })
            .appendTo(".buttons")
    },
    more_options: function(){
        if ($("#more_options").size()) {
            if (Cookie.get("show_more_options") == "true")
                var more_options_text = "<?php echo __("&uarr; Fewer Options"); ?>";
            else
                var more_options_text = "<?php echo __("More Options &darr;"); ?>";

            $(document.createElement("a")).attr({
                id: "more_options_link",
                href: "javascript:void(0)"
            }).addClass("more_options_link").append(more_options_text).insertBefore(".buttons")
            $("#more_options").clone().insertAfter("#more_options_link").removeClass("js_disabled")

            $("#more_options").wrap("<div></div>")

            if (Cookie.get("show_more_options") == null)
                $("#more_options").parent().css("display", "none")

            $("#more_options_link").click(function(){
                if ($("#more_options").parent().css("display") == "none") {
                    $(this).empty().append("<?php echo __("&uarr; Fewer Options"); ?>")
                    Cookie.set("show_more_options", "true", 30)
                } else {
                    $(this).empty().append("<?php echo __("More Options &darr;"); ?>")
                    Cookie.destroy("show_more_options")
                }
                $("#more_options").parent().slideToggle()
            })
        }
    },
    watch_slug: function(){
        $("input#slug").keyup(function(e){
            if (/^([a-zA-Z0-9\-\._:]*)$/.test($(this).val()))
                $(this).css("background", "")
            else
                $(this).css("background", "#ffdddd")
        })
    },
    confirm_group: function(msg){
        $("form.confirm").submit(function(){
            if (!confirm("<?php echo __("You are a member of this group. Are you sure the permissions are as you want them?"); ?>"))
                return false
        })
    }
}

<?php $trigger->call("admin_javascript"); ?>
<!-- --></script>
