<?php
/**
 * PHP YAML FrontMatter Class
 * A simple and easy to use class to handle YAML Front-Matter.
 * 
 * @author David D'hont (blaxus@gmail.com)
 * @author Arian Xhezairi (http://xhezairi.com)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @version 1.1.00
 */
class FrontMatter {
    private $data;

    /**
     * Constructor method, checks a file and then puts the contents into custom strings for usage
     *
     * Parameters:
     *     $file - The file being parsed
     */
    public function __construct($file) {
        $file = $this->read_file($file);
        $fm = $this->front_matter($file);

        foreach ($fm as $key => $value)
            $this->data[$key] = $value;
    }

    /**
     * Function: fetch
     * Return $value by $key reference.
     */
    protected function fetch($key) {
        return $this->data[$key];
    }

    /**
     * Function: front_matter
     * FrontMatter method, rturns all the variables from a YAML Frontmatter input.
     *
     * Parameters:
     *     $content - The file contents
     *
     * Returns:
     *     $final - returns all variables in an array
     */
    protected function front_matter($content) {
        $output = explode("---\n", $content);    # Explode Seperators

        foreach ($output as $key => $value) {
            if (!empty($value)) {    # Ignore empty nodes
                $lastChar = substr($value, -1);    # Trap last Character in string
                # Return string without last character if it is a newline, Otherwise use normal value
                $tmp[$key] = ($lastChar == "\n") ? substr($value, 0, -1) : $value ;
            }
        }

        $vars = explode("\n", $tmp[1]);    # Explode newlines only for the variables

        foreach($vars as $variable) {
            # Explode so we can see both key and value
            $var = explode(": ", $variable);

            # Store Key and Value
            $key = $var[0];
            $val = $var[1];
            $final[$key] = $val;
        }

        # Store Content in Final array
        $final["content"] = $tmp[2];
        return $final;
    }

    /**
     * Function: read_file
     * Read file and returns it's contents.
     *
     * Parameters:
     *     $file - the file to read
     *
     * Returns:
     *     $data - returned data
     */
    protected function read_file($file) {
        $fh = fopen($file, "r");
        $data = fread($fh, filesize($file));

        # Fix Data Stream to be the exact same format as PHP's strings
        $data = str_replace(array("\r\n", "\r", "\n"), "\n", $data); 
        fclose($fh);

        return $data;
    }
}
