<?php

namespace adeattwood\logviewer\assets;

/**
 * Asset for the package loads in all the required js, css and fonts
 *
 * @category  PHP
 * @package   adeattwood/yii2-log-viewer
 * @author    Ade Attwood <attwood16@googlemail.com>
 * @copyright 2017 adeattwood.co.uk
 * @license   BSD-2-Clause http://adeattwood.co.uk/license.html
 * @link      https://github.com/AdeAttwood/yii2-log-viewer
 * @since     v0.1
 */
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