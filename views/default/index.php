<?php

/**
 * @category  PHP
 * @package   adeattwood/yii2-log-viewer
 * @author    Ade Attwood <attwood16@googlemail.com>
 * @copyright 2017 adeattwood.co.uk
 * @license   BSD-2-Clause http://adeattwood.co.uk/license.html
 * @link      https://github.com/AdeAttwood/yii2-log-viewer
 * @since     v0.1
 */

use adeattwood\logviewer\assets\Asset;
use adeattwood\logviewer\Module as LogViewerModule;
use dosamigos\datepicker\DateRangePicker;
use yii\bootstrap\Modal;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\helpers\Inflector;

Asset::register( $this );

$this->title = 'Logs - ' . Yii::$app->name;

$logViewerModule = LogViewerModule::getInstance();

$labels = [
  'time' => '',
  'message' => '',
  'ip' => '',
  'user_id' => '',
  'session_id' => '',
  'level' => '',
  'category' => ''
];

$dates = [
    'from' => Yii::$app->formatter->asDate( '-1 month' ),
    'to'   => Yii::$app->formatter->asDate( '+1 day' )
];

echo Html::beginTag( 'div', [ 'id' => 'logs-table' ] );

echo Html::beginTag( 'div', [ 'class' => 'loading', 'v-show' => 'loading' ] );
echo Html::tag( 'h2', 'Loading Log Items' );
echo Html::tag( 'i', null, [ 'class' => 'fa fa-spinner fa-pulse fa-3x fa-fw' ] );
echo Html::endTag( 'div' );

echo Html::beginTag( 'div', [ 'v-show' => '!loading' ] );

echo Html::tag( 'h1', 'Logs' );
echo Html::tag( 'h2', 'Displaying {{ sortedDataCount }} out of {{ logCount }} log items' );

Modal::begin( [
    'id' => 'details-modal',
    'header' => '<h2 class="modal-header-h2"></h2>',
    'size' => Modal::SIZE_LARGE,
] );

Modal::end();

echo DateRangePicker::widget( [
    'name' => 'date_from',
    'value' => $dates[ 'from' ],
    'nameTo' => 'date_to',
    'valueTo' => $dates[ 'to' ],
] );

echo Html::beginTag(
    'table',
    [
        'class' => $logViewerModule->tableColors ? 'table colored' : 'table table-striped'
    ]
);

echo Html::beginTag( 'thead' );

foreach ( $labels as $attribute => $label ) {
    echo Html::beginTag( 'th' );
    
    if ( $attribute == 'level' ) {
        echo <<<HTML
<select v-model="filters.$attribute" class="form-control">
  <option value="">Select Level</option>
  <option v-for="(key, value) in levels" v-bind:value="key">
    {{ value }}
  </option>
</select> 
HTML;
    } else {
        echo Html::input( 'text', $attribute . '_input', null, [
            'class' => 'form-control',
            'v-model' => 'filters.' . $attribute,
        ] );
    }

    echo Html::a( Inflector::humanize( $attribute ), '', [
        'v-on:click.prevent' => "sort('$attribute')",
    ] );

    echo Html::endTag( 'th' );
}

echo Html::tag( 'th', null );

echo Html::endTag( 'thead' );

echo Html::beginTag( 'tbody' );

echo Html::beginTag( 'tr', [
    'v-for' => '( item, index ) in sortedData',
    'v-bind:class' => "'tr-' + item.level"
] );

foreach ( array_keys( $labels ) as $column ) {
  if ( $column == 'time' ) {
      echo Html::tag( 'td', "{{item.$column | dateTime}}", [ 'class' => $column ] );
  } else {
      echo Html::tag( 'td', "{{item.$column}}", [ 'class' => $column ] );
  }
}

echo Html::tag(
    'td',
    Html::tag(
        'i',
        '',
        [
            'class' => 'fa fa-info-circle fa-2x hover-item',
            'title' => 'View Details',
            'v-show' => 'item.vars != ""',
            'v-on:click.prevent' => "showModel(index)",
        ]
    ),
    [
        'class' => 'details'
    ]
);

echo Html::endTag( 'tr' );

echo Html::endTag( 'tbody' );
echo Html::endTag( 'table' );
echo Html::endTag( 'div' );
echo Html::endTag( 'div' );

$this->registerJS(
    'window.jsonVueData = ' .
    Json::htmlEncode(
        [
            'getLogsRoute' => "/{$logViewerModule->id}/get-logs",
            'filters' => $labels
        ],
        true
    ),
    $this::POS_HEAD
);
