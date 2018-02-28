<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model \dimichspb\yii\mailqueue\models\MailQueue\MailQueue */

$this->title = \Yii::t('mail-queue', 'Mail Queue') . ' - ' . $model->getId()->getValue();
$this->params['breadcrumbs'][] = ['label' => \Yii::t('mail-queue', 'Mail Queue'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="event-view">
    <div class="box">
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'created_at:datetime',
                    'send_at:datetime',
                    [
                        'attribute' => 'message',
                        'value' => $model->getMessage()->toString(),
                    ],
                    'attempts',
                    'statuses',
                    'sent_at:datetime'
                ],
            ]) ?>
        </div>
    </div>
</div>
