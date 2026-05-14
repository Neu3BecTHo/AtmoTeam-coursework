<?php

use yii\db\Migration;


class m240513_000010_add_is_blocked_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'is_blocked', $this->boolean()->defaultValue(false)->after('status'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'is_blocked');
    }
}
