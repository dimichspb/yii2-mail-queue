<?php
namespace dimichspb\yii\mailqueue\tests;

use dimichspb\yii\mailqueue\jobs\CheckMailQueueJob;
use dimichspb\yii\mailqueue\jobs\DispatchMessageJob;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;

class DispatchMessageJobTest extends TestCase
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

        foreach ($models as $model) {
            $dispatchMessageJob = new DispatchMessageJob([
                'id' => $model->getId()->getValue()
            ]);
            $dispatchMessageJob->execute($this->app->queue);
        }

        $this->assertEquals(count($models), $this->getMailDirectoryFileCount());
    }
}