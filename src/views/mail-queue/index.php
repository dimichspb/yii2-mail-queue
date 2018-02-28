<?php

use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var $this \yii\web\View */
/** @var $dataProvider \yii\data\ActiveDataProvider */
/** @var $searchModel \dimichspb\yii\mailqueue\models\MailQueue\search\MailQueueSearch */

$this->title = \Yii::t('mail-queue', 'Mail Queue');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="blog-index">
    <div class="box">
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => ['class' => 'table-responsive'],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'id',
                    'created_at:datetime',
                    'send_at:datetime',
                    [
                        'attribute' => 'message',
                        'value' => function (MailQueue $model) {
                            return $model->getMessage()->getSubject();
                        },
                    ],
                    'attempts',
                    'statuses',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {delete}'
                    ],
                    'sent_at:datetime'
                ],
            ]); ?>
        </div>
    </div>
</div>

