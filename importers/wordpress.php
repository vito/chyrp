<?php
	require_once "../includes/common.php";

	if (!in_array("text", $config->enabled_feathers))
		error(__("Missing Feather"), __("Importing from WordPress requires the Text feather to be installed and enabled."));

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

	$errors = array();
	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				$xml = file_get_contents($_FILES['xml_file']['tmp_name']);
				$xml = preg_replace("/<wp:comment_content><!\[CDATA\[(.*?)\]\]><\/wp:comment_content>/s", "<wp:comment_content>\\1</wp:comment_content>", $xml);
				$xml = preg_replace("/<wp:comment_content>(.*?)<\/wp:comment_content>/s", "<wp:comment_content><![CDATA[\\1]]></wp:comment_content>", $xml);
				$xml = preg_replace("/<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s", "<wp:meta_value>\\1</wp:meta_value>", $xml);
				$xml = preg_replace("/<wp:meta_value>(.*?)<\/wp:meta_value>/s", "<wp:meta_value><![CDATA[\\1]]></wp:meta_value>", $xml);

				if (!strpos($xml, "xmlns:wp=\"http://wordpress.org/export/")) {
					$errors[] = __("File does not seem to be a valid WordPress exported file.");
				} else {
					$parser = new XMLParser($xml);
					$parser->parse();

					$items = $parser->document["RSS"][0]["CHANNEL"][0]["ITEM"];
					foreach ($items as $the_post) {
						if ($the_post["TITLE"][0]["data"] == "zz_placeholder") continue;
						if ($the_post["WP:POST_TYPE"][0]["data"] == "post") {
							$status_translate = array('publish' => "public", 'draft' => "draft", 'private' => "private", 'static' => "public", 'object' => "public", 'attachment' => "public", 'inherit' => "public", 'future' => "draft", 'pending' => "draft");

							$values = array("title" => $the_post["TITLE"][0]["data"], "body" => $the_post["CONTENT:ENCODED"][0]["data"]);
							$status = str_replace(array_keys($status_translate), array_values($status_translate), $the_post["WP:STATUS"][0]["data"]);
							$clean = (isset($the_post["WP:POST_NAME"][0]["data"])) ? $the_post["WP:POST_NAME"][0]["data"] : sanitize($the_post["TITLE"][0]["data"]) ;
							$url = Post::check_url($clean);
							$timestamp = ($the_post["WP:POST_DATE"][0]["data"] == "0000-00-00 00:00:00") ? datetime() : $the_post["WP:POST_DATE"][0]["data"] ;

							# Damn it feels good to be a gangsta...
							$_POST['status'] = $status;
							$_POST['pinned'] = false;
							$_POST['created_at'] = $timestamp;
							$id = Post::add($values, $clean, $url);

							$trigger->call("import_wordpress_post", array($the_post, $id));
						} elseif ($the_post["WP:POST_TYPE"][0]["data"] == "page") {
							$clean = (isset($the_post["WP:POST_NAME"][0]["data"])) ? $the_post["WP:POST_NAME"][0]["data"] : sanitize($the_post["TITLE"][0]["data"]) ;
							$url = $page->check_url($clean);

							$id = $page->add($the_post["TITLE"][0]["data"], $the_post["CONTENT:ENCODED"][0]["data"], 0, true, $clean, $url);

							$trigger->call("import_wordpress_page", array($the_post, $id));
						}
					}

					$step = "2";
				}
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
		<title><?php echo __("Chyrp: Import: WordPress"); ?></title>
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
			<h1><?php echo __("Import WordPress"); ?></h1>
			<p><?php echo __("Please upload your WordPress eXtended RSS .xml file."); ?></p>
			<form action="wordpress.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<label for="xml_file"><?php echo __("eXtended .XML File"); ?></label>
				<input type="file" name="xml_file" value="" id="xml_file" />
				<br />
				<br />
				<input type="hidden" name="step" value="1" id="step" />
				<input type="hidden" name="feather" value="text" id="feather" />
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
