<?php namespace Tschallacka\WPBuilder;

/**
 * @author Tschallacka
 * Simple config holder, which allows for the changing of values as the script progresses.
 */
class Config {
    
    /**
     * This command line param defines wether to show the help and then exit
     * @var string
     */
    const HELP = '-h';
    
    /**
     * This command line param defines wether to show the help and then exit
     * @var string
     */
    const HELP_ALT = '--help';
    
    /**
     * Default namespace to use when no namespace is defined with --root-namespace
     * @var string
     */
    const DEFAULT_NAMESPACE = "WordpressPlugin";
    
    /**
     * This command line param defines What to use as the root namespace
     * @var string
     */
    const ROOT_NAMESPACE_ARG = '--root-namespace=';
    
    /**
     * If the build directory shouldn't be the current working directory
     * use this command to overwrite the current working directory
     * @var string
     */
    const BUILD_DIRECTORY_ARG = '--build-source=';
    
    /**
     * The root namespace. Either the DEFAULT_NAMESPACE or what's defined
     * by --root-namespace
     * @var string
     */
    public static $ROOT_NAMESPACE = self::DEFAULT_NAMESPACE;
    
    /**
     * The working directory is stored here, where the plugin will be built from
     * @var string
     */
    public static $DIRECTORY = '';
    
    /**
     * Text types to scan for possible namespaces
     * @var string[]
     */
    public static $TEXT_TYPES = [
        'txt',
        'htm',
        'html',
        'php',
        'js',
        'yaml',
        'json',
        'xml',
        'wsdl',
        'ini',
        'config',
        'readme',
        'csv',
        'xhtml',
        'cfg',
        'prf',
        'preferences',
        'settings',
    ];
}