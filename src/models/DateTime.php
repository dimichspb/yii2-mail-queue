<?php
namespace dimichspb\yii\mailqueue\models;

use Assert\Assertion;

class DateTime
{
    protected $value;
    protected $format = 'Y-m-d H:i:s';

    public function __construct($datetime = null)
    {
        try {
            Assertion::nullOrNotEmpty($datetime);
        } catch (\Exception $exception) {
            var_dump($datetime);
        }
        $this->value = $datetime? \DateTimeImmutable::createFromFormat($this->format, $datetime): new \DateTimeImmutable();

    }

    public function getValue()
    {
        return $this->value->format($this->format);
    }

    /**
     * @param $interval
     * @return static
     * @throws \Exception
     */
    public function add($interval)
    {
        $this->value->add(new \DateInterval($interval));

        return $this;
    }

    public function isEqualTo(DateTime $that)
    {
        return $this->getValue() === $that->getValue();
    }
}