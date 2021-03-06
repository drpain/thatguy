<?php

// This class will be used to merge an array of files into a single file to make the number of connections used less.

class Merge
{
    public $dir = '';                   // Set the directory of operation
    public $inDir = '';                 // The inbound directory
    public $outputFile = '';            // set the output file to be produced.
    public $files = array();            // Array which we will use to add the files to be merged

    // Bootstrap the merge function
    public function __construct($dir)
    {
        if ($dir)
        {
            // Add the out in order to seperate the in and out
            $this->dir = (substr($dir, -1) == '/') ? $dir : $dir . "/";
            $this->inDir = $this->dir;
            $this->dir = $this->dir . "out/";

            // Check if the directory exists and then create it
            if (!file_exists($this->dir)) mkdir($this->dir);
            return true;
        }
        return false;
    }

    // This function is responsible for creating the output file to be refreshed.
    public function output($output=false)
    {
        if ($output == false)
        {
            if ($this->outputFile !== '')
            {
                return $this->dir . $this->outputFile;
            } else {
                return false;
            }
        } else {
            $this->outputFile = $output;
            return true;
        }
    }

    // This function will add the files.
    public function add($file)
    {
        if ($file !== '')
        {
            $this->files[] .= $file;
        }
        return false;
    }

    // This function will rmeove a file.
    public function remove($file)
    {
        if ($file !== '')
        {
            if(($key = array_search($file, $this->files)) !== false)
            {
                unset($this->files[$file]);
            }
            return true;
        }
        return false;
    }

    // This function will do a test merge to compare the size to the current file.
    public function getSize()
    {
        $output = '';
        if (($this->outputFile !== '')
        && (count($this->files) > 0))
        {
            foreach($this->files as $file)
            {
                $full_path = $this->parse($file);
                if ($full_path !== false)
                {
                    $handle     = fopen($full_path, "r");
                    $contents   = fread($handle, filesize($full_path));
                    fclose($handle);
                    $contents   = $this->minify($contents);
                    $output .= $contents;
                }
            }

            $tempFile = $this->dir . time() . uniqid();
            $handle = fopen($tempFile, "w+");
            fwrite($handle, $output);
            fclose($handle);
            $filesize = filesize($tempFile);
            unlink($tempFile);
            return $filesize;

        }
        return false;
    }



    # I would like to minify the CSS and JS a bit more
    public function miniFy($content)
    {
        $find       = array("\r", "\t");
        $replace    = array("\n", "");
        $content    = str_replace($find, $replace, $content);
        $lines      = explode("\n", $content);
        $newContent = array();

        foreach($lines as $lid=>$line)
        {
            $line = trim($line);

            # Remove lines with // Comments
            if (substr($line, 0, 2) == "//")
            {
                continue;
            }

            # Remove lines with /* */ Comments
            if ((substr($line, 0, 2) == "/*") AND (substr($line, (strlen($line) - 2)) == "*/"))
            {
                continue;
            }

            if (!empty($line))
            {
                $newContent[] = $line;
            }
        }

        $content = implode("\n", $newContent);
        return $content;
    }


    public function export()
    {
        $size = $this->getSize();
        $exists = Cache::exists($this->output());

        if (($exists == false)
        or ($size == false)
        or (Cache::changed($this->output(), $size)))
        {
            $output = '';
            if (($this->output() !== '')
            && (count($this->files) > 0))
            {
                foreach($this->files as $file)
                {
                    $full_path = $this->parse($file);
                    if ($full_path !== false)
                    {
                        $handle = fopen($full_path, "r");
                        $contents = fread($handle, filesize($full_path));
                        fclose($handle);
                        $contents   = $this->minify($contents);
                        $output .= $contents;
                    }
                }

                // White the page specific merged file
                $export = $this->output();
                $handle = fopen($export, "w+");
                fwrite($handle, $output);
                fclose($handle);
                Cache::add($this->output(), $size);
                return true;
            }
            return false;
        }
    }

    // This will cehck if specified files exists in order to combine them
    public function parse($file)
    {
        return $this->inDir . $file;
    }

}

?>