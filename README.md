# Wordpress Composer Plugin Builder

A plugin builder for publishing to the wordpress plugin store making sure all your composer dependencies are loaded into your own namespace, thus preventing conflicts between different required dependencies.  
You can of course use this if you just want to load up all the things in your project into your own namespace so you'll always have the versions you want.  

How to use it:

run composer with the following command

    composer require tschallacka\wpbuilder

or add in your composer.json the following line an then update.
```
"require": {
           "tschallacka/wpbuilder": "dev-master"
},
```

Then to execute it browse in your favorite cli(bash, cmd, command.com) to the directory where you wish to execute this. Chances are you already have your composer cli open here. Ideally you're in the same directory where you have your vendor folder.

    cd /d/www/mydevsite/wp-plugin/mypluginname

    php vendor/tschallacka/wpbuilder/build.php

It will create a new directory called build in your current working directory, and place all items there in your namespace. It will attempt to rename all references to the namespace  to the namespace you defined.

This program is far from pefect yet, but it should shave of a significant amount of time if you wish to use composer packages in your wordpress plugin, without having to worry about duplicates or older or newer versions of a package hampering your plugin.

**help.txt**

Welcome to Tschallacka's Composer to Wordpress Builder.
The purpose of this tool is to help you publishing your plugins in a wordpress
comfortable format without clashing versions of plugins with other plugins
(for example someone using an older version of Carbon breaking your plugin)
This will copy all files into a new directory, moving all the files into
the root namespace you defined.

Requirements:
- PSR-4 is expected for the autoparsing of the files. Anything not PSR-4 might
  break or need some manual renaming of classes/includes.
- Make sure your current working directory is the plugin base dir when
  executing this command
- This plugin will attempt to obey .gitignore with the sole exception
  of the vendor directory.
- Classes will more from example Carbon\Carbon to YourPlugin\Carbon\Carbon
  Keep that in memory when doing magic string that can't be str_replaced
  
The build plugin will have a PSR-4 autoload command for composer included.
Make sure that you include the vendor/autoload.php file in your plugin where
needed to make sure everything is loaded as expected.

Arguments:  
-h, --help Show this help  
--root-namespace=WordpressPlugin The root namespace under which all  
   loaded dependencies should be placed.  
   Ideally you will name this the same as the root namespace as your plugin  
   as otherwise your plugin will also be moved into this namespace.  
--build-source=/users/foo/Desktop/bar  If you wish to execute this script somewhere else   
   than the current working directory use this argument to define the   
   location of the source directory. The plugin will also be built in that  
   directory  


