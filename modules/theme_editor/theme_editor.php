<?php
    require_once("lib/file_tree.php");

    class ThemeEditor extends Modules {

        public function extend_nav($navs) {
            if (Visitor::current()->group->can("toggle_extensions"))
                $navs["theme_editor"] = array("title" => __("Theme Editor", "theme_editor"));

            return $navs;
        }

        public function admin_head() {
            $config = Config::current();
            if (Route::current()->action != "theme_editor")
                return;
?>
        <link rel="stylesheet" href="<?php echo $config->chyrp_url; ?>/modules/theme_editor/lib/style.css" type="text/css" media="screen" />
        <script src="<?php echo $config->chyrp_url; ?>/modules/theme_editor/lib/file_tree.js" type="text/javascript" charset="utf-8"></script>
<?php
        }

        public function admin_theme_editor($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));
            if (empty($_POST))
                return $admin->display("theme_editor", array("editor" => self::admin_context($admin->context)), __("Theme Editor", "theme_editor"));

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            if (isset($_POST['file']) and isset($_POST['newcontent'])) {
                $done = file_put_contents($_POST['file'], $_POST['newcontent']);
                if (!empty($done))
                    Flash::notice(__("File Updated"), "/admin/?action=theme_editor&file=".$_POST['cur_file']);
            }
        }

        static function admin_context($context) {
            $theme = Config::current()->theme;
            $theme_dir = THEME_DIR."/";

            $file = ltrim(isset($_GET['file']) ? $_GET['file'] : "info.yaml", "/");
            $cur_file = $theme_dir.$file;

            $ext = array("css", "js", "php", "pot", "twig", "yaml");
            $context["editor"]["list_all"] = php_file_tree($theme_dir, "?action=theme_editor&file=[link]", $ext);
            if (isset($cur_file) and is_file($cur_file)) {
                $context["editor"]["file_name"] = $file;
                $context["editor"]["file_path"] = $cur_file;
                $context["editor"]["file_content"] = file_get_contents($cur_file);
            }

            return $context;
        }
    }

