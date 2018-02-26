<?php
namespace dimichspb\yii\mailqueue\tests;

use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;

class MailQueueModelTest extends TestCase
{
    /**
     * @throws \Assert\AssertionFailedException
     */
    public function testMessageSet()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $message = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $model = new MailQueue($message);

        $foundMessage = $model->getMessage();

        $this->assertInstanceOf(MailQueue::className(), $model);
        $this->assertEquals($message, $foundMessage);
    }
}