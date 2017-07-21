<?php

namespace adeattwood\logviewer\filters;

use Yii;
use yii\di\Instance;
use yii\web\Response;

/**
 * Handles the page cache and will minify the html before caching
 *
 * @category  PHP
 * @package   adeattwood/yii2-log-viewer
 * @author    Ade Attwood <attwood16@googlemail.com>
 * @copyright 2017 adeattwood.co.uk
 * @license   BSD-2-Clause http://adeattwood.co.uk/license.html
 * @link      https://github.com/AdeAttwood/yii2-log-viewer
 * @since     v0.1
 */
class PageCache extends \yii\filters\PageCache
{
    /**
     * If you want to minify the response before caching
     *
     * @var boolean
     */
    public $minify = true;

    /**
     * @inheritdoc
     */
    public function cacheResponse()
    {
        array_pop( $this->view->cacheStack );
        $beforeCacheResponseResult = $this->beforeCacheResponse();
        if ( $beforeCacheResponseResult === false ) {
            $content = ob_get_clean();
            if ( empty( $this->view->cacheStack ) && !empty( $this->dynamicPlaceholders ) ) {
                $content = $this->updateDynamicContent( $content, $this->dynamicPlaceholders );
            }
            echo $content;
            return;
        }
        $response = Yii::$app->getResponse();
        $data = [
            'cacheVersion' => 1,
            'cacheData' => is_array( $beforeCacheResponseResult ) ? $beforeCacheResponseResult : null,
        ];

        if ( $this->minify ) {
            $data[ 'content' ] = $this->minifyHtml( ob_get_clean() );
        } else {
            $data[ 'content' ] = ob_get_clean();
        }

        if ( $data['content'] === false || $data['content'] === '' ) {
            return;
        }
        $data[ 'dynamicPlaceholders' ] = $this->dynamicPlaceholders;
        foreach ( [ 'format', 'version', 'statusCode', 'statusText' ] as $name ) {
            $data[ $name ] = $response->{$name};
        }

        $this->cache->set( $this->calculateCacheKey(), $data, $this->duration, $this->dependency );
        
        if ( empty( $this->view->cacheStack ) && !empty( $this->dynamicPlaceholders ) ) {
            $data[ 'content' ] = $this->updateDynamicContent( $data[ 'content' ], $this->dynamicPlaceholders );
        }
        echo $data[ 'content' ];
    }

    /**
     * Will minify html
     *
     * @param string $input The html to minify
     *
     * @return string
     */
    protected function minifyHtml( $input )
    {
        if( trim( $input ) === "" ) {
            return $input;
        }
        // Remove extra white-space(s) between HTML attribute(s)
        $input = preg_replace_callback( '#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function( $matches ) {
            return '<' . $matches[1] . preg_replace( '#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2] ) . $matches[3] . '>';
        }, str_replace( "\r", "", $input ) );
        // Minify inline CSS declaration(s)
        if( strpos( $input, ' style=' ) !== false ) {
            $input = preg_replace_callback( '#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function( $matches ) {
                return '<' . $matches[ 1 ] . ' style=' . $matches[ 2 ] . $this->minifyCss( $matches[ 3 ] ) . $matches[ 2 ];
            }, $input );
        }
        if( strpos( $input, '</style>' ) !== false ) {
            $input = preg_replace_callback( '#<style(.*?)>(.*?)</style>#is', function( $matches ) {
                return '<style' . $matches[ 1 ] .'>'. $this->minifyCss( $matches[ 2 ] ) . '</style>';
            }, $input );
        }
        if( strpos( $input, '</script>' ) !== false ) {
            $input = preg_replace_callback( '#<script(.*?)>(.*?)</script>#is', function( $matches ) {
                return '<script' . $matches[ 1 ] .'>'. $this->minifyJs( $matches[ 2 ] ) . '</script>';
            }, $input );
        }
        return preg_replace(
            array(
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
            ),
            array(
                '<$1$2</$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                ""
            ),
        $input );
    }

    /**
     * Will minify inline css
     *
     * @param string $input The css to minify
     *
     * @return string
     */
    protected function minifyCss( $input ) {
        if( trim( $input ) === "" ) {
            return $input;
        }
        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
            ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2'
            ),
        $input);
    }

    /**
     * Will minify inline javascript
     *
     * @param string $input The javascript to minify
     *
     * @return string
     */
    protected function minifyJs( $input ) {
        if( trim( $input ) === "" ) {
            return $input;
        }
        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
        $input );
    }
}
