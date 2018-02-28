<?php
namespace dimichspb\yii\mailqueue;

use yii\base\InvalidConfigException;
use yii\web\Application as WebApplication;
use yii\console\Application as ConsoleApplication;

class Bootstrap implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof WebApplication) {
            $this->initUrlRoutes($app);
        }
        if ($app instanceof ConsoleApplication) {
           $app->controllerMap['mailqueue'] = 'dimichspb\yii\mailqueue\controllers\MailQueueController';
        }
    }

    /**
     * Initializes web url routes (rules in Yii2).
     *
     * @param WebApplication $app
     *
     * @throws InvalidConfigException
     */
    protected function initUrlRoutes(WebApplication $app)
    {
        /** @var $module Module */
        $module = $app->getModule('mailqueue');
        $config = [
            'class' => 'yii\web\GroupUrlRule',
            'prefix' => $module->prefix,
            'rules' => $module->routes,
        ];

        if ($module->prefix !== 'mailqueue') {
            $config['routePrefix'] = 'mailqueue';
        }

        $rule = \Yii::createObject($config);
        $app->getUrlManager()->addRules([$rule], false);
    }
}