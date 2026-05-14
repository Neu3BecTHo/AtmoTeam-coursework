<?php

use yii\db\Migration;


class m240512_add_post_id_to_poll_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%poll}}', 'post_id', $this->integer()->null());

        $this->addForeignKey(
            'fk-poll-post_id',
            '{{%poll}}',
            'post_id',
            '{{%post}}',
            'id',
            'CASCADE'
        );

        $this->createIndex('idx-poll-post_id', '{{%poll}}', 'post_id');
    }

    public function safeDown()
    {

        $this->dropIndex('idx-poll-post_id', '{{%poll}}');

        $this->dropForeignKey('fk-poll-post_id', '{{%poll}}');

        $this->dropColumn('{{%poll}}', 'post_id');
    }
}
