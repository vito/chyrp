<?php
    class Audio extends Feathers implements Feather {
        public function __init() {
            $this->setField(array("attr" => "title",
                                  "type" => "text",
                                  "label" => __("Title", "text"),
                                  "optional" => true,
                                  "bookmarklet" => "title"));
            $this->setField(array("attr" => "audio",
                                  "type" => "file",
                                  "label" => __("Audio File", "audio"),
                                  "note" => "<small>(Max. file size: ".ini_get('upload_max_filesize').")</small>"));
            if (isset($_GET['action']) and $_GET['action'] == "bookmarklet")
                $this->setField(array("attr" => "from_url",
                                      "type" => "text",
                                      "label" => __("From URL?", "audio"),
                                      "optional" => true,
                                      "no_value" => true));
            $this->setField(array("attr" => "description",
                                  "type" => "text_block",
                                  "label" => __("Description", "audio"),
                                  "optional" => true,
                                  "preview" => true,
                                  "bookmarklet" => "selection"));

            $this->setFilter("title", array("markup_title", "markup_post_title"));
            $this->setFilter("description", array("markup_text", "markup_post_text"));

            $this->respondTo("delete_post", "delete_file");
            $this->respondTo("feed_item", "enclose_audio");
            $this->respondTo("filter_post", "filter_post");
            $this->respondTo("admin_write_post", "swfupload");
            $this->respondTo("admin_edit_post", "swfupload");
            $this->respondTo("post_options", "add_option");
        }

        public function swfupload($admin, $post = null) {
            if (isset($post) and $post->feather != "audio" or
                isset($_GET['feather']) and $_GET['feather'] != "audio")
                return;

            Trigger::current()->call("prepare_swfupload", "audio", "*.mp3;*.m4a;*.mp4;*.oga;*.ogg;*.webm");
        }

        public function submit() {
            if (!isset($_POST['filename'])) {
                if (isset($_FILES['audio']) and $_FILES['audio']['error'] == 0)
                    $filename = upload($_FILES['audio'], array("mp3", "m4a", "mp4", "oga", "ogg", "webm"));
                elseif (!empty($_POST['from_url']))
                    $filename = upload_from_url($_POST['from_url'], array("mp3", "m4a", "mp4", "oga", "ogg", "webm"));
                else
                    error(__("Error"), __("Couldn't upload audio file."));
            } else
                $filename = $_POST['filename'];

            return Post::add(array("title" => $_POST['title'],
                                   "filename" => $filename,
                                   "description" => $_POST['description']),
                             $_POST['slug'],
                             Post::check_url($_POST['slug']));
        }

        public function update($post) {
            if (!isset($_POST['filename']))
                if (isset($_FILES['audio']) and $_FILES['audio']['error'] == 0) {
                    $this->delete_file($post);
                    $filename = upload($_FILES['audio'], array("mp3", "m4a", "mp4", "oga", "ogg", "webm"));
                } elseif (!empty($_POST['from_url'])) {
                    $this->delete_file($post);
                    $filename = upload_from_url($_POST['from_url'], array("mp3", "m4a", "mp4", "oga", "ogg", "webm"));
                } else
                    $filename = $post->filename;
            else {
                $this->delete_file($post);
                $filename = $_POST['filename'];
            }

            $post->update(array("title" => $_POST['title'],
                                "filename" => $filename,
                                "description" => $_POST['description']));
        }

        public function title($post) {
            return oneof($post->title, $post->title_from_excerpt());
        }

        public function excerpt($post) {
            return $post->description;
        }

        public function feed_content($post) {
            return $post->description;
        }

        public function delete_file($post) {
            if ($post->feather != "audio") return;
            unlink(MAIN_DIR.Config::current()->uploads_path.$post->filename);
        }

        public function filter_post($post) {
            if ($post->feather != "audio") return;
            $post->audio_player = $this->audio_player($post->filename, array(), $post);
        }

        public function audio_type($filename) {
            $file_split = explode(".", $filename);
            $file_ext = strtolower(end($file_split));
            switch($file_ext) {
                case "mp3":
                    return "audio/mpeg";
                case "m4a":
                    return "audio/mp4";
                case "mp4":
                    return "audio/mp4";
                case "oga":
                    return "audio/ogg";
                case "ogg":
                    return "audio/ogg";
                case "webm":
                    return "audio/webm";
                default:
                    return "application/octet-stream";
            }
        }
        
        public function audio_ext($filename) {
            $file_split = explode(".", $filename);
            $audio_type = strtolower(end($file_split));
            switch($audio_type) {
                case "mp3":
                    return "mp3";
                case "m4a":
                    return "m4a";
                case "mp4":
                    return "mp4";
                case "oga":
                    return "oga";
                case "ogg":
                    return "ogg";
                case "webm":
                    return "webm";
                default:
                    return "application/octet-stream";
            }
        }

        public function enclose_audio($post) {
            $config = Config::current();
            if ($post->feather != "audio" or !file_exists(uploaded($post->filename, false)))
                return;

            $length = filesize(uploaded($post->filename, false));

            echo '          <link rel="enclosure" href="'.uploaded($post->filename).'" type="'.$this->audio_type($post->filename).'" title="'.truncate(strip_tags($post->description)).'" length="'.$length.'" />'."\n";
        }

        public function audio_player($filename, $params = array(), $post) {
            $vars = "";
            foreach ($params as $name => $val)
                $vars.= "&amp;".$name."=".$val;

            $config = Config::current();

            $player = "\n\t".'<div id="jquery_jplayer_'.$post->id.'" class="jp-jplayer"></div>';
            $player.= "\n\t".'<div id="jp_container_'.$post->id.'" class="jp-audio">';
            $player.= "\n\t\t".'<div class="jp-type-single">';
            $player.= "\n\t\t\t".'<div class="jp-gui jp-interface">';
            $player.= "\n\t\t\t\t".'<ul class="jp-controls">';
            $player.= "\n\t\t\t\t\t".'<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>';
            $player.= "\n\t\t\t\t\t".'<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>';
            $player.= "\n\t\t\t\t\t".'<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>';
            $player.= "\n\t\t\t\t\t".'<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>';
            $player.= "\n\t\t\t\t\t".'<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>';
            $player.= "\n\t\t\t\t\t".'<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>';
            $player.= "\n\t\t\t\t".'</ul>';
            $player.= "\n\t\t\t\t".'<div class="jp-progress">';
            $player.= "\n\t\t\t\t\t".'<div class="jp-seek-bar">';
            $player.= "\n\t\t\t\t\t\t".'<div class="jp-play-bar"></div>';
            $player.= "\n\t\t\t\t\t".'</div>';
            $player.= "\n\t\t\t\t".'</div>';
            $player.= "\n\t\t\t\t".'<div class="jp-volume-bar">';
            $player.= "\n\t\t\t\t\t".'<div class="jp-volume-bar-value"></div>';
            $player.= "\n\t\t\t\t".'</div>';
            $player.= "\n\t\t\t\t".'<div class="jp-time-holder">';
            $player.= "\n\t\t\t\t\t".'<div class="jp-current-time"></div>';
            $player.= "\n\t\t\t\t\t".'<div class="jp-duration"></div>';
            $player.= "\n\t\t\t\t\t".'<ul class="jp-toggles">';
            $player.= "\n\t\t\t\t\t\t".'<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>';
            $player.= "\n\t\t\t\t\t\t".'<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>';
            $player.= "\n\t\t\t\t\t".'</ul>';
            $player.= "\n\t\t\t\t".'</div>';
            $player.= "\n\t\t\t".'</div>';
            $player.= "\n\t\t\t".'<div class="jp-no-solution">';
            $player.= "\n\t\t\t\t".'<span>Update Required</span>';
            $player.= "\n\t\t\t\t".'To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.';
            $player.= "\n\t\t\t".'</div>';
            $player.= "\n\t\t".'</div>';
            $player.= "\n\t".'</div>';

            $player.= "\n\t".'<link href="'.$config->chyrp_url.'/feathers/audio/skin/blue.monday.hd/jplayer.blue.monday.hd.css" rel="stylesheet" type="text/css" />';
            $player.= "\n\t".'<script src="'.$config->chyrp_url.'/feathers/audio/jplayer/jquery.jplayer.js" type="text/javascript"></script>';
            $player.= "\n\t".'<script>';
            $player.= "\n\t".'$(document).ready(function(){';
            $player.= "\n\t\t".'$("#jquery_jplayer_'.$post->id.'").jPlayer({';
            $player.= "\n\t\t\t".'ready: function() {';
            $player.= "\n\t\t\t\t".'$(this).jPlayer("setMedia", {';
            $player.= "\n\t\t\t\t\t".$this->audio_ext($post->filename).': "'.$config->chyrp_url.$config->uploads_path.$filename.'"';
            $player.= "\n\t\t\t\t".'});';
            $player.= "\n\t\t\t".'},';
            $player.= "\n\t\t\t".'play: function() {';
            $player.= "\n\t\t\t\t".'$(this).jPlayer("pauseOthers");';
            $player.= "\n\t\t\t".'},';
            $player.= "\n\t\t\t".'swfPath: "'.$config->chyrp_url.'/feathers/audio/jplayer/",';
            $player.= "\n\t\t\t".'supplied: "'.$this->audio_ext($post->filename).'",';
            $player.= "\n\t\t\t".'wmode:"window",';
            $player.= "\n\t\t\t".'solution: "html,flash",';
            $player.= "\n\t\t\t".'cssSelectorAncestor: "#jp_container_'.$post->id.'",';
            $player.= "\n\t\t\t".'preload: "auto"';
            $player.= "\n\t\t".'});';
            $player.= "\n\t".'});';
            $player.= "\n\t".'</script>'."\n";

            return $player;
        }

        public function add_option($options, $post = null) {
            if (isset($post) and $post->feather != "audio") return;
            elseif (Route::current()->action == "write_post")
                if (!isset($_GET['feather']) and Config::current()->enabled_feathers[0] != "audio" or
                    isset($_GET['feather']) and $_GET['feather'] != "audio") return;

            $options[] = array("attr" => "from_url",
                               "label" => __("From URL?", "audio"),
                               "type" => "text");

            return $options;
        }
    }
