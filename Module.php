<?php

namespace adeattwood\logviewer;

use Yii;
use yii\web\ForbiddenHttpException;

/**
 * LogViewer module definition class
 */
class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
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
     *
     * @var integer
     */
    public $logLimit = 1000;

    /**
     * The amount of time in second to cache the logs
     *
     * @var integer
     */
    public $logCacheTime = false;

    /**
     * The amount of time in second to cache the page html
     *
     * @var integer
     */
    public $pageCacheTime = false;

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
