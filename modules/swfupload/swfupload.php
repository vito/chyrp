<?php
    class SWFUpload extends Modules {
        public $insert_swfupload = array();

        public function admin_head() {
            if (substr_count($_SERVER['HTTP_USER_AGENT'], "MSIE"))
                return;

            $config = Config::current();

            if (!empty($this->insert_swfupload)) {
                echo '      <script src="'.$config->chyrp_url.'/modules/swfupload/lib/swfupload.js" type="text/javascript" charset="utf-8"></script>'."\n";
                echo '      <script src="'.$config->chyrp_url.'/modules/swfupload/lib/swfupload.queue.js" type="text/javascript" charset="utf-8"></script>'."\n";
                echo '      <script src="'.$config->chyrp_url.'/modules/swfupload/lib/fileprogress.js" type="text/javascript" charset="utf-8"></script>'."\n";
                echo '      <script src="'.$config->chyrp_url.'/modules/swfupload/lib/handlers.js" type="text/javascript" charset="utf-8"></script>'."\n";
                echo '      <link rel="stylesheet" href="'.$config->chyrp_url.'/modules/swfupload/lib/style.css" type="text/css" media="screen" charset="utf-8" />'."\n";
                echo '      <script type="text/javascript">'."\n";
                echo "          $(function(){\n";
                foreach ($this->insert_swfupload as $id => $options) {
                    $upload_url                   = $config->chyrp_url."/modules/swfupload/upload_handler.php";
                    $flash_url                    = $config->chyrp_url."/modules/swfupload/lib/swfupload.swf";
                    $file_types                   = "*";
                    $file_types_description       = "All Files";
                    $debug                        = false;
                    $file_queued_handler          = "fileQueued";
                    $file_queue_error_handler     = "fileQueueError";
                    $file_dialog_complete_handler = "fileDialogComplete";
                    $file_upload_limit            = 100;
                    $file_queue_limit             = 0;
                    $swfupload_preload_handler    = "preLoad";
                    $swfupload_load_failed_handler= "loadFailed";
                    $upload_start_handler         = "uploadStart";
                    $upload_progress_handler      = "uploadProgress";
                    $upload_error_handler         = "uploadError";
                    $upload_success_handler       = "uploadSuccess";
                    $upload_complete_handler      = "uploadComplete";
                    $queue_complete_handler       = "queueComplete";
                    $use_query_string             = true;

                    if (is_string($options))
                        $file_types = $options;
                    else
                        foreach ($options as $key => $val)
                            $$key = $val;

                    echo '              $("#'.$id.'_field").clone().attr("id", "'.$id.'_fake").addClass("swfupload_button").insertBefore("#'.$id.'_field")'."\n";
                    echo "              ".$id." = new SWFUpload({\n";
                    echo '                  upload_url : "'.$upload_url.'",'."\n";
                    echo '                  flash_url : "'.$flash_url.'",'."\n";
                    echo '                  post_params: {"PHPSESSID" : "'.session_id().'", "PHPSESSNAME" : "'.session_name().'", "ajax" : "true" },'."\n";
                    echo '                  file_size_limit : "100 MB",'."\n";
                    echo '                  file_types : "'.$file_types.'",'."\n";
                    echo '                  file_types_description : "'.$file_types_description.'",'."\n";
                    echo '                  file_queued_handler : '.$file_queued_handler.','."\n";
                    echo '                  file_queue_error_handler : '.$file_queue_error_handler.','."\n";
                    echo '                  file_dialog_complete_handler : '.$file_dialog_complete_handler.','."\n";
                    echo '                  file_upload_limit : '.$file_upload_limit.','."\n";
                    echo '                  file_queue_limit : '.$file_queue_limit.','."\n";
                    echo '                  use_query_string : '.$use_query_string.','."\n";
                    echo '                  custom_settings : { "progressTarget" : "fsUploadProgress",
                                      "cancelButtonId" : "progressCancel" },'."\n";
                    if ($debug) echo '                  debug: true,'."\n";
                    echo '                  '."\n";
                    echo '                  button_placeholder_id : "'.$id.'_field",'."\n";
                    echo '                  button_width : 100,'."\n";
                    echo '                  button_height : 10,'."\n";
                    echo '                  button_action : SWFUpload.BUTTON_ACTION.SELECT_FILES,'."\n";
                    echo '                  swfupload_preload_handler : '.$swfupload_preload_handler.','."\n";
                   #echo '                  swfupload_load_failed_handler : '.$swfupload_load_failed_handler.','."\n";
                    echo '                  upload_start_handler : '.$upload_start_handler.','."\n";
                    echo '                  upload_progress_handler : '.$upload_progress_handler.','."\n";
                    echo '                  upload_error_handler : '.$upload_error_handler.','."\n";
                    echo '                  upload_success_handler : '.$upload_success_handler.','."\n";
                    echo '                  upload_complete_handler : '.$upload_complete_handler.','."\n";
                    echo '                  queue_complete_handler : '.$queue_complete_handler.''."\n";
                    echo '              })'."\n";
                    echo '              $("#SWFUpload_0")'."\n";
                    echo '                  .css({ position: "absolute", top: 60, left: 10 })'."\n";
                    echo '                  .before(\'<div id="fsUploadProgress"><div id="divStatus">0 Files Uploaded</div></div>\')'."\n";
                }
                echo "          })\n";
                echo "      </script>\n";
            }
        }

        public function prepare_swfupload($id, $extensions) {
            $this->insert_swfupload[$id] = $extensions;
        }
    }
