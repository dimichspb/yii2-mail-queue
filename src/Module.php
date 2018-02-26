<?php
namespace dimichspb\yii\mailqueue;

class Module extends \yii\base\Module
{
    public function init()
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'dimichspb\yii\mailqueue\commands';
        }
    }
}