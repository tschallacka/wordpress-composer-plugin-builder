<?php namespace Tschallacka\WPBuilder;
function is_cli()
{
    if ( defined('STDIN') )
    {
        return true;
    }
    
    if ( php_sapi_name() === 'cli' )
    {
        return true;
    }
    
    if ( array_key_exists('SHELL', $_ENV) ) {
        return true;
    }
    
    if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0)
    {
        return true;
    }
    
    if ( !array_key_exists('REQUEST_METHOD', $_SERVER) )
    {
        return true;
    }
    
    return false;
}
if(is_cli()) {
    $dir = getcwd();
    if(is_null($directory) || !$directory) {
        echo "Error, can't read the current working directory. Please make sure all permissions are set correctly. See http://php.net/manual/en/function.getcwd.php";
        exit(1);
    }
    $builder = new Builder($argv);
    $builder->run();
}