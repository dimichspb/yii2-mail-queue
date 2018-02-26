<?php
namespace dimichspb\yii\mailqueue\tests;

use dimichspb\yii\mailqueue\jobs\CheckMailQueueJob;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use dimichspb\yii\mailqueue\models\MailQueue\SendAt;

class CheckMailQueueJobTest extends TestCase
{
    public function testExecute()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $message = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $modelClass = $this->app->mailer->modelClass;
        /** @var MailQueue $model */
        $model = new $modelClass($message);
        $model->save();

        $job = new CheckMailQueueJob();

        $models = $job->execute($this->app->queue);

        $this->assertEmpty($model->errors);
        $this->assertCount(1, $models);
        $this->assertEquals($model->getId(), (reset($models))->getId());
    }

    public function testFilters()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $oneHour = new \DateInterval('PT1H');
        $past = (new \DateTime())->sub($oneHour);
        $future = (new \DateTime())->add($oneHour);
        $now = (new \DateTime());

        $messagePast1 = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $messagePast2 = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $messageFuture1 = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $messageFuture2 = $this->app->mailer->compose('example')
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Test message subject')
            ->setTextBody('Test message plain body');

        $modelClass = $this->app->mailer->modelClass;
        /** @var MailQueue $model */
        $model = new $modelClass($messagePast1);
        $model->setSendAt(new SendAt($past->format('Y-m-d H:i:s')));
        $model->save();

        $model = new $modelClass($messagePast2);
        $model->setSendAt(new SendAt($past->format('Y-m-d H:i:s')));
        $model->save();

        $model = new $modelClass($messageFuture1);
        $model->setSendAt(new SendAt($future->format('Y-m-d H:i:s')));
        $model->save();

        $model = new $modelClass($messageFuture2);
        $model->setSendAt(new SendAt($future->format('Y-m-d H:i:s')));
        $model->save();

        $models = $this->app->mailer->getModels();

        $this->assertCount(2, $models);

        foreach ($models as $model) {
            $this->assertInstanceOf($modelClass, $model);
            $this->assertEquals($model->getSendAt()->getValue(), $past->format('Y-m-d H:i:s'));
            $this->assertTrue((new \DateTime($model->getSendAt()->getValue()))->getTimestamp() < $now->getTimestamp());
        }
    }

    public function testLimits()
    {
        $this->clearMailDirectory();
        $this->clearMailQueueTable();
        $this->clearQueue();

        $oneHour = new \DateInterval('PT1H');
        $past = (new \DateTime())->sub($oneHour);

        $modelClass = $this->app->mailer->modelClass;

        for ($i = 0; $i <= 15; $i++) {
            $message = $this->app->mailer->compose('example')
                ->setFrom('from@domain.com')
                ->setTo('to@domain.com')
                ->setSubject('Test message subject')
                ->setTextBody('Test message plain body');
            /** @var MailQueue $model */
            $model = new $modelClass($message);
            $model->setSendAt(new SendAt($past->format('Y-m-d H:i:s')));
            $model->save();
        }

        $models = $this->app->mailer->getModels();

        $this->assertCount($this->app->mailer->maxPerPeriod, $models);
    }
}