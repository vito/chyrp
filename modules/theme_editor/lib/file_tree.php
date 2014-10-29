<?php
# PHP FILE TREE	
# AUTHOR: Cory S.N. LaViska (http://abeautifulsite.net/)
# Adapted for Chyrp by the Chyrp team.

function php_file_tree($directory, $return_link, $extensions = array()) {
    # Generates a valid XHTML list of all directories, sub-directories, and files in $directory
    # Remove trailing slash
    if( substr($directory, -1) == "/" ) $directory = substr($directory, 0, strlen($directory) - 1);
    $code = php_file_tree_dir($directory, $return_link, $extensions);
    return $code;
}

function php_file_tree_dir($directory, $return_link, $extensions = array(), $first_call = true) {
    # Recursive function called by php_file_tree() to list directories/files
    # Get and sort directories/files
    if( function_exists("scandir") ) $file = scandir($directory); else $file = php4_scandir($directory);
    natcasesort($file);
    # Make directories first
    $files = $dirs = array();
    foreach($file as $this_file) {
        if( is_dir("$directory/$this_file" ) ) $dirs[] = $this_file; else $files[] = $this_file;
    }

    #unset($dirs[$key = array_search('images', $dirs)]);
    $file = array_merge($dirs, $files);
    
    # Filter unwanted extensions
    if( !empty($extensions) ) {
        foreach( array_keys($file) as $key ) {
            if( !is_dir("$directory/$file[$key]") ) {
                $ext = substr($file[$key], strrpos($file[$key], ".") + 1); 
                if( !in_array($ext, $extensions) ) unset($file[$key]);
            }
        }
    }

    $theme_file_tree = "";
    if( count($file) > 2 ) { # Use 2 instead of 0 to account for . and .. "directories"
        $theme_file_tree = "<ul";
        if( $first_call ) { $theme_file_tree .= " class=\"theme-file-tree\""; $first_call = false; }
        $theme_file_tree .= ">";
        foreach( $file as $this_file ) {
            if( $this_file != "." && $this_file != ".." ) {
                if( is_dir("$directory/$this_file") ) {
                    # Directory
                    $theme_file_tree .= "<li class=\"pft-dir\"><a href=\"#\">" . htmlspecialchars($this_file) . "</a>";
                    $theme_file_tree .= php_file_tree_dir("$directory/$this_file", $return_link ,$extensions, false);
                    $theme_file_tree .= "</li>";
                } else {
                    # File - Get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
                    # $ext = "ext-" . substr($this_file, strrpos($this_file, ".") + 1);
                    $theme = Config::current()->theme;
                    $len = strlen($theme);
                    $dir = substr(stristr($directory, $theme."/"), $len);
                    $link = str_replace("[link]", "$dir/" . urlencode($this_file), $return_link);
                    $theme_file_tree .= "<li class=\"pft-file\"><em><a href=\"$link\">" . htmlspecialchars($this_file) . "</a></em></li>";
                }
            }
        }
        $theme_file_tree .= "</ul>";
    }
    return $theme_file_tree;
}

# For PHP4 compatibility
function php4_scandir($dir) {
    $dh  = opendir($dir);
    while( false !== ($filename = readdir($dh)) ) {
        $files[] = $filename;
    }
    sort($files);
    return($files);
}
