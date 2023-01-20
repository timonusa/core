<?php

class Message extends  Post
{
    const TABLE_NAME = 'core_message';

    public static function getQuestions() {
        $elseWhere = '';
        if (isset($_GET['time']) && $_GET['time'] != null ) {
            $elseWhere = ' AND m.publ_time >=' . (int)$_GET['time'];
        }
        $sql = static::getPDO()->prepare('SELECT m.content as text, m.publ_time as time, u.username, u.photo FROM ' . static::TABLE_NAME . ' m LEFT JOIN core_user u ON u.id=m.user_id WHERE is_question=1 ' . $elseWhere);
        $sql->execute();
        $messages = [];
        while ($message = $sql->fetch(PDO::FETCH_ASSOC) ) {
            $msg['photo'] = $message['photo'];
            $msg['username'] = $message['username'];
            $msg['text'] = $message['text'];
            $msg['time'] = date('H:i ', $message['time'] );
            $messages[] = $msg;
        }
        return $messages;
    }

}
