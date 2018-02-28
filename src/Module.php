<?php
namespace dimichspb\yii\mailqueue;

class Module extends \yii\base\Module
{
    public $prefix = 'mailqueue';

    public $routes = [
        'mailqueue/index' => 'mailqueue/mail-queue/index',
        'mailqueue/view' => 'mailqueue/mail-queue/view',
    ];

    public function init()
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'dimichspb\yii\mailqueue\commands';
        }
        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        \Yii::$app->i18n->translations['mail-queue*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => __DIR__ . '/messages',
            'fileMap' => [
                'mail-queue' => 'messages.php',
            ],
        ];
    }
}