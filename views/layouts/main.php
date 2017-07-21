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

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */


?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <title><?= $this->title ?></title>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>"/>
        <meta name="author" content="Ade Attwood">
        <?= Html::csrfMetaTags() ?>
        <?php $this->head() ?>
    </head>
    <body>
        <?php $this->beginBody() ?>
        <div class="container-fluid">
            <?= $content ?>
        </div>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
