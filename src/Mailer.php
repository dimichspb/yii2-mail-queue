<?php
namespace dimichspb\yii\mailqueue;

use dimichspb\yii\mailqueue\events\AfterDispatchMessageEvent;
use dimichspb\yii\mailqueue\events\AfterPutMailQueueEvent;
use dimichspb\yii\mailqueue\events\BeforeDispatchMessageEvent;
use dimichspb\yii\mailqueue\events\BeforePutMailQueueEvent;
use dimichspb\yii\mailqueue\events\CreatedEvent;
use dimichspb\yii\mailqueue\events\InitializedEvent;
use dimichspb\yii\mailqueue\exceptions\ModelNotFoundException;
use dimichspb\yii\mailqueue\exceptions\QueueNotConfigured;
use dimichspb\yii\mailqueue\jobs\CheckMailQueueJob;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use dimichspb\yii\mailqueue\models\MailQueue\SendAt;
use ReflectionObject;
use ReflectionProperty;
use yii\base\UnknownPropertyException;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;
use yii\queue\Queue;
use yii\swiftmailer\Mailer as SwiftMailer;

class Mailer extends BaseMailer
{
    use EventTrait;

    /**
     * @event MailEvent an event raised right before send.
     * You may set [[MailEvent::isValid]] to be false to cancel the send.
     */
    const EVENT_BEFORE_SEND_LATER = 'beforeSendLater';
    /**
     * @event MailEvent an event raised right after send.
     */
    const EVENT_AFTER_SEND_LATER = 'afterSendLater';

    /**
     * @event MailEvent an event raised right before send.
     * You may set [[MailEvent::isValid]] to be false to cancel the send.
     */
    const EVENT_BEFORE_SEND_AT = 'beforeSendAt';
    /**
     * @event MailEvent an event raised right after send.
     */
    const EVENT_AFTER_SEND_AT = 'afterSendAt';
    /**
     * @var  BaseMailer
     */
    private $mailer;

    /**
     * @var Queue
     */
    private $queue;

    public $mailerClass = SwiftMailer::class;

    public $mailerOptions = [];
    /**
     * @var string table name for the model class MailQueue
     */
    public $table = '{{%mail_queue}}';

    /**
     * @var string model default class name
     */
    public $modelClass = MailQueue::class;

    public $messageClass = 'yii\swiftmailer\Message';

    /**
     * @var MailQueue
     */
    private $model;
    /**
     * @var integer maximum attempts to send an mail message
     */
    public $maxAttempts = 5;

    /**
     * @var integer[]|string[] seconds or interval specifications to delay between attempts to send a mail message, see http://php.net/manual/en/dateinterval.construct.php
     */
    public $attemptIntervals = [0, 'PT1M', 'PT10M', 'PT1H', 'PT6H'];

    public $defaultAttemptInterval = 'PT1M';

    public $ttr = 5 * 60;

    /**
     * @var integer number of mail messages which could be sent per `periodSeconds`
     */
    public $maxPerPeriod = 10;

    /**
     * @var float period in seconds which indicate the time interval for `maxPerPeriod` option
     */
    public $periodSeconds = 1;

    /**
     * Mailer constructor.
     * @param array $config
     * @throws QueueNotConfigured
     */
    public function __construct(array $config = [])
    {
        try {
            $this->queue = \Yii::$app->queue;
        } catch (UnknownPropertyException $exception) {
            throw new QueueNotConfigured('Queue is not configured. Please fix the settings.', 0, $exception);
        }

        parent::__construct($config);

        $this->recordEvent(new CreatedEvent());
    }

    private function getPublicProperties()
    {
        $reflection = new ReflectionObject($this);

        return $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    public function init()
    {
        $this->mailer = new $this->mailerClass($this->mailerOptions);

        parent::init();

        $this->recordEvent(new InitializedEvent());
    }

    /**
     * @param MessageInterface $message
     * @return bool|void
     * @throws \Exception
     */
    public function sendMessage($message)
    {
        $this->sendMessageExtended($message);
    }

    /**
     * @param $message
     * @param \DateTime $dateTime
     * @throws \Exception
     */
    public function sendMessageAt($message, \DateTime $dateTime)
    {
        $this->sendMessageExtended($message, $dateTime);
    }

    /**
     * @param $message
     * @param \DateInterval $interval
     * @throws \Exception
     */
    public function sendMessageLater($message, \DateInterval $interval)
    {
        $this->sendMessageExtended($message, null, $interval);
    }

    /**
     * @param $message
     * @param \DateTime|null $dateTime
     * @param \DateInterval|null $interval
     * @return bool
     * @throws \Exception
     */
    protected function sendMessageExtended($message, \DateTime $dateTime = null, \DateInterval $interval = null)
    {
        $this->recordEvent(new BeforePutMailQueueEvent());

        $dateTime = $dateTime?: new \DateTime();
        $interval = $interval?: new \DateInterval('PT00S');

        $dateTime->add($interval);

        $modelClass = $this->modelClass;
        /* @var $model MailQueue */
        $this->model = new $modelClass($message);
        $this->model->setSendAt(new SendAt($dateTime->format('Y-m-d H:i:s')));

        $result = $this->model->save();

        $this->pushJob();

        $this->recordEvent(new AfterPutMailQueueEvent());

        return $result;
    }

    /**
     * @param MessageInterface $message
     * @param \DateInterval $interval
     * @return bool
     * @throws \Exception
     */
    public function sendLater($message, \DateInterval $interval)
    {
        if (!$this->beforeSendLater($message)) {
            return false;
        }

        $address = $message->getTo();
        if (is_array($address)) {
            $address = implode(', ', array_keys($address));
        }
        \Yii::info('Sending email later "' . $message->getSubject() . '" to "' . $address . '" after ' . $interval->format('YYYY-mm-dd H:i:s'), __METHOD__);

        if ($this->useFileTransport) {
            $isSuccessful = $this->saveMessage($message);
        } else {
            $isSuccessful = $this->sendMessageLater($message, $interval);
        }
        $this->afterSendLater($message, $isSuccessful);

        return $isSuccessful;
    }

    /**
     * @param array $messages
     * @param \DateInterval $interval
     * @return int
     * @throws \Exception
     */
    public function sendMultipleLater(array $messages, \DateInterval $interval)
    {
        $successCount = 0;
        foreach ($messages as $message) {
            if ($this->sendLater($message, $interval)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * @param MessageInterface $message
     * @param \DateTime $dateTime
     * @return bool
     * @throws \Exception
     */
    public function sendAt($message, \DateTime $dateTime)
    {
        if (!$this->beforeSendAt($message)) {
            return false;
        }

        $address = $message->getTo();
        if (is_array($address)) {
            $address = implode(', ', array_keys($address));
        }
        \Yii::info('Sending email later "' . $message->getSubject() . '" to "' . $address . '" at ' . $dateTime->format('YYYY-mm-dd H:i:s'), __METHOD__);

        if ($this->useFileTransport) {
            $isSuccessful = $this->saveMessage($message);
        } else {
            $isSuccessful = $this->sendMessageAt($message, $dateTime);
        }
        $this->afterSendAt($message, $isSuccessful);

        return $isSuccessful;
    }

    /**
     * @param array $messages
     * @param \DateTime $dateTime
     * @return int
     * @throws \Exception
     */
    public function sendMultipleAt(array $messages, \DateTime $dateTime)
    {
        $successCount = 0;
        foreach ($messages as $message) {
            if ($this->sendLater($message, $dateTime)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * @param MessageInterface $message
     * @return bool
     * @throws \Exception
     */
    public function send($message)
    {
        if (!$this->beforeSend($message)) {
            return false;
        }

        $address = $message->getTo();
        if (is_array($address)) {
            $address = implode(', ', array_keys($address));
        }
        \Yii::info('Sending email "' . $message->getSubject() . '" to "' . $address . '"', __METHOD__);

        if ($this->useFileTransport) {
            $isSuccessful = $this->saveMessage($message);
        } else {
            $isSuccessful = $this->sendMessage($message);
        }
        $this->afterSend($message, $isSuccessful);

        return $isSuccessful;
    }

    public function dispatch(MessageInterface $message)
    {
        $this->recordEvent(new BeforeDispatchMessageEvent());
        $result = $this->mailer->send($message);
        $this->recordEvent(new AfterDispatchMessageEvent());

        return $result;
    }

    /**
     * @return MailQueue[]
     * @throws \Exception
     */
    public function getModels()
    {
        /* @var $modelClass MailQueue */
        $modelClass = $this->modelClass;

        $now = new \DateTime();
        $period = new \DateInterval('PT' . $this->periodSeconds . 'S');
        $now->sub($period);

        $alreadySentCount = $modelClass::find()
            ->select('id')
            ->where(['>=', 'send_at', $now->format('Y-m-d H:i:s')])
            ->orderBy(['created_at' => SORT_ASC])
            ->count();

        $limit = $this->maxPerPeriod - $alreadySentCount;

        return $modelClass::find()
            ->where(['<=', 'send_at', (new SendAt())->getValue()])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    public function getModel()
    {
        return $this->model;
    }

    public function findModel($id)
    {
        /* @var $modelClass MailQueue */
        $modelClass = $this->modelClass;
        if (!$model = $modelClass::findOne(['id' => $id])) {
            throw new ModelNotFoundException($this->modelClass . ' with ID ' . $id . ' not found!');
        }
    }

    public function pushJob()
    {
        return $this->queue->push(new CheckMailQueueJob());
    }

    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * @return BaseMailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @param $attempt
     * @return \DateInterval
     * @throws \Exception
     */
    public function getNextAttemptInterval($attempt)
    {
        $interval = isset($this->attemptIntervals[$attempt])? $this->attemptIntervals[$attempt]: $this->defaultAttemptInterval;

        return new \DateInterval($interval);
    }

    public function getTtr()
    {
        return $this->ttr;
    }

    /**
     * This method is invoked right before mail send.
     * You may override this method to do last-minute preparation for the message.
     * If you override this method, please make sure you call the parent implementation first.
     * @param MessageInterface $message
     * @return bool whether to continue sending an email.
     */
    public function beforeSendLater($message)
    {
        $event = new MailEvent(['message' => $message]);
        $this->trigger(self::EVENT_BEFORE_SEND_LATER, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked right after mail was send.
     * You may override this method to do some postprocessing or logging based on mail send status.
     * If you override this method, please make sure you call the parent implementation first.
     * @param MessageInterface $message
     * @param bool $isSuccessful
     */
    public function afterSendLater($message, $isSuccessful)
    {
        $event = new MailEvent(['message' => $message, 'isSuccessful' => $isSuccessful]);
        $this->trigger(self::EVENT_AFTER_SEND_LATER, $event);
    }

    /**
     * This method is invoked right before mail send.
     * You may override this method to do last-minute preparation for the message.
     * If you override this method, please make sure you call the parent implementation first.
     * @param MessageInterface $message
     * @return bool whether to continue sending an email.
     */
    public function beforeSendAt($message)
    {
        $event = new MailEvent(['message' => $message]);
        $this->trigger(self::EVENT_BEFORE_SEND_AT, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked right after mail was send.
     * You may override this method to do some postprocessing or logging based on mail send status.
     * If you override this method, please make sure you call the parent implementation first.
     * @param MessageInterface $message
     * @param bool $isSuccessful
     */
    public function afterSendAt($message, $isSuccessful)
    {
        $event = new MailEvent(['message' => $message, 'isSuccessful' => $isSuccessful]);
        $this->trigger(self::EVENT_AFTER_SEND_AT, $event);
    }
}