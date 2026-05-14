<?php

use yii\db\Migration;


class m240513_000009_add_multiple_votes_to_poll extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%poll}}', 'multiple_votes', $this->boolean()->defaultValue(false)->after('question'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%poll}}', 'multiple_votes');
    }
}
