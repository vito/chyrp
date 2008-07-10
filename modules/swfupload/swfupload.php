<?php
	class SWFUpload extends Module {
		public $insert_swfupload = array();

		public function admin_head() {
			$config = Config::current();

			if (!empty($this->insert_swfupload)) {
				echo '		<script src="'.$config->chyrp_url.'/modules/swfupload/lib/FlashDevelop/swfupload.js" type="text/javascript" charset="utf-8"></script>'."\n";
				echo '		<script src="'.$config->chyrp_url.'/modules/swfupload/lib/handlers.js" type="text/javascript" charset="utf-8"></script>'."\n";
				echo '		<link rel="stylesheet" href="'.$config->chyrp_url.'/modules/swfupload/style.css" type="text/css" media="screen" title="no title" charset="utf-8" />'."\n";
				echo '		<script type="text/javascript">'."\n";
				echo "			$(function(){\n";
				foreach ($this->insert_swfupload as $id => $extensions) {
					echo '				$("#'.$id.'").replaceWith("<input type=\"button\" value=\"Upload\" class=\"swfupload_button\" id=\"'.$id.'\" />")'."\n";
					echo "				".$id." = new SWFUpload({\n";
					echo '					upload_url : "'.$config->chyrp_url.'/modules/swfupload/upload_handler.php",'."\n";
					echo '					flash_url : "'.$config->chyrp_url.'/modules/swfupload/lib/FlashDevelop/Flash9/swfupload_f9.swf",'."\n";
					echo '					post_params: {"PHPSESSID" : "'.session_id().'", "PHPSESSNAME" : "'.session_name().'" },'."\n";
					echo '					file_size_limit : "100 MB",'."\n";
					echo '					file_types : "'.$extensions.'",'."\n";
					echo '					file_types_description : "All Files",'."\n";
					#echo '					debug: true,'."\n";
                    echo '					'."\n";
					echo '					// The event handler functions are defined in handlers.js'."\n";
					echo '					file_queue_error_handler : fileQueueError,'."\n";
					echo '					file_dialog_complete_handler : fileDialogComplete,'."\n";
					echo '					upload_start_handler : uploadStart,'."\n";
					echo '					upload_progress_handler : uploadProgress,'."\n";
					echo '					upload_error_handler : uploadError,'."\n";
					echo '					upload_success_handler : uploadSuccess,'."\n";
					echo '					upload_complete_handler : uploadComplete'."\n";
					echo '				})'."\n";

					echo '				$("#'.$id.'").click(function(){'."\n";
					echo '					'.$id.'.selectFiles();'."\n";
					echo '				}).before(\'<div id="progress"><div class="back"><div class="fill"></div><div class="clear"></div></div></div>\')'."\n";
				}
				echo "			})\n";
				echo "		</script>\n";
				echo '		<style type="text/css">'."\n";
				echo '			#progress {'."\n";
				echo '				background: #fff;'."\n";
				echo '				padding: 1px;'."\n";
				echo '				border: 1px solid #ddd;'."\n";
				echo '				display: none;'."\n";
				echo '			}'."\n";
				echo '			#progress .back {'."\n";
				echo '				background: #f0f0f0;'."\n";
				echo '			}'."\n";
				echo '			#progress .fill {'."\n";
				echo '				background-color: #5fb904;'."\n";
				echo '				height: 24px;'."\n";
				echo '				width: 0;'."\n";
				echo '			}'."\n";
				echo '		</style>'."\n";
			}
		}

		public function prepare_swfupload($id, $extensions) {
			$this->insert_swfupload[$id] = $extensions;
		}
	}
