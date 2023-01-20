<?php

class User extends Post
{
    const TABLE_NAME = 'core_user';
    const UPLOAD_DIR = 'users';

    public static function getAllUsersId(int $id = null) : array
    {
        $elseWhere = '';
        if (isset($id)) {
            $elseWhere = ' WHERE id !=' . $id;
        }
        $sql = static::getPDO()->prepare('SELECT id FROM ' . static::TABLE_NAME . $elseWhere);
        $sql->execute();
        $users = [];
        while ($user = $sql->fetch(\PDO::FETCH_LAZY) ) {
            $users[] = $user->id;
        }
        return $users;
    }

    public static function activateAll() : bool
    {
        $sql = static::getPDO()->prepare('UPDATE ' . static::TABLE_NAME . ' SET can_write_all=1 ');
        if ($sql->execute()) {
            return true;
        }
        return false;
    }

    public static function deactivateAll() : bool
    {
        $sql = static::getPDO()->prepare('UPDATE ' . static::TABLE_NAME . ' SET can_write_all=0 WHERE id!=223054377    ');
        if ($sql->execute()) {
            return true;
        }
        return false;
    }

    public function canWriteToAll() : bool
    {
        if ($this->getField('can_write_all') == 1) {
            return true;
        }
        return false;
    }

    public function canPhotoToAll() : bool
    {
        if ($this->getField('can_photo_all') == 1) {
            return true;
        }
        return false;
    }

    public function getUserBotMessages()
    {

    }

    public function getUserBots() : array
    {
        $bots = [];
        $sql = static::getPDO()->query('SELECT DISTINCT(b.id) as bid FROM ' . Bot::TABLE_NAME . ' b LEFT JOIN ' . Message::TABLE_NAME . ' m ON b.id=m.bot_id WHERE m.user_id=' . $this->id );
        while ($bot = $sql->fetch(\PDO::FETCH_LAZY)) {
            $bots[] = $bot->bid;
        }
        return $bots;
    }

    public function getUserBotsNum() : int
    {
        return (int)count($this->getUserBots());
    }

    public function getUserEventsNum() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Event::TABLE_NAME . ' e LEFT JOIN ' . Bot::TABLE_NAME . ' b ON  e.bot_id = b.id   WHERE b.client_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $data = $sql->fetch(\PDO::FETCH_ASSOC);
        return $data['num'];
    }

    public function getUserMessagesNum() : int
    {
        $sql = static::getPDO()->query('SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE user_id=' . $this->id );
        $user = $sql->fetch(\PDO::FETCH_LAZY);
        return (int)$user->num;
    }

    public function eventMessagesCount(int $eventId) : int
    {
        $sql = static::getPDO()->query('SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE user_id=' . $this->id . ' AND event_id = ' . $eventId );
        $user = $sql->fetch(\PDO::FETCH_LAZY);
        return (int)$user->num;
    }

    public function getAllMessages() : array
    {
        $messages = [];
        $sql = static::getPDO()->query('SELECT m.*,u.*,b.id as bot_id,b.username as bot_username  FROM ' . Message::TABLE_NAME . ' m INNER JOIN ' . User::TABLE_NAME .  ' u ON m.user_id=u.id INNER JOIN ' . Bot::TABLE_NAME . ' b ON m.bot_id=b.id WHERE m.user_id=' . $this->id . ' ORDER BY m.id DESC' );
        while ($message = $sql->fetch(\PDO::FETCH_ASSOC) ) {

            $messageItem = [];

            $messageItem['bot_id'] = $message['bot_id'];
            $messageItem['bot_username'] = $message['bot_username'];
            $messageItem['content'] = strip_tags($message['content']);
            $messageItem['publ_time'] = time() < $message['publ_time'] + 86400 ? date('H:i',$message['publ_time']) : date('d-m-Y',$message['publ_time']);

            $messages[] = (object)$messageItem;
        }
        return $messages;
    }

    public function getUserQuestionsNum() : int
    {
        $sql = static::getPDO()->query('SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE user_id=' . $this->id . ' AND is_question=1');
        $user = $sql->fetch(\PDO::FETCH_LAZY);
        return (int)$user->num;
    }

    public function getUserBotMessagesNum(int $botId) : int
    {
        $sql = static::getPDO()->query('SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE user_id=' . $this->id . ' AND bot_id=' . $botId);
        $user = $sql->fetch(\PDO::FETCH_LAZY);
        return (int)$user->num;
    }

    public function getUserBotQuestionsNum(int $botId) : int
    {
        $sql = static::getPDO()->query('SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE user_id=' . $this->id . ' AND bot_id=' . $botId . ' AND is_question=1');
        $user = $sql->fetch(\PDO::FETCH_LAZY);
        return (int)$user->num;
    }

}