<?php 
namespace Kpion\Spaghetti;


class Spaghetti
{
    public ?Database $db = null;
    public ?Markdown $md = null;

    // Root directory. By default input file's parent directory.
    protected ?string $root = null;

    // Main input file passed as an argument
    protected ?string $inputFile = null;

    public function __construct(array $argv, ?Markdown $md = null, ?Database $db = null, )
    {
        $this->md = $md?:new Markdown();
        $this->db = $db?:new Database(null, $this->md);
        $this->parseArgs($argv);
    }

    public function parseArgs(array $argv): void {
        $options = getopt("", ["cwd:"]); // Define long option `--cwd`
        
        $args = array_slice($argv, 1);
        if(count($args) === 0 || $args[count($args)-1] === ''){
            echo "Error: missing input file\n";
            exit(1);
        }
        
        // Set the input file (last argument in `$args`)
        $this->inputFile = $args[count($args)-1];
        
        // Process `--cwd` if provided
        if (isset($options['cwd'])) {
            $cwd = rtrim($options['cwd'], '/');
            if (!is_dir($cwd)) {
                echo "Error: Provided --cwd directory does not exist\n";
                exit(1);
            }
            if($this->isAbsolute($cwd)){
                $this->root = $cwd;
            }else{
                $this->root = getcwd() . '/' . $cwd;
            }
            if(!@chdir($this->root)){
                echo "Error: Could not change directory\n";
                exit(1);
            }
        }

        // Apparently we still don't know our roots. No --cwd in params.
        if($this->root === null){
            if($this->isAbsolute($this->inputFile)){
                $this->root = dirname($this->inputFile);
            }else{
                $this->root = dirname(getcwd() . '/' . $this->inputFile);
            }            
        }
        $this->inputFile = getcwd().'/'.$this->inputFile;
        if(!file_exists($this->inputFile())){
            echo "Input file doesn't exist: " . $this->inputFile() . "\n";
            exit (1);
        }
       
    }

    public function parseArgs2(array $argv): void {
        $options = getopt("", ["cwd:"]); // Parse --cwd option
        $args = array_slice($argv, 1);

        // Get the input file path from last argument
        $this->inputFile = end($args) ?: null;
        if (!$this->inputFile || !file_exists($this->inputFile)) {
            exit("Error: Missing or non-existent input file.\n");
        }

        // Set the root directory if --cwd is specified
        $this->root = isset($options['cwd']) ? realpath($options['cwd']) : dirname(realpath($this->inputFile));

        // Set root to current working directory if --cwd is invalid
        if (!$this->root || !is_dir($this->root)) {
            exit("Error: Provided --cwd directory is invalid.\n");
        }
        // var_dump($this);exit (0);
    }

    public function isRoot():bool 
    {
        return $this->root !== null;
    }

    public function setRoot(string $path): void {
        $path = rtrim($path,'/');
        $this->root = $this->isAbsolute($path) ? $path : realpath($this->inputDir() . '/' . $path);
    } 

    public function root(): string {
        return $this->root;
    }    

    // Get the directory of the input file. This often is the same as `root()`
    public function inputDir(): string 
    {
        return dirname($this->fullPath($this->inputFile));
    }

    public function inputFile():string {
        var_dump('inputFile: $this->root:',$this->root);
        var_dump('inputFile: this->inputFile:',$this->inputFile);
        
        // if($forceFullPath)
        // {
        //     return $this->fullPath($this->inputFile);
        // }
        return $this->inputFile;
    }

    // Check if a path is absolute
    public function isAbsolute(string $path): bool {
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path);
    }

    // Get the full path of a relative path
    public function fullPath(string $path): string {
        return $this->isAbsolute($path) ? $path : $this->root . '/' . ltrim($path, '/');
    }    

    // Returns a **parsed** content of a specified file/url.
    // This is evaluate it, so it's usefull for including .md.php files. Otherwise, if we 
    // want to include a code snippet, without evaluating, we should we ::file method.
    public function import (string $path, array $context = []):string{
        var_dump('import:',$path);
        $path = $this->fullPath($path);
        if(!file_exists($path)){
            return "File read error: $path\n";
        }
        // We want to pass us ($this) as $spaghetti. Unless the caller overwritten it. 
        // Plus, the caller can pass any other stuff they want to.
        if(!isset($context['spaghetti'])){
            $context['spaghetti'] = $this;
        }
        ob_start();
        extract($context, EXTR_SKIP);
        require ($path);
        $result = ob_get_clean();        
        return $result;
    }

    // Return the content of a specified file/url. Don't parse it.
    public function file(string $path): string {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $this->fetchUrlContent($path);
        }
        $path = $this->fullPath($path);
        return file_exists($path) ? file_get_contents($path) : "File read error: $path\n";
    }
  
    public function fetchUrlContent(string $url): string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            
            $content = curl_exec($ch);
            curl_close($ch);
            return $content ?: "URL read error: $url\n";
        }
        return ini_get('allow_url_fopen') ? file_get_contents($url) : "URL read error ($url): cURL and allow_url_fopen disabled\n";
    }

    // Returns a sorted directory structure in Markdown format with optional exclusions
    public function dir(string $directory, int $depth = 2, array $exclude = ['.git', 'vendor'],  int $indentationLevel = 0): string {
        $directory = $this->fullPath($directory);
        $output = "";

        // Scan current level of directory
        $items = array_diff(scandir($directory), ['.', '..']); // Exclude `.` and `..`

        // Sort items: directories first, then files, both in alphabetical order
        usort($items, function ($a, $b) use ($directory) {
            $aIsDir = is_dir($directory . DIRECTORY_SEPARATOR . $a);
            $bIsDir = is_dir($directory . DIRECTORY_SEPARATOR . $b);
            
            if ($aIsDir === $bIsDir) {
                return strcasecmp($a, $b); // Alphabetical comparison if both are same type
            }
            return $aIsDir ? -1 : 1; // Directories come first
        });

        foreach ($items as $item) {
            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;

            // Exclude directories/files based on the exclude list
            if (in_array($item, $exclude)) {
                continue;
            }

            // Add current item to output with appropriate icon
            $indentation = str_repeat('    ', $indentationLevel);
            $output .= $indentation  . (is_dir($fullPath) ? "📂 " : "📄 ") . $item . "\n";

            // If the item is a directory and we have more depth to go, recurse
            if (is_dir($fullPath) && $depth > 1) {
                $output .= $this->dir($fullPath, $depth - 1, $exclude, $indentationLevel + 1);
            }
        }

        //return $indentationLevel === 0 ? "```markdown\n$output\n```\n" : $output;
        return $output;
    }


}

