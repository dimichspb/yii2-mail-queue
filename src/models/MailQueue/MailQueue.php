<?php
namespace dimichspb\yii\mailqueue\models\MailQueue;

use dimichspb\yii\mailqueue\EventTrait;
use dimichspb\yii\mailqueue\models\InstantiateTrait;
use dimichspb\yii\mailqueue\models\MailQueue\events\AttemptAddedEvent;
use dimichspb\yii\mailqueue\models\MailQueue\events\CreatedEvent;
use dimichspb\yii\mailqueue\models\MailQueue\events\MessageUpdatedEvent;
use dimichspb\yii\mailqueue\models\MailQueue\events\SendAtUpdatedEvent;
use dimichspb\yii\mailqueue\models\MailQueue\events\SentAtUpdatedEvent;
use dimichspb\yii\mailqueue\models\MailQueue\events\StatusAddedEvent;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\mail\MessageInterface;


/**
 * This is the model class for table "{{%mail_queue}}".
 *
 * @property Id $id
 * @property Attempt[] $attempts
 * @property string $message_data
 * @property CreatedAt $created_at
 * @property SendAt $send_at
 * @property SentAt $sent_at
 * @property MessageInterface $message
 * @property Status[] $statuses
 */
class MailQueue extends ActiveRecord
{
    use EventTrait, InstantiateTrait;

    private $id;
    private $created_at;
    private $send_at;
    private $sent_at;
    private $attempts = [];
    private $message_data;
    private $statuses = [];

    /**
     * MailQueue constructor.
     * @param MessageInterface $message
     * @param SendAt $sendAt
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(MessageInterface $message, SendAt $sendAt = null)
    {
        $this->id = new Id();
        $this->created_at = new CreatedAt();
        $this->send_at = $sendAt?: new SendAt();
        $this->sent_at = null;
        $this->setMessage($message);
        $this->addStatus(new Status(Status::NEW));

        $this->recordEvent(new CreatedEvent());

        parent::__construct();
    }

    /**
     * @param SendAt $sendAt
     */
    public function setSendAt(SendAt $sendAt)
    {
        $this->send_at = $sendAt;
        $this->recordEvent(new SendAtUpdatedEvent());
    }

    public function getSendAt()
    {
        return $this->send_at;
    }

    /**
     * @param SentAt $sentAt
     */
    public function setSentAt(SentAt $sentAt)
    {
        $this->sent_at = $sentAt;
        $this->recordEvent(new SentAtUpdatedEvent());
    }

    public function getSentAt()
    {
        return $this->sent_at;
    }

    /**
     * @param \DateInterval $interval
     * @throws \Exception
     */
    public function applyDelay(\DateInterval $interval)
    {
        $this->setSendAt((new SendAt())->add($interval));
    }


    /**
     * @return MessageInterface
     */
    public function getMessage()
    {
        return unserialize($this->message_data);
    }

    public function setMessage(MessageInterface $message)
    {
        $this->message_data = serialize($message);
        $this->recordEvent(new MessageUpdatedEvent());
    }

    public function addStatus(Status $status)
    {
        $this->statuses[] = $status;
        $this->save();
        $this->recordEvent(new StatusAddedEvent());
    }

    public function getLastStatus()
    {
        return end($this->statuses);
    }

    public function addAttempt(Attempt $attempt)
    {
        $this->attempts[] = $attempt;
        $this->save();
        $this->recordEvent(new AttemptAddedEvent());
    }

    public function getLastAttempt()
    {
        return end($this->attempts);
    }

    public function updateLastAttempt($value)
    {
        $attempt = $this->getLastAttempt();
        if (!$attempt) {
            $attempt = new Attempt($value);
            $this->addAttempt($attempt);
        } else {
            $attempt->setValue($value);
            $this->save();
        }
        switch ($value) {
            case Attempt::ERROR:
                $this->addStatus(new Status(Status::ERROR));
                break;
            case Attempt::DONE:
                $this->setSentAt(new SentAt());
                $this->addStatus(new Status(Status::DONE));
                break;
            default:
                $this->addStatus(new Status(Status::PROCESS));
        }
    }

    public function setId(Id $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        $mailer = \Yii::$app->mailer;

        return ($mailer && isset($mailer->tableName))? $mailer->tableName: '{{%mail_queue%}}';
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function afterFind()
    {
        $this->id = new Id(
            $this->getAttribute('id')
        );
        $this->created_at = new CreatedAt(
            $this->getAttribute('created_at')
        );
        $this->send_at = new SendAt(
            $this->getAttribute('send_at')
        );
        $this->sent_at = new SentAt(
            $this->getAttribute('sent_at')
        );

        $this->message_data = $this->getAttribute('message_data');

        $this->attempts = array_map(function ($row) {
            return new Attempt(
                $row['value'],
                new CreatedAt($row['created_at'])
            );
        }, Json::decode($this->getAttribute('attempts')));

        $this->statuses = array_map(function ($row) {
            return new Status(
                $row['value'],
                new CreatedAt($row['created_at'])
            );
        }, Json::decode($this->getAttribute('statuses')));

        parent::afterFind();
    }

    public function beforeSave($insert)
    {
        $this->setAttribute('id', $this->id->getValue());
        $this->setAttribute('created_at', $this->created_at->getValue());
        $this->setAttribute('send_at', $this->send_at->getValue());
        $this->setAttribute('sent_at', $this->sent_at? $this->sent_at->getValue(): null);
        $this->setAttribute('message_data', $this->message_data);
        $this->setAttribute('attempts', Json::encode(array_map(function (Attempt $attempt) {
            return [
                'value' => $attempt->getValue(),
                'created_at' => $attempt->getCreatedAt()->getValue(),
            ];
        }, $this->attempts)));

        $this->setAttribute('statuses', Json::encode(array_map(function (Status $status) {
            return [
                'value' => $status->getValue(),
                'created_at' => $status->getCreatedAt()->getValue(),
            ];
        }, $this->statuses)));

        return parent::beforeSave($insert);
    }
}