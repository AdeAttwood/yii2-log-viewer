<?php

namespace adeattwood\logviewer\controllers;

use adeattwood\logviewer\filters\PageCache;
use adeattwood\logviewer\Module as LogViewerModule;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * The default controller handles all the request for the logs view
 *
 * @category  PHP
 * @package   adeattwood/yii2-log-viewer
 * @author    Ade Attwood <attwood16@googlemail.com>
 * @copyright 2017 adeattwood.co.uk
 * @license   BSD-2-Clause http://adeattwood.co.uk/license.html
 * @link      https://github.com/AdeAttwood/yii2-log-viewer
 * @since     v0.1
 */
class DefaultController extends \yii\web\Controller
{

    /**
     * The layout file for the views
     *
     * @var string
     */
    public $layout = 'main';

    /**
     * A array of logs to display in the logs view
     *
     * @var array
     */
    protected $logs = [];

    /**
     * Levels of errors in used to dynamically add to a dropdown for filtering
     *
     * @var array
     */
    protected $levels = [];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => [ 'GET' ],
                    'get-logs' => [ 'GET' ]
                ],
            ],
            'pageCache' => [
                'class' => PageCache::className(),
                'only' => [ 'index' ],
                'duration' => LogViewerModule::getInstance()->pageCacheTime,
                'enabled' => ( bool )LogViewerModule::getInstance()->pageCacheTime,
                'minify'  => true
            ]
        ];
    }

    /**
     * The main action renders the index view
     *
     * @return yii\web\Response
     */
    public function actionIndex()
    {
        return $this->render( 'index' );
    }

    

    /**
     * The ajax request action for getting and parsing the log files
     *
     * @return yii\web\Response
     */
    public function actionGetLogs()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->cache->delete( $this->cacheKey );
        $cache  = Yii::$app->cache->get( $this->cacheKey );

        if ( isset( $cache[ 'logs' ] ) && isset( $cache[ 'levels' ] ) ) {
            Yii::trace( 'Loading logs from cache', __METHOD__ );
            $this->logs   = $cache[ 'logs' ];
            $this->levels = $cache[ 'levels' ];
        } else {
            foreach ( Yii::$app->log->targets as $target ) {
                if ( !isset( $target->logFile ) ) {
                    continue;
                }
                $this->parseFile( $target->logFile );
            }

            ArrayHelper::multisort( $this->logs, 'time', SORT_DESC );

            $this->filterLogs();

            if ( LogViewerModule::getInstance()->logLimit ) {
                $this->logs = array_slice( $this->logs, 0, LogViewerModule::getInstance()->logLimit );
            }

            foreach ( $this->logs as $log ) {
                $this->levels[ $log[ 'level' ] ] = Inflector::humanize( $log[ 'level' ] );
            }

            $cache = [
                'logs' => $this->logs,
                'levels' => $this->levels
            ];

            if( LogViewerModule::getInstance()->logCacheTime ) {
                Yii::trace( 'Caching logs for ' . LogViewerModule::getInstance()->logCacheTime . ' seconds', __METHOD__ );
                Yii::$app->cache->set( $this->cacheKey, $cache, LogViewerModule::getInstance()->logCacheTime );
            }
        }

        return [
            'logs'  => $this->logs,
            'levels' => $this->levels
        ];

    }

    /**
     * Filters the logs biased on the get parameter's
     *
     * @return void
     */
    protected function filterLogs()
    {
        $get = Yii::$app->request->get();

        if ( !empty( $get ) ) {
            $this->logs = array_filter( $this->logs, function( $log ) use ( $get ) {
                if ( isset( $get[ 'level' ] ) && $log[ 'level' ] !== $get[ 'level' ] ) {
                    return false;
                }

                if ( isset( $get[ 'ip' ] ) && $log[ 'ip' ] !== $get[ 'ip' ] ) {
                    return false;
                }

                if ( isset( $get[ 'session' ] ) && $log[ 'session' ] !== $get[ 'session' ] ) {
                    return false;
                }

                if ( isset( $get[ 'category' ] ) && $log[ 'category' ] !== $get[ 'category' ] ) {
                    return false;
                }

                if ( isset( $get[ 'user' ] ) && $log[ 'user' ] !== $get[ 'user' ] ) {
                    return false;
                }

                if ( isset( $get[ 'from' ] ) ) {
                    $from = Yii::$app->formatter->asTimestamp( $get[ 'from' ] );
                    $date = Yii::$app->formatter->asTimestamp( $log[ 'time' ] / 1000 );
                    if ( (int)$from > (int)$date ) {
                        return false;
                    }
                    unset( $from );
                    unset( $date );
                }

                if ( isset( $get[ 'to' ] ) ) {
                    $to   = Yii::$app->formatter->asTimestamp( $get[ 'to' ] );
                    $date = Yii::$app->formatter->asTimestamp( $log[ 'time' ] / 1000 );
                    if ( (int)$to < (int)$date ) {
                        return false;
                    }
                    unset( $to );
                    unset( $date );
                }
                return true;
            } );
        }
    }

    /**
     * Parses a log file based on the default yii log format
     * this method populates the `$this->logs`
     *
     * @param string $file Path to the log file to parse
     * 
     * @return bool
     */
    protected function parseFile( $file )
    {
        $log = [];
        if ( $file = fopen( $file, "r" ) ) {
            while( !feof( $file ) ) {
                $line = fgets( $file );
                preg_match( "/\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]/", $line, $logInfo );
                $logTimeAndMessage = preg_split( "/\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]\[(.*)\]/", $line );

                if ( isset( $logInfo[ 1 ] )
                    && isset( $logInfo[ 2 ] )
                    && isset( $logInfo[ 3 ] )
                    && isset( $logInfo[ 4 ] )
                    && isset( $logInfo[ 5 ] )
                    && isset( $logTimeAndMessage[ 0 ] )
                    && isset( $logTimeAndMessage[ 1 ] )
                ) {
                    if ( !empty( $log ) && count( $this->logs ) < LogViewerModule::getInstance()->maxGetLogs ) {
                        $this->logs[] = $log;
                    }

                    $log = [
                        'time' => Yii::$app->formatter->asTimestamp( $logTimeAndMessage[ 0 ] ) * 1000,
                        'rawDate' => $logTimeAndMessage[ 0 ],
                        'message' => $logTimeAndMessage[ 1 ],
                        'ip' => $logInfo[ 1 ],
                        'user_id' => $logInfo[ 2 ],
                        'session_id' => $logInfo[ 3 ],
                        'level' => $logInfo[ 4 ],
                        'category' => $logInfo[ 5 ],
                        'vars' => ''
                    ];
                } else {
                    $log[ 'vars' ] .= utf8_encode( $line );
                }
            }
            fclose( $file );
            return true;
        }

        return false;
    }

    /**
     * Create a string baised in the url to be used for a key when caching
     *
     * @return string
     */
    protected function getCacheKey()
    {
        return 'LOG_' . Yii::$app->request->url;
    }
}
