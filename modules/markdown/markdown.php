<?php
    require "lib/markdown.php";

    class Markdown extends Modules {
        public function __init() {
            $this->addAlias("markup_text", "markdownify", 8);
            $this->addAlias("preview", "markdownify", 8);
        }
        static function markdownify($text) {
            return Markdown($text);
        }
        public function admin_head() {
            $config = Config::current();
            if (!$config->enable_wysiwyg)
                return;

            $current_action = isset(Route::current()->action) ? Route::current()->action : "write_post" ;
            $wysiwyg_actions = array("write_post", "edit_post", "write_page", "edit_page");
            if (!in_array($current_action, $wysiwyg_actions))
                return;

            $path = $config->chyrp_url."/includes/lib/markitup/";
            ?>
      <link href="<?php echo $path; ?>skins/simple/style.css" type="text/css" rel="stylesheet">
      <link href="<?php echo $path; ?>sets/markdown/style.css" type="text/css" rel="stylesheet">
      <script src="<?php echo $path; ?>jquery.markitup.js" type="text/javascript" charset="utf-8"></script>
      <script src="<?php echo $path; ?>sets/markdown/set.js" type="text/javascript" charset="utf-8"></script>
      <script type="text/javascript">
        $(document).ready(function(){
            $("#write_form textarea, #edit_form textarea").markItUp(mySettings);
        });
      </script>
      <?php
        }
    }
