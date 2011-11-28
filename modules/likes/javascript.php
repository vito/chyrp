<?php
    define('JAVASCRIPT', true);
    require_once "../../includes/common.php";
    error_reporting(0);
    header("Content-Type: application/x-javascript");
?>
<!-- --><script>
    $(function() {
        $(".likepost").click(function() {
            var id = $(this).attr("id").replace(/post_id_/, "");
            var dataString = 'post_id='+ id +'&ajax=true';
            var fullUrl = '<?php echo Config::current()->chyrp_url; ?>/?action=like'
            var parent = $(this);

            $(this).fadeOut(100);
            $.ajax({type: "post",
                    dataType: "json",
                    url: fullUrl,
                    data: dataString,
                    cache: false,
                    success: function(html) {
                        parent.html(html);
                        parent.fadeIn(200);
                    } 
            });
            return false;
        });
    });
<?php Trigger::current()->call("likes_javascript"); ?>
<!-- --></script>
