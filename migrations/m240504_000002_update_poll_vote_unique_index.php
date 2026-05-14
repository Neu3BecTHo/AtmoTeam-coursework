<?php

use yii\db\Migration;


class m240504_000002_update_poll_vote_unique_index extends Migration
{
    public function safeUp()
    {

        $this->dropIndex('idx-poll_vote-unique', '{{%poll_vote}}');



        $this->createIndex('idx-poll_vote-unique', '{{%poll_vote}}', ['poll_id', 'poll_option_id', 'user_id'], true);
        
        echo "Индекс poll_vote обновлён для поддержки множественных голосов!";
    }

    public function safeDown()
    {

        $this->dropIndex('idx-poll_vote-unique', '{{%poll_vote}}');

        $this->createIndex('idx-poll_vote-unique', '{{%poll_vote}}', ['poll_id', 'user_id'], true);
        
        echo "Индекс poll_vote возвращён к исходному состоянию";
    }
}
