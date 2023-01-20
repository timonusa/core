<?php

class Bot extends Post
{
    const TABLE_NAME = 'core_bot';
    const UPLOAD_DIR = 'bots';


    public static function createViaChat($text,$userId) : bool
    {
        //выпарсиваем из текста токен
        $username = explode('You will find it at t.me/',explode('. You can now',$text)[0])[1];

        //определяем id клиента
        $clientId = Client::idByTelegramId($userId);

        //выпарсиваем имя бота
        $token = explode('Use this token to access the HTTP API:',explode('Keep your token secure',$text)[0])[1];
        $token = trim(trim($token,'_'));

        //$bot = new Bot(24);
        //$telegram = new Telegram($bot->getField('token'));
        //$telegram->sendMessage($username,$userId);
        //$telegram->sendMessage($token,$userId);

        //$sql = "INSERT INTO core_bot (`token`,`username`,`title`,`client_id`) VALUES ('$token','$username','$username',$userId) ";
        //$telegram->sendMessage($sql,$userId);
        //static::getPDO()->query($sql);

        //создаем нового бота
        if (($botId = static::createLine(['token','username','title','client_id'],[$token,$username,$username,$clientId])) > 0) {


            //вешаем вебхук
            file_get_contents('https://api.telegram.org/bot' . $token . '/setWebhook?url=https://bit-events.ru/bot-all.php?bot_id=' . $botId );

            //создаем автоматически первое событие в боте
            $eventFields = [];
            $eventFields[] = 'bot_id';
            $eventFields[] = 'title';
            $eventFields[] = 'active';
            $eventFields[] = 'furl';
            $eventFields[] = 'date';
            $eventValues = [];
            $eventValues[] = $botId;
            $eventValues[] = 'Базовое событие бота';
            $eventValues[] = 1;
            $eventValues[] = md5('Базовое событие бота' . time());
            $eventValues[] = date('Y-m-d');
            Event::createLine($eventFields,$eventValues);

            return true;
        }



        return false;
    }

    public function getUsers(int $num = null) : array
    {
        $amountSql = '';
        if ($num) {
            $amountSql .= " LIMIT " . intval($num) ;
        }
        $sql = 'SELECT *,u.id as uid FROM ' . User::TABLE_NAME . ' u INNER JOIN '. Message::TABLE_NAME  .' m ON m.user_id=u.id WHERE m.bot_id=' . $this->id . ' GROUP BY u.id ORDER BY m.publ_time DESC ' . $amountSql ;
        //$sql = 'SELECT *,u.id as uid FROM ' . User::TABLE_NAME . ' u INNER JOIN '. Message::TABLE_NAME  .' m ON m.user_id=u.id WHERE m.bot_id=' . $this->id . '  ORDER BY m.publ_time DESC ' . $amountSql ;
        $users = [];
        $sql = static::getPDO()->query($sql);
        while($user = $sql->fetch(\PDO::FETCH_ASSOC)){
            $users[] = (object)$user;
        }

        return $users;
    }

    public function getUsersId() : array
    {
        $sql = 'SELECT DISTINCT(user_id) FROM ' . Message::TABLE_NAME  .'  WHERE bot_id=' . $this->id . ' ';
        $users = [];
        $sql = static::getPDO()->query($sql);
        while($user = $sql->fetch(\PDO::FETCH_LAZY)){
            $users[] = (int)$user->user_id;
        }

        return $users;
    }
    public function getLastUsersId(int $num) : array
    {
        $sql = 'SELECT DISTINCT(user_id) FROM ' . Message::TABLE_NAME  .'  WHERE bot_id=' . $this->id . ' LIMIT ' . $num;
        $users = [];
        $sql = static::getPDO()->query($sql);
        while($user = $sql->fetch(\PDO::FETCH_LAZY)){
            $users[] = (int)$user->user_id;
        }

        return $users;
    }


    public function getEvents() : array
    {
        $sql = 'SELECT * FROM ' . Event::TABLE_NAME . ' WHERE bot_id = ' . $this->id . ' ORDER BY id DESC ';
        $events = [];
        $sql = static::getPDO()->query($sql);
        while($event = $sql->fetch(\PDO::FETCH_ASSOC)){
            $events[] = $event;
        }

        return $events;
    }

    public function getEventsNum() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Event::TABLE_NAME . ' WHERE bot_id = ' . $this->id . '  ';
        $sql = static::getPDO()->query($sql);
        $events = $sql->fetch(\PDO::FETCH_ASSOC);

        return (int)$events['num'];
    }

    public static function getIdByToken(string $token) : int
    {
        $sql = 'SELECT id FROM ' . static::TABLE_NAME . " WHERE token= '" . $token . "' ";
        $sql = static::getPDO()->query($sql);
        $bot = $sql->fetch(\PDO::FETCH_LAZY);
        return (int)$bot->id;
    }

    public function deactivateEventsAll() : bool
    {
        $sql = 'UPDATE `' . Event::TABLE_NAME . "` SET active = 0 WHERE bot_id= " . $this->id . " ";
        if (static::getPDO()->query($sql)) {
            return true;
        }
        return false;
    }



    public function getUsersNumber() : int
    {
        return count($this->getUsers());
    }

    public function getMessagesNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE bot_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $messages = $sql->fetch(\PDO::FETCH_LAZY);

        return $messages->num;
    }

    public function getQuestionsNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE bot_id=' . $this->id .  ' AND is_question=1';
        $sql = static::getPDO()->query($sql);
        $messages = $sql->fetch(\PDO::FETCH_LAZY);

        return $messages->num;
    }

    public function getLastMessages(int $num = null) : array
    {
        $messages = [];
        $amountSql = '';
        if ($num) {
            $amountSql .= " LIMIT $num ";
        }
        $sql = 'SELECT *,m.id as mid FROM ' . Message::TABLE_NAME . ' m LEFT JOIN ' . User::TABLE_NAME .' u ON m.user_id=u.id WHERE m.bot_id=' . $this->id . ' ORDER BY m.id DESC ' . $amountSql ;
        $sql = static::getPDO()->query($sql);
        while ($message = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $message['content'] = strip_tags($message['content']);
            $message['publ_time'] = (time() < $message['publ_time'] + 86400) ? date('H:i',$message['publ_time']) : date('d-m-Y',$message['publ_time']);
            $messages[] = (object)$message;
        }

        return $messages;
    }

    public function activeEventId()
    {
        $sql = 'SELECT id FROM ' . Event::TABLE_NAME . '  WHERE bot_id=' . $this->id . ' AND active = 1 ORDER BY id DESC';
        $sql = static::getPDO()->query($sql);
        $event = $sql->fetch(\PDO::FETCH_LAZY);

        return $event->id;
    }

    public function activateEvent(int $eventId) : bool
    {
        $sql = 'UPDATE ' . Event::TABLE_NAME . ' SET active = 0 ';
        static::getPDO()->query($sql);

        $sql = 'UPDATE ' . Event::TABLE_NAME . ' SET active = 1  WHERE event_id=' . $eventId ;
        static::getPDO()->query($sql);

        return true;
    }
}
