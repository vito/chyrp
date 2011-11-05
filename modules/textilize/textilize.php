<?php
    require "lib/textile.php";

    class Textilize extends Modules {
        public function __init() {
            $this->textile = new Textile();
            $this->addAlias("markup_text", "textile", 8);
            $this->addAlias("preview", "textile", 8);
        }
        public function textile($text) {
            return $this->textile->TextileThis($text);
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
      <link href="<?php echo $path; ?>sets/textile/style.css" type="text/css" rel="stylesheet">
      <script src="<?php echo $path; ?>jquery.markitup.js" type="text/javascript" charset="utf-8"></script>
      <script src="<?php echo $path; ?>sets/textile/set.js" type="text/javascript" charset="utf-8"></script>
      <script type="text/javascript">
        $(document).ready(function(){
            $("#write_form textarea, #edit_form textarea").markItUp(mySettings);
        });
      </script>
      <?php
        }
    }
