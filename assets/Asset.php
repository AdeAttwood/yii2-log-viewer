<?php

namespace adeattwood\logviewer\assets;

class Asset extends \yii\web\AssetBundle
{
    /**
     * @var String Path the the vue when installed with bower
     */
    public $sourcePath = __DIR__ . '/dist';
    
    /**
     * @var Array List of js files to include in the asset
     */
    public $js = [ 'js/log-viewer.js' ];

    /**
     * @var Array List of css files to include in the asset
     */
    public $css = [ 'css/log-viewer.css' ];
}