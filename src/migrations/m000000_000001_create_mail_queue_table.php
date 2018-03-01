<?php

use yii\db\Migration;

/**
 * Handles the creation of table `mail_queue`.
 */
class m000000_000001_create_mail_queue_table extends Migration
{
    private $tableName = '{{%mail_queue%}}';
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable($this->tableName, [
            'id' => 'char(36)',
            'created_at' => $this->dateTime(),
            'send_at' => $this->dateTime(),
            'attempts' => $this->text(),
            'message_data' => $this->binary(),
            'statuses' => $this->text(),
        ]);
        if ($this->db->driverName !== 'sqlite') {
            $this->addPrimaryKey('pk_mail_queue', $this->tableName, 'id');
        }
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable($this->tableName);
    }
}
