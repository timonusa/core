<?php

class Client extends  Post
{
    const TABLE_NAME = 'core_client';

    public function getBots()
    {
        $sql = 'SELECT * FROM ' . Bot::TABLE_NAME . ' WHERE client_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $bots = [];
        while ($bot = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $bots[] = $bot;
        }
        return $bots;
    }

    public function getBotsNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Bot::TABLE_NAME . ' WHERE client_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $data = $sql->fetch(\PDO::FETCH_ASSOC);
        return $data['num'];
    }

    public function getEventsNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Event::TABLE_NAME . ' e LEFT JOIN ' . Bot::TABLE_NAME . ' b ON  e.bot_id = b.id   WHERE b.client_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $data = $sql->fetch(\PDO::FETCH_ASSOC);
        return $data['num'];
    }

    public function getUsersNumber() : int
    {
        $sql = 'SELECT COUNT(DISTINCT(m.user_id)) as num FROM ' . User::TABLE_NAME . ' u LEFT JOIN  ' . Message::TABLE_NAME . ' m ON m.user_id = u.id LEFT JOIN ' . Event::TABLE_NAME . ' e ON m.event_id = e.id LEFT JOIN ' . Bot::TABLE_NAME . ' b ON  e.bot_id = b.id   WHERE b.client_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $data = $sql->fetch(\PDO::FETCH_ASSOC);
        return $data['num'];
    }

    public function getMessagesNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' m LEFT JOIN ' . Event::TABLE_NAME . ' e ON m.event_id = e.id LEFT JOIN ' . Bot::TABLE_NAME . ' b ON  e.bot_id = b.id   WHERE b.client_id=' . $this->id;
        $sql = static::getPDO()->query($sql);
        $data = $sql->fetch(\PDO::FETCH_ASSOC);
        return $data['num'];
    }

    public function hasBot(int $botId) : bool
    {
        $sql = static::getPDO()->prepare('SELECT COUNT(*) as num FROM ' . Bot::TABLE_NAME . ' WHERE id=:bot_id AND client_id=' . $this->id) ;
        $sql->bindParam(':bot_id',$botId);
        $sql->execute();
        $bot = $sql->fetch(\PDO::FETCH_ASSOC);
        if ($bot['num'] > 0) {
            return true;
        }
        return false;
    }

    public function hasEvent(int $eventId) : bool
    {
        $sql = static::getPDO()->prepare('SELECT COUNT(*) as num FROM ' . Bot::TABLE_NAME . ' b INNER JOIN ' . Event::TABLE_NAME . ' e ON e.bot_id=b.id WHERE e.id=:event_id AND client_id=' . $this->id) ;
        $sql->bindParam(':event_id',$eventId);
        $sql->execute();
        $bot = $sql->fetch(\PDO::FETCH_ASSOC);
        if ($bot['num'] > 0) {
            return true;
        }
        return false;
    }

    public function hasUser(int $userId) : bool
    {
        $sql = static::getPDO()->prepare('SELECT COUNT(*) as num FROM ' . Bot::TABLE_NAME . ' b INNER JOIN ' . Event::TABLE_NAME . ' e ON e.bot_id=b.id INNER JOIN ' . Message::TABLE_NAME . ' m ON m.bot_id=b.id   WHERE m.user_id=:user_id AND client_id=' . $this->id) ;
        $sql->bindParam(':user_id',$userId);
        $sql->execute();
        $user = $sql->fetch(\PDO::FETCH_ASSOC);
        if ($user['num'] > 0) {
            return true;
        }
        return false;
    }

    public static function existByTelegramId(int $telegramId) : bool
    {
        $sql = static::getPDO()->prepare('SELECT COUNT(*) as num FROM ' . static::TABLE_NAME . '  WHERE telegram_id=:telegram_id ') ;
        $sql->bindParam(':telegram_id',$telegramId);
        $sql->execute();
        $client = $sql->fetch(\PDO::FETCH_ASSOC);
        if ($client['num'] > 0) {
            return true;
        }
        return false;
    }

    public static function idByTelegramId(int $telegramId) : ?int
    {
        $sql = static::getPDO()->prepare('SELECT id FROM ' . static::TABLE_NAME . '  WHERE telegram_id=:telegram_id ') ;
        $sql->bindParam(':telegram_id',$telegramId);
        $sql->execute();
        $client = $sql->fetch(\PDO::FETCH_ASSOC);
        if ($client['id'] > 0) {
            return $client['id'];
        }
        return null;
    }

    public static function logIn(string $username, string $password) : ?string
    {
        $sql = static::getPDO()->prepare('SELECT id,password FROM ' . static::TABLE_NAME . " WHERE username=:username ");
        $sql->bindParam(':username',$username);
        $sql->execute();
        $user = $sql->fetch(\PDO::FETCH_LAZY) ;
        if (isset($user->id)) {
            if (!hash_equals($user->password,crypt($password,$user->password))) {
                return null;
            }
            $className = __CLASS__;
            $user = new $className($user->id);
            $userHash = md5($user->id . $username . $password . (string)time());
            $user->updateField('user_hash',$userHash);
            //qsetcookie('event_admin_hash',$userHash,time() + 10000,'/');
            return $userHash;
        }
        return null;
    }


    public static function logOut() : bool
    {
        setcookie('event_admin_hash',"",time() - 10000,'/');
        return true;
    }

    public static function validate(string $token) : bool
    {
        if (isset($token)) {
            $userHash = $token;
            $sql = static::getPDO()->prepare('SELECT COUNT(*) as num FROM ' . static::TABLE_NAME . " WHERE user_hash=:user_hash ");
            $sql->bindParam(':user_hash',$userHash);
            $sql->execute();
            $user = $sql->fetch(\PDO::FETCH_LAZY);
            if ($user->num > 0) {
                return true;
            }
        }
        return false;
    }

    public function getAvatarDefault()
    {
        return 'avatar.png';
    }

    public static function findClient(string $token) : ?Client
    {
        if (isset($token)) {
        //if (isset($_COOKIE['event_admin_hash'])) {
            $userHash = $token;
            $sql = static::getPDO()->prepare('SELECT id FROM ' . static::TABLE_NAME . " WHERE user_hash=:user_hash ");
            $sql->bindParam(':user_hash',$userHash);
            $sql->execute();
            $user = $sql->fetch(\PDO::FETCH_LAZY);
            if ($user->id) {
                return new static($user->id);
            }
        }
        return null;
    }

}

