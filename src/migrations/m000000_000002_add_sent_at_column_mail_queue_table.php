<?php

use yii\db\Migration;

/**
 * Handles the creation of table `mail_queue`.
 */
class m000000_000002_add_sent_at_column_mail_queue_table extends Migration
{
    private $tableName = '{{%mail_queue%}}';
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->addColumn($this->tableName, 'sent_at', $this->dateTime());
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropColumn($this->tableName, 'sent_at');
    }
}
