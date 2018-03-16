<?php namespace Tschallacka\WPBuilder;

/**
 * File wrapper for the Wordpress Plugin Builder
 * It's an interface to get certain properties from
 * given file.
 * @author Tschallacka
 */
class File
{
    /**
     * @var \SplFileInfo
     */
    protected $file;
    
    protected $raw_dir;
    
    /**
     * Multiple namespaces in one file is possible unfortunately.
     * For those that don't obey psr :S
     * @var array
     */
    protected $namespaces = [];
    
    protected $manual_override = false;
    
    public static $class_to_namespace = [];
    
    public function __construct(\SplFileInfo $file)
    {
        global $directory;
        $this->file = $file;
        $this->raw_dir = str_replace($directory, '', dirname($this->getPath()));
        $this->loadNameSpace();
    }
    
    /**
     * Get the file objects
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }
    
    public function getExtension()
    {
        return strtolower($this->getFile()->getExtension());
    }
    
    public function isPhp()
    {
        return $this->getExtension() == 'php';
    }
    
    public function getPath()
    {
        return $this->getFile()->getRealPath();
    }
    
    public function hasNameSpace() 
    {
        return (bool)count($this->namespaces);    
    }
    
    public function getNameSpaces()
    {
        return $this->namespaces;
    }
    
    /**
     * If this is a namespaceless php file in need of a manual override
     * to our root namespace. This will trigger special errors.
     * @return boolean
     */
    public function needsManualOverride()
    {
        return $this->manual_override;
    }
    
    public static function getRootNamespaceClasses() {
        if(array_key_exists(Config::$ROOT_NAMESPACE, self::$class_to_namespace)) {
            return self::$class_to_namespace[Config::$ROOT_NAMESPACE];
        }
        return [];
    }
    
    /**
     * This function assumes psr-4 is followed.
     * or something similar.
     * or not at all.
     * we do our best
     * ...
     *
     */
    protected function loadNameSpace()
    {
        $f = fopen($this->getPath(), 'r');
        $lastNameSpace = Config::$ROOT_NAMESPACE;
        while(($line = fgets($f)) !== false) {
            $namespace = $this->matchNameSpace($line);
            
            if($namespace) {
                $lastNameSpace = $namespace;
                $this->namespaces[] = $namespace;
            }
            if($this->isPhp()) {
               $class = $this->matchClass($line);
               if($class) {
                   $this->addClass($lastNameSpace, $class);
               }
            }
        }
        fclose($f);
        
        /**
         * This catch WILL break plugins if they don't account
         * for the namespace change of namespaceless code to namespaced to root namespace.
         */
        if(count($this->namespaces) === 0) {
            $this->namespaces[] = Config::DEFAULT_NAMESPACE;
            $this->manual_override = true;
        }
    }
    
    
    
    protected function addClass($ns, $class) 
    {
        if(!array_key_exists($ns, self::$class_to_namespace)) {
            self::$class_to_namespace[$ns] = [];
        }
        self::$class_to_namespace[$ns][] = $class; 
    }
    
    protected function matchClass($line) 
    {
        if(preg_match('/(class)(\s+)([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(.*)({*|$)/sm',$line, $m)) {
            return $m[3];
        }
        return null;
    }
    
    /**
     * Matches a string to see if there's a namespace declaration in there.
     * https://gist.github.com/naholyr/1885879#file-compare-php-L36
     * @param string $line
     * @return string|NULL
     */
    protected function matchNameSpace($line)
    {
        if (preg_match('#(namespace)(\\s+)([A-Za-z0-9\\\\]+?)(\\s*);#sm', $line, $m)) {
            return $m[3];
        }
        return null;
    }
}