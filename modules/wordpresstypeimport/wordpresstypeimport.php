<?php
    class WordpressTypeImport extends Modules {
    
        public function import_wordpress_enhancement() {
            
            return '<p>'.__('When importing posts make sure that the required feathers are enabled otherwise the posts will be imported as text feathers.').'</p>';
        }
    
        public function import_wordpress_post_link($content, $item) {

            // test if link feather is activated

            $config = Config::current();
            if (!in_array("link", $config->enabled_feathers)) {
                return $content;
            }
            
            $linkdata = array();
        
            $linkdata['name'] = $content['content']['title'];
            
            $body = $content['content']['body'];
            
            // get first link in content body
            
            $source_link_start = strpos($content['content']['body'], 'href="');
            
            if ($source_link_start !== false) {
                $source_link_end = strpos($content['content']['body'], '"', $source_link_start + 6);
                
                if ($source_link_end !== false) {
                    $linkdata['source'] = substr($content['content']['body'], $source_link_start + 6, $source_link_end - $source_link_start - 6);
                }
            }
            
            // description is everything after the first paragraph
            
            $linkdata['description'] = trim(substr($content['content']['body'], strpos($content['content']['body'], '</p>') + 4));

            if (
                isset($linkdata['name']) &&
                isset($linkdata['source']) &&
                isset($linkdata['description'])
               ) {
                $content['feather'] = 'link';
                $content['content'] = $linkdata;
                return $content;
            }
            
            // fallback, return standard text post data
            
            return $content;
        }
        
        public function import_wordpress_post_quote($content, $item) {

            // test if quote feather is activated

            $config = Config::current();
            if (!in_array("quote", $config->enabled_feathers)) {
                return $content;
            }
            
            $quotedata = array();
        
            $body = $content['content']['body'];
            
            // get first blockquote in content body
            
            $source_link_start = strpos($content['content']['body'], '<blockquote>');
            
            if ($source_link_start !== false) {
                $source_link_end = strpos($content['content']['body'], '</blockquote>', $source_link_start + 12);
                
                if ($source_link_end !== false) {
                    $quotedata['quote'] = substr($content['content']['body'], $source_link_start + 12, $source_link_end - $source_link_start - 12);
                }
            }
            
            // source description is everything after the first paragraph
            
            $quotedata['source'] = trim(substr($content['content']['body'], strpos($content['content']['body'], '</blockquote>') + 13));

            if (
                isset($quotedata['quote']) &&
                isset($quotedata['source'])
               ) {
                $content['feather'] = 'quote';
                $content['content'] = $quotedata;
                return $content;
            }
            
            // fallback, return standard text post data
            
            return $content;
        }
        
        public function import_wordpress_post_video($content, $item) {

            // test if quote feather is activated

            $config = Config::current();
            if (!in_array("video", $config->enabled_feathers)) {
                return $content;
            }
            
            $videodata = array();
            
            // keep title
            
            $videodata['title'] = trim($content['content']['title']);
        
            $body = $content['content']['body'];
            
            // get first link in post body, it's the video url
            
            $urls = array();
            if (preg_match('$https?\://{1}\S+$', $body, $urls) && count($urls) > 0) {
                $videodata['video'] = $urls[0];
                $videodata['embed'] = Video::embed_tag($videodata['video']);
            }
            
            // everything else is the video caption
            
            if (isset($videodata['video'])) {
                $videodata['caption'] = trim(str_replace($videodata['video'], '', $body));
            }
            
            if (
                isset($videodata['title']) &&
                isset($videodata['video']) &&
                isset($videodata['embed']) &&
                isset($videodata['caption'])
               ) {
                $content['feather'] = 'video';
                $content['content'] = $videodata;
                return $content;
            }
            
            // fallback, return standard text post data
            
            return $content;
        }

        public function import_wordpress_post_image($content, $item) {

            // test if quote feather is activated

            $config = Config::current();
            if (!in_array("photo", $config->enabled_feathers)) {
                return $content;
            }
            
            $photodata = array();
            
            // keep title
            
            $photodata['title'] = $content['content']['title'];
        
            $body = $content['content']['body'];
            
            // get href of first link and/or src of first image tag
            
            $images = array();

            $a_hrefs = array();
            $img_src = array();
            if (preg_match('$<a href="([^>^"]*)"[^>]*>[S]*<img[^>]*src="([^>^"]*)"[^>]*>[S]*</a>$', $body, $a_hrefs) && count($a_hrefs) > 0) {
                $images[] = str_replace($config->url.$config->uploads_path, '', $a_hrefs[1]);
                $images[] = str_replace($config->url.$config->uploads_path, '', $a_hrefs[2]);
            }
            elseif (preg_match('$<img[^>]*src="([^>^"]*)"[^>]*>$', $body, $img_src) && count($img_src) > 0) {
                $images[] = str_replace($config->url.$config->uploads_path, '', $img_src[1]);
            }
            
            $images = array_unique($images);
            $images_sizes = array();
            
            foreach($images as $image) {
                // check image dimensions to choose the biggest image as source
                if (is_readable(MAIN_DIR.$config->uploads_path.$image)) {
                    $imginfo = getimagesize(MAIN_DIR.$config->uploads_path.$image);
                    $squarepixel = $imginfo[0] * $imginfo[1]; // size in square pixel
                    $images_sizes[$squarepixel] = $image;
                }
            }
            
            if (count($images_sizes) > 0) {
                ksort($images_sizes, SORT_NUMERIC);
                $photodata['filename'] = array_pop($images_sizes);
            }
            elseif (count($images) > 0) {
                $photodata['filename'] = $images[0];
            }
            
            // everything else is the photo caption
            
            if (count($a_hrefs) > 0) {
                $photodata['caption'] = trim(str_replace($a_hrefs[0], '', $body));
            }
            elseif (count($img_src) > 0) {
                $photodata['caption'] = trim(str_replace($img_src[0], '', $body));
            }
            
            $captions = array();
            if (preg_match('$\[caption[^\]]*\]([^\[]*)\[/caption\]$', $photodata['caption'], $captions) && count($captions) > 0) {
                // in some cases there is an caption element without image now
                $photodata['caption'] = str_replace($captions[0], '<p class="wp-caption-import">'.trim($captions[1]).'</p>'.PHP_EOL, $photodata['caption']);
            }
            
            if (
                isset($photodata['title']) &&
                isset($photodata['filename']) &&
                isset($photodata['caption'])
               ) {
                $content['feather'] = 'photo';
                $content['content'] = $photodata;
                return $content;
            }
            
            // fallback, return standard text post data
            
            return $content;
        }
    }
