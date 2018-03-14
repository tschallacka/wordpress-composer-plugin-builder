<?php namespace Tschallacka\WPBuilder;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Builder 
{
    /**
     * Where to find text files for printing
     * @var string
     */
    protected $asset_path;
    
    /**
     * Where to place the files during the build
     * process
     * @var string
     */
    protected $build_dir;
    
    protected $gitignores = [];
    
    protected $allfiles = [];
    
    protected $ignore_files = [];
    
    protected $edit_files = [];
    
    protected $namespace_roots = [];
    
    protected $modified_list = [];
    
    public function __construct($argv = []) 
    {
        $this->parseArguments($argv);
    }
    
    public function run() 
    {
        $this->runIntro();
        $this->checkBuildDir();
        $this->harvestFiles();
        $this->harvestGitIgnored();
        $this->harvestEditFiles();
        $this->harvestNamespaces();
        $this->print("Finished preparations. Now to get down to work.");
        $this->createBuildDir();
        $this->generateDirectoryTree();
        $this->doTheMagic();

        if(count($this->modified_list)) {
            $this->printFile('nons.txt');
            $this->printModified();
        }
    }
    
   
    protected function printModified()
    {
        foreach($this->modified_list as $item) {
            $this->print(sprintf(" |- $s", $item));
        }
    }
    
    protected function doTheMagic() 
    {
        $modified_list = [];
        /**
         * @var $file File
         */
        foreach($this->edit_files as $file ) {
            $path = $file->getPath();
            
            $new_location = $this->getBuildFilepath($file);
            
            if(in_array($file->getExtension(), Config::$TEXT_TYPES)) {
                $contents = file_get_contents($path);
                foreach($this->namespace_roots as $root) {
                    $contents = $this->replaceNamespace($root, $contents);
                }
                $contents = $this->checkManualOverride($file, $contents);
                file_put_contents($new_location, $contents);
                $this->print(sprintf("Transformed %s to %s", $path, $new_location));
                continue;
            }
            copy($path, $new_location);
            $this->print(sprintf("Copied %s to %s", $path, $new_location));
        }
    }
    
    protected function checkManualOverride(File $file, $contents) 
    {
        if($file->isPhp() && $file->needsManualOverride()) {
            $haystack = $contents;
            $needle = '<?php';
            $replace = '<?=php namespace '.Config::$ROOT_NAMESPACE.';';
            $modified_list[] = $this->getBuildFilepath($file);
            $pos = strpos($haystack, $needle);
            if ($pos !== false) {
                $contents = substr_replace($haystack, $replace, $pos, strlen($needle));
            }
        }
        return $contents;
    }
    
    protected function replaceNamespace($root, $str) {
        
        return preg_replace_callback("/(\\\\*)(".$root.")/",function($matches) {
            
            if($matches[2] !== Config::$ROOT_NAMESPACE) {
                return $matches[1] . Config::$ROOT_NAMESPACE . (empty($matches[1]) ? '\\' : $matches[1]) . $matches[2];
            }
        },$str);
    }
    
    protected function createBuildDir() 
    {
        
        $this->print("Creating build directory");
        $created = mkdir($this->build_dir);
        if(!$created) {
            $this->print("Failed to create build directory. Please check your permissions");
            exit(3);
        }
       
    }
    
    protected function getBuildDirectory(File $file) 
    {
        $raw_dir = str_replace(Config::$DIRECTORY, '', dirname($file->getPath()));
        $new_dir = $this->build_dir . $raw_dir;
        return $new_dir;
    }
    
    
    protected function getBuildFilepath(File $file)
    {
        $raw_file = str_replace(Config::$DIRECTORY, '', $file->getPath());
        $new_file = $this->build_dir . $raw_file;
        return $new_file;
    }
    
    protected function harvestNamespaces() 
    {
        $namespace_array = [];
        foreach($this->edit_files as $file) {
            $namespaces = $file->getNameSpaces();
            if(count($namespaces)) {
                foreach($namespaces as $namespace) {
                    $namespace_array[] = $namespace;
                }
            }
        }
        $namespace_array = array_filter($namespace_array);
        $namespace_array = array_unique($namespace_array);
        sort($namespace_array);
        
        $namespace_roots = [];
        foreach($namespace_array as $namespace) {
            $slash = strpos($namespace,'\\');
            if($slash !== false) {
                $namespace_roots[] = substr($namespace, 0, $slash);
            }
        }
        $this->namespace_roots = array_unique($namespace_roots);
    }
    
    protected function generateDirectoryTree() 
    {
        $this->print("Generating directory tree");
        foreach($this->edit_files as $file) {
            $new_dir = $this->getBuildDirectory($file);
            $this->createDirectory($new_dir);   
        }
    }
    
    protected function createDirectory($new_dir) 
    {
        if(!is_dir($new_dir)) {
            $created = mkdir($new_dir, 0777, TRUE);
            if($created) {
                $this->print(sprintf(" - Created directory %s",$new_dir));
            }
            else {
                $this->print(sprintf("Failed to create %s",$new_dir));
                exit(4);
            }
        }
    }
    
    protected function print($message) 
    {
        echo "$message\n";
    }
    
    protected function harvestEditFiles() 
    {
        $this->print("Filtering lists based on .gitignore entries");
        $this->edit_files = array_filter($this->allfiles, function(File $file) {
            $filepath = $file->getPath();
            foreach($this->ignore_files as $ignore) {
                if(strpos($filepath, $ignore) === 0) {
                    return false;
                }
            }
            return strpos($filepath, '.git'.DIRECTORY_SEPARATOR) === false;
        });
    }
    
    protected function harvestGitIgnored() 
    {
        foreach($this->gitignores as $gitignore) {
            
            $this->ignore_files += $this->parseGitIgnoreFile($gitignore);
        }
        $this->print("These files\directories will be ignored");
        foreach($this->ignore_files as $file) {
            $this->print(sprintf(" - %s",$file));
        }
    }
    
    protected function harvestFiles() 
    {
        $di = new RecursiveDirectoryIterator(Config::$DIRECTORY,RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($di);
        
        foreach($it as $file) {
            if (basename($file) == '.gitignore') {
                $this->gitignores[] = $file->getRealPath();
                continue;
            }
            
            $this->allfiles[] = new File($file);
        }
        
        $this->print(sprintf("Found %s gitignore setting files",count($this->gitignores)));
    }
    
    protected function getGitIgnores() 
    {
        return $this->gitignores;
    }
    
    protected function getBuildDir() 
    {
        if(is_null($this->build_dir)) {
            $this->checkBuildDir();
        }
        
        return $this->build_dir;    
    }
    
    protected function checkBuildDir() 
    {
        $this->build_dir = Config::$DIRECTORY . DIRECTORY_SEPARATOR . 'build';
        
        if(file_exists($this->build_dir) && is_dir($this->build_dir)) {
            $this->print(        "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            $this->print(sprintf("Error, build dir %s already exists. Please remove/rename\n".
                                 "the build directory manually to continue.\n",$this->build_dir));
            exit(2);
        }
    }
    
    /**
     * @author Denis de Bernardy https://stackoverflow.com/users/417194/denis-de-bernardy
     * @url https://stackoverflow.com/questions/19981583/php-filtering-files-and-paths-according-gitignore#20151790
     * @param string $file
     * @return string[] git ignored files
     */
    protected function parseGitIgnoreFile($file) { # $file = '/absolute/path/to/.gitignore'
        $dir = dirname($file);
        $matches = array();
        $lines = file($file);
        foreach ($lines as $line) {
            
            $line = trim($line);
            if ($line === '') continue;                 # empty line
            if($line == 'vendor' || $line == '/vendor') continue; #vendor directory
            if (substr($line, 0, 1) == '#') continue;   # a comment
            if (substr($line, 0, 1) == '!') {           # negated glob
                $line = substr($line, 1);
                $files = array_diff(glob("$dir".DIRECTORY_SEPARATOR."*"), glob("$dir".DIRECTORY_SEPARATOR."$line"));
            } else {                                    # normal glob
                $files = glob("$dir".DIRECTORY_SEPARATOR."$line");
            }
            $matches = array_merge($matches, $files);
        }
        /**
         * clean up \/ double slashes
         */
        foreach($matches as $key => $file) {
            $matches[$key] = realpath($file);
        }
        return $matches;
    }
    
    protected function getAssetPath($filename) 
    {
        if(is_null($this->asset_path))  {
            $this->asset_path = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR; 
        }
        return $this->asset_path . $filename;
    }
    
    protected function readFile($file) 
    {
        $filename = $this->getAssetPath($file);
        if(is_file($filename) && !is_dir($filename)) {
            return file_get_contents($filename);
        }
        return '';
    }
    
    
    
    protected function parseArguments($argv = []) 
    {
        foreach( $argv as $arg ) {
            $arg = trim($arg);
            $arg_lowercase = strtolower($arg);
            $this->checkAction($arg, $arg_lowercase);
        }
    }
    
    protected function checkAction($arg, $arg_lowercase) 
    {
        if($arg_lowercase == Config::HELP || $arg_lowercase == Config::HELP_ALT) {
            $this->runHelp();
            return;
        }

        if(strpos($arg_lowercase, Config::ROOT_NAMESPACE_ARG) === 0) {
            Config::$ROOT_NAMESPACE = substr($arg, strpos($arg, '=') + 1);
            return;
        }
        
        if(strpos($arg_lowercase, Config::BUILD_DIRECTORY_ARG) === 0) {
            Config::$DIRECTORY = substr($arg, strpos($arg, '=') + 1);
            return;
        }
    }
    
    public function setDirectory($dir) 
    {
        if(!is_dir($dir)) {
            $this->print(sprintf("Error, can't find directory %s.\n".
                                 "Please make sure you entered the path correctly.\n".
                                 "See http://php.net/manual/en/function.getcwd.php",$dir));
            exit(1);
        }
        Config::$DIRECTORY = $dir;
    }
    
    protected function printFile($file) 
    {
        echo $this->readFile($file);    
    }
    
    protected function runHelp() 
    {
         $help = $this->readFile('help.txt');
         $parsed = sprintf($help, Config::HELP, Config::HELP_ALT,Config::KEEP_BUILD_ARG, Config::ROOT_NAMESPACE_ARG, Config::DEFAULT_NAMESPACE, Config::BUILD_DIRECTORY_ARG);
         echo $parsed;
         exit(0);
    }
    
    protected function runIntro()
    {   
        $intro = $this->readFile('intro.txt');
        $parsed = sprintf($intro, Config::KEEP_BUILD_ARG);
        $this->print($parsed);
        $this->print("Running with the following settings");
        $this->print(sprintf("= Starting execution in directory %s",Config::$DIRECTORY));
        $this->print(sprintf("= Root namespace = %s",Config::$ROOT_NAMESPACE));
    }
}
