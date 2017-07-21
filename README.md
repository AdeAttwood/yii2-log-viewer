# Log viewer module for yii2

This module renders the yii2 logs into a easier to read format. The module also provide sorting and filtering functionality.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

~~~
composer require adeattwood/yii2-log-viewer
~~~

or add

~~~
"adeattwood/yii2-log-viewer": "*"
~~~

to the require section of your `composer.json` file.

## Configuration

Once the extension is installed, simply modify your application configuration as follows:

~~~php
return [
    'bootstrap' => [ 'log-viewer' ],
    'modules' => [
        'log-viewer' => [
            'class' => 'adeattwood\logviewer\Module',
            // other configuration
        ],
        // ...
    ],
    // ...
];
~~~

The module will now be accessible from `/log-viewer` where you can view a table of your logs

## Other Configuration

~~~php
'log-viewer' => [
    'class' => 'adeattwood\logviewer\Module',
    'logLimit' => 10000,   // The amount of log items to send to the view.
    'logCacheTime' => 30,  // The amount of time the log items will be cached in seconds.
    'pageCacheTime' => 30, // The amount of time the page html will be cached in seconds.
    'tableColors' => true  // Different colors for different log levels in the table.
    'allowedIPs' => [      // The ip addressed allowed to access the logs view.
        '127.0.0.1',
        '192.168.0.*',
        '::1' 
    ],
],
~~~
