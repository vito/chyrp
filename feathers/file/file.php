<?php

/*
    TODO: support upload to subfolder
 */
    class File extends Feathers implements Feather {
        public function __init() {
            $this->chyrp_config = Config::current();
            $this->setField(array("attr" => "filename",
                                  "type" => "file",
                                  "label" => __("File", "file"),
                                  "note" => "<small>(Max. file size: ".ini_get('upload_max_filesize').")</small>"
                                  )
                                  
                                  );

            if (isset($_GET['action']) and $_GET['action'] == "bookmarklet")
                $this->setField(array("attr" => "from_url",
                                      "type" => "text",
                                      "label" => __("From URL?", "file"),
                                      "optional" => true,
                                      "no_value" => true));

            $this->setField(array("attr" => "title",
                                    "type"=> "text",
                                    "label" => __("Title", "file"),
                                    "optional" => true,
                                    "preview" => true,
                                    ));
                                    
            $this->setField(array("attr" => "caption",
                                  "type" => "text_block",
                                  "label" => __("Caption", "file"),
                                  "optional" => true,
                                  "preview" => true,
                                  ));


            if (isset($_GET['url'])/* and preg_match("/\.(jpg|jpeg|png|gif|bmp)$/", $_GET['url'])*/) {
                $this->bookmarkletSelected();

                $this->setField(array("attr" => "from_url",
                                      "type" => "text",
                                      "label" => __("From URL?", "file"),
                                      "optional" => true,
                                      "value" => $_GET['url']));
            }


            $this->setFilter("caption", array("markup_text", "markup_post_text"));
            $this->respondTo("delete_post", "delete_file");
            $this->respondTo("filter_post","filter_post");
            $this->respondTo("post_options", "add_option");
        }

        public function add_option($options, $post = null) {
            if (isset($post) and $post->feather != "file") return;
            elseif (Route::current()->action == "write_post")
                if (!isset($_GET['feather']) and Config::current()->enabled_feathers[0] != "file" or
                    isset($_GET['feather']) and $_GET['feather'] != "file") return;

            $options[] = array("attr" => "option[source]",
                               "label" => __("Source", "file"),
                               "type" => "text",
                               "value" => oneof(@$post->source, ""));

            $options[] = array("attr" => "from_url",
                               "label" => __("From URL?", "file"),
                               "type" => "text");

            $options[] = array("attr" => "mime_type",
                                "label" => __("Mime type","file"),
                                "type" => "text");

            return $options;
        }

        public function submit() {
            if (!isset($_POST['filename'])) {
                if (isset($_FILES['filename']) and $_FILES['filename']['error'] == 0)
                    $filename = upload($_FILES['filename']);
                elseif (!empty($_POST['from_url']))
                    $filename = upload_from_url($_POST['from_url']);
                else
                    error(__("Error"), __("Couldn't upload file."));
            } else
                $filename = $_POST['filename'];

            # Prepend scheme if a URL is detected
            if (preg_match('~^((([a-z]|[0-9]|\-)+)\.)+([a-z]){2,6}/~', @$_POST['option']['source']))
                $_POST['option']['source'] = "http://".$_POST['option']['source'];

            return Post::add(array("filename" => $filename,
                                   "caption" => $_POST['caption'],
                                   "title" => $_POST['title']),
                             $_POST['slug'],
                             Post::check_url($_POST['slug']));
        }

        public function update($post) {
            if (!isset($_POST['filename']))
                if (isset($_FILES['file']) and $_FILES['file']['error'] == 0) {
                    $this->delete_file($post);
                    $filename = upload($_FILES['file']);
                } elseif (!empty($_POST['from_url'])) {
                    $this->delete_file($post);
                    $filename = upload_from_url($_POST['from_url']);
                } else
                    $filename = $post->filename;
            else {
                $this->delete_file($post);
                $filename = $_POST['filename'];
            }

            # Prepend scheme if a URL is detected
            if (preg_match('~^((([a-z]|[0-9]|\-)+)\.)+([a-z]){2,6}/~', @$_POST['option']['source']))
                $_POST['option']['source'] = "http://".$_POST['option']['source'];

            $post->update(array("filename" => $filename,
                                "caption" => $_POST['caption'],
                                "title" => $_POST['title']
                                )
                                );
        }

        public function title($post) {
            return oneof($post->title,$post->title_from_excerpt(), $post->filename);
        }
        public function excerpt($post) {
            return $post->caption;
        }

        public function feed_content($post) {
            return $post->caption.'<br /><br />'.self::file_link($post);
        }
        public function delete_file($post) {
            if ($post->feather != "file") return;
            unlink(MAIN_DIR.Config::current()->uploads_path.$post->filename);
        }

        public function filter_post($post) {
            if ($post->feather != "file") return;
            
            $post->link = uploaded($post->filename);
            if (empty($post->caption)) $post->caption=$post->filename;
            
        }
        
        public function file_link($post) {
            return uploaded($post->filename);
        }
        
    }
