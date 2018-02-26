<?php
namespace dimichspb\yii\mailqueue\tests;

use dimichspb\yii\mailqueue\models\DateTime;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;

class MailerSendTest extends TestCase
{
    /**
     * @after CorrectMailerConfigurationTest
     */
    public function testSend()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $message = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $dateTime = new DateTime();
        $this->app->mailer->send($message);

        /** @var MailQueue $modelClass */
        $modelClass = $this->app->mailer->modelClass;
        /** @var MailQueue $model */
        $model = $modelClass::find()->one();
        $this->assertTrue($this->isMailDirectoryClear());
        $this->assertInstanceOf($this->app->mailer->modelClass, $model);
        $this->assertEquals($dateTime->getValue(), $model->getSendAt()->getValue());
        $this->assertFalse($this->isQueueClear());
    }

    /**
     * @after CorrectMailerConfigurationTest
     */
    public function testSendAt()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $message = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $dateTime = new \DateTime('2020-01-01 00:00:00');
        $this->app->mailer->sendAt($message, $dateTime);

        /** @var MailQueue $modelClass */
        $modelClass = $this->app->mailer->modelClass;
        /** @var MailQueue $model */
        $model = $modelClass::find()->one();

        $this->assertTrue($this->isMailDirectoryClear());
        $this->assertInstanceOf($this->app->mailer->modelClass, $model);
        $this->assertEquals($dateTime->format('Y-m-d H:i:s'), $model->getSendAt()->getValue());
        $this->assertFalse($this->isQueueClear());
    }

    /**
     * @after CorrectMailerConfigurationTest
     * @throws \Exception
     */
    public function testSendLater()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $message = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $interval = new \DateInterval('PT1H');
        $dateTime = new \DateTime();
        $this->app->mailer->sendLater($message, $interval);

        /** @var MailQueue $modelClass */
        $modelClass = $this->app->mailer->modelClass;
        /** @var MailQueue $model */
        $model = $modelClass::find()->one();

        $this->assertTrue($this->isMailDirectoryClear());
        $this->assertInstanceOf($this->app->mailer->modelClass, $model);
        $this->assertEquals($dateTime->add($interval)->format('Y-m-d H:i:s'), $model->getSendAt()->getValue());
        $this->assertFalse($this->isQueueClear());
    }
}