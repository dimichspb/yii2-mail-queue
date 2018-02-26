<?php
namespace dimichspb\yii\mailqueue\tests;

use dimichspb\yii\mailqueue\Mailer;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use DirectoryIterator;
use yii\console\Application;
use yii\queue\Queue;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $app;

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
    }

    protected function mockApplication()
    {
        $this->app = new Application([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'runtimePath' => __DIR__ . '/runtime',
            'bootstrap' => [
                'dimichspb\yii\mailqueue\Bootstrap',
                'queue'
            ],
            'aliases' => [
                '@tests' => __DIR__,
            ],
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite:' . __DIR__ . '/runtime/db/db.sql',
                ],
                'mailer' => [
                    'class' => Mailer::className(),
                    'mailerOptions' => [
                        'useFileTransport' => true,
                    ],
                    'useFileTransport' => false,
                ],
                'queue' => [
                    'class' => \yii\queue\file\Queue::class,
                ]
            ]
        ]);
    }


    protected function clearMailDirectory()
    {
        foreach ($this->getMailDirectoryContent() as $fileInfo) {
            if(!$fileInfo->isDot()) {
                unlink($fileInfo->getPathname());
            }
        }
    }

    protected function clearMailQueueTable()
    {
        /** @var MailQueue $modelClass */
        $modelClass = $this->app->mailer->modelClass;

        $modelClass::deleteAll();
    }

    protected function clearQueue()
    {
        $this->app->queue->clear();
    }

    protected function isMailDirectoryClear()
    {
        foreach ($this->getMailDirectoryContent() as $fileInfo) {
            if (!$fileInfo->isDot()) {
                return false;
            }
        }
        return true;
    }

    protected function isQueueClear()
    {
        return
            $this->getWaitingCount() === 0  &&
            $this->getDelayedCount() === 0  &&
            $this->getReservedCount() === 0 &&
            $this->getDoneCount() === 0;
    }

    /**
     * @return int
     */
    protected function getWaitingCount()
    {
        $data = $this->getIndexData();
        return !empty($data['waiting']) ? count($data['waiting']) : 0;
    }

    /**
     * @return int
     */
    protected function getDelayedCount()
    {
        $data = $this->getIndexData();
        return !empty($data['delayed']) ? count($data['delayed']) : 0;
    }

    /**
     * @return int
     */
    protected function getReservedCount()
    {
        $data = $this->getIndexData();
        return !empty($data['reserved']) ? count($data['reserved']) : 0;
    }

    /**
     * @return int
     */
    protected function getDoneCount()
    {
        $data = $this->getIndexData();
        $total = isset($data['lastId']) ? $data['lastId'] : 0;
        return $total - $this->getDelayedCount() - $this->getWaitingCount();
    }

    protected function getIndexData()
    {
        static $data;
        if ($data === null) {
            $fileName = $this->app->queue->path . '/index.data';
            if (file_exists($fileName)) {
                $data = call_user_func($this->app->queue->indexDeserializer, file_get_contents($fileName));
            } else {
                $data = [];
            }
        }

        return $data;
    }

    protected function getMailDirectoryContent()
    {
        return new DirectoryIterator(\Yii::getAlias($this->app->mailer->fileTransportPath));
    }
}