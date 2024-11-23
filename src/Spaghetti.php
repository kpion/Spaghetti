<?php 
namespace Kpion\Spaghetti;

use \Exception;

class Spaghetti
{
    public ?Database $db = null;
    public ?AbstractFormatter $formatter = null;

    // Project Root directory. By default cwd (current working dir), i.e. where the program was started
    protected ?string $projectRoot = null;

    // Document Root directory. By default input file's parent directory.
    protected ?string $docRoot = null;

    // Main input file passed as an argument
    protected ?string $inputFile = null;

    /**
     * Cooks up a new spaghetti instance.
     * 
     * @param array $argv optional CLI arguments. If not given we can use e.g. setInputFile later.
     * @param AbstractFormatter $formatter AbstractFormatter instance, we'll use Markdown if null given
     */
    public function __construct(?array $argv, ?AbstractFormatter $formatter = null, ?Database $db = null)
    {
        $this->formatter = $formatter?:new Markdown();
        $this->db = $db?:new Database(null, $this->formatter);
        if($argv !== null){
            $this->parseArgs($argv);
        }
    }

    public function parseArgs(array $argv): void {
        $options = getopt("", ["project:"]); // Define long option `--cwd`
        
        $args = array_slice($argv, 1);
        if(count($args) === 0 || $args[count($args)-1] === ''){
            echo "Error: missing input file\n";
            exit(1);
        }
        
        // Set the input file (last argument in `$args`)
        $this->inputFile = $args[count($args)-1];
        
        // Process `--cwd` if provided
        if (isset($options['project'])) {
            $projectRoot = rtrim($options['project'], '/');
            if (!is_dir($projectRoot)) {
                echo "Error: Provided --cwd directory does not exist\n";
                exit(1);
            }
            if($this->isAbsolute($projectRoot)){
                $this->projectRoot = $projectRoot;
            }else{
                $this->projectRoot = getcwd() . '/' . $projectRoot;
            }
            if(!@chdir($this->projectRoot)){
                echo "Error: Could not change directory\n";
                exit(1);
            }
        }

        // Apparently we still don't know our roots. No --project in params. We'll use cwd
        if($this->projectRoot === null){
            $this->projectRoot = getcwd();
        }

        if(!$this->isAbsolute($this->inputFile)){
            $this->inputFile = getcwd().'/'.$this->inputFile;
        }

        $this->docRoot = dirname($this->inputFile);

        if(!file_exists($this->inputFile())){
            echo "Input file doesn't exist: " . $this->inputFile() . "\n";
            exit (1);
        }
    }

    /**
     * Just an alias for $this->import($this->inputFile());, so we can start everything so nicely: echo (new Spaghetti($argv))->run();
     */
    public function run(){
        return $this->import($this->inputFile());
    }

    // Root directory of main project's source files
    public function setProjectRoot(string $path): void {
        if(!$this->isAbsolute($path)){
            throw new Exception ("setProjectRoot requires an absolute path"); // Otherwise, relative to what?
        }
        $path = rtrim($path,'/');
        $this->docRoot = $path;
    } 

    // Root directory of main project's source files
    public function projectRoot(): string {
        return $this->projectRoot();
    }    

    // Get the full path of a relative path (in the context of projectRoot)
    public function fullProjectPath(string $path): string {
        return $this->isAbsolute($path) ? $path : $this->projectRoot . '/' . ltrim($path, '/');
    }    


    // Root directory of documentation
    public function setDocRoot(string $path): void {
        $path = rtrim($path,'/');
        $this->docRoot = $this->isAbsolute($path) ? $path : realpath($this->docRoot() . '/' . $path);
    } 

    // Root directory of documentation
    public function docRoot(): string {
        return $this->docRoot;
    }    

    // Get the full path of a relative path (in the context of docRoot)
    public function fullDocPath(string $path): string {
        return $this->isAbsolute($path) ? $path : $this->docRoot . '/' . ltrim($path, '/');
    }    

    public function setInputFile(string $inputFile):static {
        $this->inputFile = $inputFile;
        return $this;
    }
   
    public function inputFile():string {
        return $this->inputFile;
    }

    // Check if a path is absolute
    public function isAbsolute(string $path): bool {
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path);
    }

    // Returns a **parsed** content of a specified file/url.
    // This is evaluate it, so it's usefull for including .md.php files. Otherwise, if we 
    // want to include a code snippet, without evaluating, we should we ::file method.
    public function import (string $path, array $context = []):string{
        $path = $this->fullDocPath($path);
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
            return $this->fetch($path);
        }
        $path = $this->fullProjectPath($path);
        return file_exists($path) ? file_get_contents($path) : "File read error: $path\n";
    }
  
    public function fetch(string $url): string {
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
        $directory = $this->fullProjectPath($directory);
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
            $output .= $indentation  . (is_dir($fullPath) ? "📂 " : "📄 ") . $this->formatter->sanitize($item,64) . "\n";

            // If the item is a directory and we have more depth to go, recurse
            if (is_dir($fullPath) && $depth > 1) {
                $output .= $this->dir($fullPath, $depth - 1, $exclude, $indentationLevel + 1);
            }
        }
        return $output;
    }
}

