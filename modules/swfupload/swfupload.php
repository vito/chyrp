<?php
    class SWFUpload extends Modules {
        public $insert_swfupload = array();

        public function admin_head() {
            if (substr_count($_SERVER['HTTP_USER_AGENT'], "MSIE"))
                return;

            $config = Config::current();

            if (!empty($this->insert_swfupload)) {
                echo '      <script src="'.$config->chyrp_url.'/modules/swfupload/lib/swfupload.js" type="text/javascript" charset="utf-8"></script>'."\n";
                echo '      <script src="'.$config->chyrp_url.'/modules/swfupload/lib/handlers.js" type="text/javascript" charset="utf-8"></script>'."\n";
                echo '      <link rel="stylesheet" href="'.$config->chyrp_url.'/modules/swfupload/style.css" type="text/css" media="screen" title="no title" charset="utf-8" />'."\n";
                echo '      <script type="text/javascript">'."\n";
                echo "          $(function(){\n";
                foreach ($this->insert_swfupload as $id => $options) {
                    $upload_url                   = $config->chyrp_url."/modules/swfupload/upload_handler.php";
                    $flash_url                    = $config->chyrp_url."/modules/swfupload/lib/swfupload_f9.swf";
                    $file_types                   = "*";
                    $file_types_description       = "All Files";
                    $debug                        = false;
                    $file_queue_error_handler     = "fileQueueError";
                    $file_dialog_complete_handler = "fileDialogComplete";
                    $upload_start_handler         = "uploadStart";
                    $upload_progress_handler      = "uploadProgress";
                    $upload_error_handler         = "uploadError";
                    $upload_success_handler       = "uploadSuccess";
                    $upload_complete_handler      = "uploadComplete";

                    if (is_string($options))
                        $file_types = $options;
                    else
                        foreach ($options as $key => $val)
                            $$key = $val;

                    echo '              $("#'.$id.'").replaceWith("<input type=\"button\" value=\"Upload\" class=\"swfupload_button\" id=\"'.$id.'\" />")'."\n";
                    echo "              ".$id." = new SWFUpload({\n";
                    echo '                  upload_url : "'.$upload_url.'",'."\n";
                    echo '                  flash_url : "'.$flash_url.'",'."\n";
                    echo '                  post_params: {"PHPSESSID" : "'.session_id().'", "PHPSESSNAME" : "'.session_name().'", "ajax" : "true" },'."\n";
                    echo '                  file_size_limit : "100 MB",'."\n";
                    echo '                  file_types : "'.$file_types.'",'."\n";
                    echo '                  file_types_description : "'.$file_types_description.'",'."\n";
                    if ($debug)
                        echo '                  debug: true,'."\n";
                    echo '                  '."\n";
                    echo '                  file_queue_error_handler : '.$file_queue_error_handler.','."\n";
                    echo '                  file_dialog_complete_handler : '.$file_dialog_complete_handler.','."\n";
                    echo '                  upload_start_handler : '.$upload_start_handler.','."\n";
                    echo '                  upload_progress_handler : '.$upload_progress_handler.','."\n";
                    echo '                  upload_error_handler : '.$upload_error_handler.','."\n";
                    echo '                  upload_success_handler : '.$upload_success_handler.','."\n";
                    echo '                  upload_complete_handler : '.$upload_complete_handler.''."\n";
                    echo '              })'."\n";

                    echo '              $("#'.$id.'").click(function(){'."\n";
                    echo '                  '.$id.'.selectFiles();'."\n";
                    echo '              }).before(\'<div id="progress"><div class="back"><div class="fill"></div><div class="clear"></div></div></div>\')'."\n";
                }
                echo "          })\n";
                echo "      </script>\n";
            }
        }

        public function prepare_swfupload($id, $extensions) {
            $this->insert_swfupload[$id] = $extensions;
        }
    }
