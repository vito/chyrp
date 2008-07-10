<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>SWFUpload Testing</title>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="FlashDevelop/swfupload.js" type="text/javascript" charset="utf-8"></script>
		<script src="handlers.js" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			var swfu;

			$(function(){
				swfu = new SWFUpload({
					upload_url : "http://toogeneric.com/swfupload/upload_test.php",
					flash_url : "http://toogeneric.com/swfupload/FlashDevelop/Flash9/swfupload_f9.swf",
					post_params: {"PHPSESSID" : ""},
					file_size_limit : "100 MB",
					file_types : "*.mp3",
					file_types_description : "All Files",
					debug: true,

					// The event handler functions are defined in handlers.js
					file_queue_error_handler : fileQueueError,
					file_dialog_complete_handler : fileDialogComplete,
					upload_start_handler : uploadStart,
					upload_progress_handler : uploadProgress,
					upload_error_handler : uploadError,
					upload_success_handler : uploadSuccess,
					upload_complete_handler : uploadComplete
				})

				$("input").click(function(){
					swfu.selectFiles();
				})
			})
		</script>
		<link rel="stylesheet" href="style.css" />
	</head>
	<body>
		<form id="form1" action="index.php" method="post" enctype="multipart/form-data">
				<div id="progress">
					<div class="back">
						<div class="fill"></div>
						<div class="clear"></div>
					</div>
				</div>
				<div>
					<input type="button" value="Upload" />
				</div>

		</form>
	</body>
</html>