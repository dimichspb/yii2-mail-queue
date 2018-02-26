<?php
namespace dimichspb\yii\mailqueue\models\MailQueue;

use Assert\Assertion;

class Status
{
    const NEW = 'new';
    const PROCESS = 'process';
    const ERROR = 'error';
    const DONE = 'done';

    private $value;
    private $created_at;

    public function __construct($value, CreatedAt $createdAt = null)
    {
        Assertion::inArray($value, self::getAvailableStatuses());

        $this->value = $value;
        $this->created_at = $createdAt?: new CreatedAt();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function isEqualTo(Status $that)
    {
        return $this->getValue() === $that->getValue();
    }

    public static function getAvailableStatuses()
    {
        return [
            self::NEW,
            self::PROCESS,
            self::ERROR,
            self::DONE
        ];
    }

}