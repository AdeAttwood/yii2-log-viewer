<?php

namespace adeattwood\logviewer;

use Yii;
use yii\web\ForbiddenHttpException;

/**
 * The main module class
 *
 * @category  PHP
 * @package   adeattwood/yii2-log-viewer
 * @author    Ade Attwood <attwood16@googlemail.com>
 * @copyright 2017 adeattwood.co.uk
 * @license   BSD-2-Clause http://adeattwood.co.uk/license.html
 * @link      https://github.com/AdeAttwood/yii2-log-viewer
 * @since     v0.1
 */
class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{

    /**
     * The current version of this module.
     *
     * @var string
     */
    public $version = '1';

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'adeattwood\logviewer\controllers';

    /**
     * Each array element represents a single IP filter which can be either an IP address
     * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
     * The default value is `['127.0.0.1', '::1']`, which means the module can only be accessed
     * by localhost.
     *
     * @var array the list of IPs that are allowed to access this module.
     */
    public $allowedIPs = [ '127.0.0.1', '192.168.0.*', '::1' ];

    /**
     * The maximum amount of log items to get set to false to disable the limit
     * If the limit if false then it will fall back to the `$maxGetLogs` limit
     * to stop the module from running out of memory
     *
     * @var integer
     */
    public $logLimit = 10000;

    /**
     * The maximum amount of log items to get before sorting and filtering
     * This is the one to chage if you keep geting out of memory errors
     *
     * @var integer
     */
    public $maxGetLogs = 84000;

    /**
     * The amount of time in second to cache the logs
     *
     * @var integer
     */
    public $logCacheTime = 30;

    /**
     * The amount of time in second to cache the page html
     *
     * @var integer
     */
    public $pageCacheTime = 30;

    /**
     * The dir to look into to find the log files
     *
     * @var string
     */
    public $logDir = '@runtime/logs/';

    /**
     * If to display different colors for different levels in the table
     *
     * @var boolean
     */
    public $tableColors = true;

    /**
     * @inheritdoc
     */
    public function bootstrap( $app )
    {
        if ( $app instanceof \yii\web\Application ) {
            $app->getUrlManager()->addRules( [
                [ 'class' => 'yii\web\UrlRule', 'pattern' => $this->id, 'route' => $this->id . '/default/index' ],
                [ 'class' => 'yii\web\UrlRule', 'pattern' => $this->id . '/get-logs', 'route' => $this->id . '/default/get-logs' ]
            ], false );
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function beforeAction( $action )
    {
        if ( Yii::$app instanceof \yii\web\Application && !$this->checkAccess() ) {
            throw new ForbiddenHttpException( 'You are not allowed to access this page.' );
        }

        return true;
    }

    /**
     * @return int whether the module can be accessed by the current user
     */
    protected function checkAccess()
    {
        $ip = Yii::$app->getRequest()->getUserIP();
        foreach ( $this->allowedIPs as $filter ) {
            if ( $filter === '*' || $filter === $ip || ( ( $pos = strpos( $filter, '*' ) ) !== false && !strncmp( $ip, $filter, $pos ) ) ) {
                return true;
            }
        }
        Yii::warning( 'Access to log-viewer is denied due to IP address restriction. The requested IP is ' . $ip, __METHOD__ );
        return false;
    }
}
