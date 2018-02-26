<?php
namespace dimichspb\yii\mailqueue\jobs;

use dimichspb\yii\mailqueue\EventTrait;
use dimichspb\yii\mailqueue\exceptions\SendMailIsNotSuccessful;
use dimichspb\yii\mailqueue\jobs\events\AfterExecuteDispatchMessageJobEvent;
use dimichspb\yii\mailqueue\jobs\events\BeforeExecuteDispatchMessageJobEvent;
use dimichspb\yii\mailqueue\jobs\events\CreatedDispatchMessageJobEvent;
use dimichspb\yii\mailqueue\jobs\events\InitializedDispatchMessageJobEvent;
use dimichspb\yii\mailqueue\Mailer;
use dimichspb\yii\mailqueue\models\MailQueue\Attempt;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use yii\base\BaseObject;
use yii\mail\MailerInterface;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

class DispatchMessageJob extends BaseObject implements RetryableJobInterface
{
    use EventTrait;

    public $id;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var MailQueue
     */
    private $model;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->recordEvent(new CreatedDispatchMessageJobEvent());
    }

    public function init()
    {
        $this->mailer = \Yii::$app->mailer;
        $this->model = $this->mailer->findModel($this->id);

        parent::init();
        $this->recordEvent(new InitializedDispatchMessageJobEvent());
    }

    public function execute($queue)
    {
        $this->recordEvent(new BeforeExecuteDispatchMessageJobEvent());

        $this->model->updateLastAttempt(Attempt::NEW);

        $message = $this->model->getMessage();
        try {
            $this->model->updateLastAttempt(Attempt::PROCESS);
            $result = $this->mailer->send($message);
            if (!$result) {
                throw new SendMailIsNotSuccessful('Message has not been sent');
            }
        } catch (\Exception $exception) {
            $this->model->updateLastAttempt(Attempt::ERROR);
        }
        $this->model->updateLastAttempt(Attempt::DONE);

        $this->recordEvent(new AfterExecuteDispatchMessageJobEvent());
    }

    public function getTtr()
    {
        return $this->mailer->getTtr();
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < $this->mailer->getMaxAttempts();
    }

}