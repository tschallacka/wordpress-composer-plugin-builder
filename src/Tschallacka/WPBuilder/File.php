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
        
        while(($line = fgets($f)) !== false) {
            $namespace = $this->matchNameSpace($line);
        
            if($namespace) {
                $this->namespaces[] = $namespace;
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
    
    /**
     * Matches a string to see if there's a namespace declaration in there.
     * https://gist.github.com/naholyr/1885879#file-compare-php-L36
     * @param string $line
     * @return string|NULL
     */
    protected function matchNameSpace($line)
    {
        if (preg_match('#namespace\s+(.+?);$#sm', $line, $m)) {
            return $m[1];
        }
        return null;
    }
}