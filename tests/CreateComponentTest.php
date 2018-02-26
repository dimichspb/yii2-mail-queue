<?php
namespace dimichspb\yii\mailqueue\tests;

use dimichspb\yii\mailqueue\Mailer;
use yii\mail\MailerInterface;
use yii\queue\Queue;

class CreateComponentTest extends TestCase
{
    public function testCreateComponent()
    {
        $this->assertInstanceOf(Mailer::className(), $this->app->mailer);
        $this->assertInstanceOf(Queue::className(), $this->app->queue);
    }
}