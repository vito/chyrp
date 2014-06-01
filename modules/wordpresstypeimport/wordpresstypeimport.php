<?php
    class WordpressTypeImport extends Modules {
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
            
            $linkdata['description'] = substr($content['content']['body'], strpos($content['content']['body'], '</p>') + 4);

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
            
            $quotedata['source'] = substr($content['content']['body'], strpos($content['content']['body'], '</blockquote>') + 13);

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
    }
