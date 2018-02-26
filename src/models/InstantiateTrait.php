<?php
namespace dimichspb\yii\mailqueue\models;

trait InstantiateTrait
{
    private static $_instance;
    private static $_prototype;

    public static function instance($refresh = false)
    {
        if ($refresh || self::$_prototype === null) {
            self::$_instance = self::instantiate([]);
        }
        return self::$_instance;
    }

    public static function instantiate($row)
    {
        if (self::$_prototype === null) {
            $class = \get_called_class();
            self::$_prototype = unserialize(sprintf('O:%d:"%s":0:{}', \strlen($class), $class));
        }
        $entity = clone self::$_prototype;
        $entity->init();
        return $entity;
    }
}