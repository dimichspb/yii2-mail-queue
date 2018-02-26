<?php
namespace dimichspb\yii\mailqueue\jobs;

use dimichspb\yii\mailqueue\EventTrait;
use dimichspb\yii\mailqueue\jobs\events\AfterExecuteCheckMailQueueJobEvent;
use dimichspb\yii\mailqueue\jobs\events\BeforeExecuteCheckMailQueueJobEvent;
use dimichspb\yii\mailqueue\jobs\events\CreatedCheckMailQueueJobEvent;
use dimichspb\yii\mailqueue\jobs\events\InitializedCheckMailQueueJobEvent;
use dimichspb\yii\mailqueue\Mailer;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use yii\base\BaseObject;
use yii\mail\MailerInterface;
use yii\queue\JobInterface;
use yii\queue\Queue;

class CheckMailQueueJob extends BaseObject implements JobInterface
{
    use EventTrait;

    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->recordEvent(new CreatedCheckMailQueueJobEvent());
    }


    public function init()
    {
        $this->mailer = \Yii::$app->mailer;

        parent::init();
        $this->recordEvent(new InitializedCheckMailQueueJobEvent());
    }

    public function execute($queue)
    {
        $this->recordEvent(new BeforeExecuteCheckMailQueueJobEvent());

        $models = $this->mailer->getModels();

        foreach ($models as $model) {
            $queue->push(new DispatchMessageJob([
                'id' => $model->getId()->getValue(),
            ]));
        }

        $this->recordEvent(new AfterExecuteCheckMailQueueJobEvent());

        return $models;
    }
}