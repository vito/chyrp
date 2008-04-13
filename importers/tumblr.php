<?php
	require_once "../includes/common.php";
	
	if (!in_array("text", $config->enabled_feathers) or !in_array("video", $config->enabled_feathers) or !in_array("audio", $config->enabled_feathers) or !in_array("chat", $config->enabled_feathers) or !in_array("photo", $config->enabled_feathers) or !in_array("quote", $config->enabled_feathers) or !in_array("link", $config->enabled_feathers))
		error(__("Missing Feather"), __("Importing from Tumblr requires the Text, Video, Audio, Chat, Photo, Quote, and Link feathers to be installed and enabled."));
	
	if (!$user->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));
	
	class XMLParser { # Via http://php.net/xml, slightly modified.
		var $parser;
		var $filePath;
		var $document;
		var $currTag;
		var $tagStack;
	
		function XMLParser($xml) {
			$this->parser = xml_parser_create();
			$this->xml = $xml;
			$this->document = array();
			$this->currTag =& $this->document;
			$this->tagStack = array();
		}
	
		function parse() {
			xml_set_object($this->parser, $this);
			xml_set_character_data_handler($this->parser, 'dataHandler');
			xml_set_element_handler($this->parser, 'startHandler', 'endHandler');
	  
			xml_parse($this->parser, $this->xml);
    
			xml_parser_free($this->parser);
	  
			return true;
		}
	
		function startHandler($parser, $name, $attribs) {
			if(!isset($this->currTag[$name]))
				$this->currTag[$name] = array();
		
			$newTag = array();
			if(!empty($attribs))
				$newTag['attr'] = $attribs;
			array_push($this->currTag[$name], $newTag);
		
			$t =& $this->currTag[$name];
			$this->currTag =& $t[count($t)-1];
			array_push($this->tagStack, $name);
		}
	
		function dataHandler($parser, $data) {
			$data = trim($data);
			$data = (empty($data)) ? "\n" : $data ;
		
			if(!empty($data)) {
				if(isset($this->currTag['data']))
					$this->currTag['data'] .= $data;
				else
					$this->currTag['data'] = $data;
			}
		}
	
		function endHandler($parser, $name) {
			$this->currTag =& $this->document;
			array_pop($this->tagStack);
		
			for($i = 0; $i < count($this->tagStack); $i++) {
				$t =& $this->currTag[$this->tagStack[$i]];
				$this->currTag =& $t[count($t)-1];
			}
		}
	}
	
	function fallback($index, $fallback = "") {
		echo (isset($_POST[$index])) ? $_POST[$index] : $fallback ;
	}
	function read_url($url) {
		extract(parse_url($url), EXTR_SKIP);
		if (ini_get('allow_url_fopen')) {
			$connect = @fopen($url, "r");
			if (!$connect) return false;

			$content = '';
			while($remote_read = fread($connect, 4096))
				$content .= $remote_read;
			fclose($connect);
		} elseif (function_exists("curl_init")) {
			$handle = curl_init();
			curl_setopt ($handle, CURLOPT_URL, $url);
			curl_setopt ($handle, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt ($handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($handle, CURLOPT_TIMEOUT, 60);
			$content = curl_exec($handle);
			curl_close($handle);
		} else {
			$path = (!isset($path)) ? '/' : $path ;
			if (isset($query)) $path.= '?'.$query;
			$port = (isset($port)) ? $port : 80 ;
	
			$connect = @fsockopen($host, $port, $errno, $errstr, 2);
			if (!$connect) return false;

			# Send the GET headers
			fwrite($connect, "GET ".$path."?num=50 HTTP/1.1\r\n");
			fwrite($connect, "Host: ".$host."\r\n");
			fwrite($connect, "User-Agent: Chyrp/".CHYRP_VERSION."\r\n\r\n");

			$content = "";
			while (!feof($connect)) {
				$line = fgets($connect, 4096);
				$content.= trim($line)."\n";
			}

			fclose($connect);
		}
		return $content;
	}
	if (!function_exists("htmlspecialchars_decode")) {
		function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
			return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
		}
	}
	function fix_html_tags($content) {
		# This part's weird. Tumblr seems to remove the spacing around HTML tags.
		return htmlspecialchars_decode($content);
	}
	function cdatize($xml) {
		$replace = array("regular-title", "regular-body", "video-caption", "conversation-title", "conversation-text", "photo-caption", "quote-text", "quote-source", "link-text", "link-description");
		return preg_replace("/<(".join("|", $replace).")>(.*?)<\/(".join("|", $replace).")>/s", "<\\1><![CDATA[\\2]]></\\3>", $xml);
	}
	function reverse($a, $b) {
		if (empty($a) or empty($b)) return 0;
		return ($a["attr"]["ID"] < $b["attr"]["ID"]) ? -1 : 1 ;
	}
	
	$errors = array();
	
	if (!file_exists(MAIN_DIR."/upload"))
		$errors[] = __("Please create the <code>/upload</code> directory at your Chyrp install's root and CHMOD it to 777.");
	elseif (!is_writable(MAIN_DIR."/upload"))
		$errors[] = __("Please CHMOD <code>/upload</code> to 777.");
	
	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				$url = $_POST['url']."/api/read?num=50";
				$xml = read_url($url);
				$xml = cdatize($xml);
								
				$parser = new XMLParser($xml);
				$parser->parse();
				
				$parsed = $parser->document["TUMBLR"][0]["POSTS"][0];
				
				usort($parsed["POST"], "reverse");
				
				$already_in = array();
				foreach ($parsed["POST"] as $the_post)
					$already_in[] = $the_post["attr"]["ID"];
				
				if ($parsed["attr"]["TOTAL"] <= count($parsed["POST"])) {
					$parsed = $parsed;
				} else {
					while ($parsed["attr"]["TOTAL"] > count($parsed["POST"])) {
						$xml = read_url($url."&start=".count($parsed["POST"]));
						$xml = cdatize($xml);
						$parser = new XMLParser($xml);
						$parser->parse();
						$parsed_temp = $parser->document["TUMBLR"][0]["POSTS"][0];
						foreach ($parsed_temp["POST"] as $the_post) {
							if (!in_array($the_post["attr"]["ID"], $already_in)) {
								$parsed["POST"][] = $the_post;
								$already_in[] = $the_post["attr"]["ID"];
							}
						}
					}
				}
				
				foreach ($parsed["POST"] as $the_post) {
					if ($the_post["attr"]["TYPE"] == "audio")
						continue; # Can't import them since Tumblr has them locked in.
					
					$translate_types = array("regular" => "text", "conversation" => "chat");
					
					switch($the_post["attr"]["TYPE"]) {
						case "regular":
							$title = (isset($the_post["REGULAR-TITLE"])) ? fix_html_tags($the_post["REGULAR-TITLE"][0]["data"]) : "" ;
							$body = fix_html_tags($the_post["REGULAR-BODY"][0]["data"]);
							
							$values = array("title" => $title, "body" => $body);
							$clean = sanitize($title);
							break;
						case "video":
							$caption = (isset($the_post["VIDEO-CAPTION"])) ? fix_html_tags($the_post["VIDEO-CAPTION"][0]["data"]) : "" ;
							
							$values = array("embed" => $the_post["VIDEO-PLAYER"][0]["data"], "caption" => $caption);
							$clean = "";
							break;
						case "conversation":
							$title = (isset($the_post["CONVERSATION-TITLE"])) ? fix_html_tags($the_post["CONVERSATION-TITLE"][0]["data"]) : "" ;
							$dialogue = trim(fix_html_tags($the_post["CONVERSATION-TEXT"][0]["data"]));
							
							$values = array("title" => $title, "dialogue" => $dialogue);
							$clean = sanitize($title);
							break;
						case "photo":
							$image = read_url($the_post["PHOTO-URL"][0]["data"]);
							$info = pathinfo($the_post["PHOTO-URL"][0]["data"]);
							$filename = $info['basename'];
							
							$open = fopen(MAIN_DIR."/upload/".$filename, "w");
							fwrite($open, $image);
							fclose($open);
							
							$caption = (isset($the_post["PHOTO-CAPTION"])) ? fix_html_tags($the_post["PHOTO-CAPTION"][0]["data"]) : "" ;
							
							$values = array("filename" => $filename, "caption" => $caption);
							$clean = "";
							break;
						case "quote":
							$quote = fix_html_tags($the_post["QUOTE-TEXT"][0]["data"]);
							$source = (isset($the_post["QUOTE-SOURCE"])) ? fix_html_tags($the_post["QUOTE-SOURCE"][0]["data"]) : "" ;
							
							$values = array("quote" => $quote, "source" => $source);
							$clean = "";
							break;
						case "link":
							$name = (isset($the_post["LINK-TEXT"])) ? fix_html_tags($the_post["LINK-TEXT"][0]["data"]) : "" ;
							$description = (isset($the_post["LINK-DESCRIPTION"])) ? fix_html_tags($the_post["LINK-DESCRIPTION"][0]["data"]) : "" ;
							
							$values = array("name" => $name, "source" => $the_post["LINK-URL"][0]["data"], "description" => $description);
							$clean = "";
							break;
					}
					$url = Post::check_url($clean);
					$timestamp = when("Y-m-d H:i:s", $the_post["attr"]["DATE"]);
					
					# Damn it feels good to be a gangsta...
					$_POST['status'] = "public";
					$_POST['pinned'] = false;
					$_POST['created_at'] = $timestamp;
					$_POST['feather'] = str_replace(array_keys($translate_types), array_values($translate_types), $the_post["attr"]["TYPE"]);
					
					$id = Post::add($values, $clean, $url);
					$trigger->call("import_tumble", array($the_post, $id));
				}
				
				$step = "2";
				break;
		}
	}
	
	$step = (isset($step)) ? $step : "1" ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo __("Chyrp: Import: Tumblr"); ?></title>
		<style type="text/css" media="screen">
			body {
				font: .8em/1.5em normal "Lucida Grande", "Trebuchet MS", Verdana, Helvetica, Arial, sans-serif;
				color: #333;
				background: #eee;
				margin: 0;
				padding: 0;
			}
			.window {
				width: 250px;
				margin: 25px auto;
				padding: 1em;
				border: 1px solid #ddd;
				background: #fff;
			}
			h1 {
				font-size: 1.75em;
				margin-top: 0;
				color: #aaa;
				font-weight: bold;
			}
			label {
				display: block;
				font-weight: bold;
				border-bottom: 1px dotted #ddd;
				margin-bottom: 2px;
			}
			input[type="password"], input[type="text"], textarea {
				margin-bottom: 1em;
				font-size: 1.25em;
				width: 242px;
				padding: 3px;
				border: 1px solid #ddd;
			}
			select {
				margin-bottom: 1em;
			}
			.sub {
				font-size: .8em;
				color: #777;
				font-weight: normal;
			}
			.center {
				text-align: center;
			}
			.error {
				margin: 0 0 1em 0;
				padding: 6px 8px 5px 30px;
				cursor: pointer;
				border-bottom: 1px solid #FBC2C4;
				color: #D12F19;
				background: #FBE3E4 url('../admin/icons/failure.png') no-repeat 7px center;
			}
			.done {
				font-size: 1.25em;
				font-weight: bold;
				text-decoration: none;
				color: #555;
			}
		</style>
	</head>
	<body>
<?php foreach ($errors as $error): ?>
		<div class="error"><?php echo $error; ?></div>
<?php endforeach; ?>
		<div class="window">
<?php
	switch($step):
		case "1":
?>
			<h1><?php echo __("Import Tumblr"); ?></h1>
			<form action="tumblr.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<p>Audio tumbles cannot be imported.</p>
				<label for="xml_file"><?php echo __("Tumblr URL"); ?><span class="sub"> (no trailing slash)</span></label>
				<input type="text" name="url" value="" id="url" />
				<br />
				<input type="hidden" name="step" value="1" id="step" />
				<p class="center"><input type="submit" value="<?php echo __("Import &rarr;"); ?>" /></p>
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
<?php
			break;
		case "2":
?>
			<h1><?php echo __("All done!"); ?></h1>
			<p><?php echo __("All entries have been successfully imported."); ?></p>
			<a href="<?php echo $config->url; ?>" class="done"><?php echo __("View Site &raquo;"); ?></a>
<?php
			break;
	endswitch;
?>
		</div>
	</body>
</html>
