<?php
namespace dimichspb\yii\mailqueue;

use dimichspb\yii\mailqueue\repositories\MailQueueActiveRecordRepository;
use dimichspb\yii\mailqueue\repositories\MailQueueRepositoryInterface;

class Bootstrap implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
           // $app->controllerMap['mailqueue'] = 'dimichspb\yii\mailqueue\controllers\MailQueueController';
        }
    }
}