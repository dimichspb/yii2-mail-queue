<?php
namespace dimichspb\yii\mailqueue\commands;

use dimichspb\yii\mailqueue\Mailer;
use dimichspb\yii\mailqueue\models\MailQueue\MailQueue;
use dimichspb\yii\mailqueue\Module;
use yii\console\Controller;

class MailQueueController extends Controller
{
    /**
     * @var Mailer
     */
    protected $mailer;

    public function __construct(string $id, Module $module, array $config = [])
    {
        $this->mailer = \Yii::$app->mailer;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex()
    {
        /** @var MailQueue $modelClass */
        $modelClass = $this->mailer->modelClass;
        $modelsCount = $modelClass::find()->count();

        $this->stdout($modelsCount);
    }
}