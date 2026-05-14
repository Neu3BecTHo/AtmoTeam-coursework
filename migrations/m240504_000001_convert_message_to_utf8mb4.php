<?php

use yii\db\Migration;


class m240504_000001_convert_message_to_utf8mb4 extends Migration
{
    public function safeUp()
    {

        $this->execute("ALTER TABLE {{%message}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->execute("ALTER TABLE {{%notification}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->execute("ALTER TABLE {{%activity_log}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        echo "Кодировка таблиц изменена на utf8mb4. Emoji теперь поддерживаются!";
    }

    public function safeDown()
    {

        $this->execute("ALTER TABLE {{%message}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
        $this->execute("ALTER TABLE {{%notification}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
        $this->execute("ALTER TABLE {{%activity_log}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
        
        echo "Кодировка таблиц возвращена на utf8";
    }
}
