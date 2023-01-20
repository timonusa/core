<?php

class Event extends Post
{
    const TABLE_NAME = 'core_event';
    const UPLOAD_DIR = 'events';

    public function getUsersId() : array
    {
        $sql = 'SELECT DISTINCT(user_id) FROM `' . Message::TABLE_NAME  .'`  WHERE event_id=' . $this->id . ' ORDER BY publ_time DESC ';
        //$sql = 'SELECT DISTINCT(user_id) FROM `' . Message::TABLE_NAME  .'`  WHERE event_id=' . $this->id . ' ORDER BY user_id DESC ';
        $users = [];
        $sql = static::getPDO()->query($sql);
        while($user = $sql->fetch(\PDO::FETCH_LAZY)){
            $users[] = (int)$user->user_id;
        }
        return $users;
    }

    public function getEventUsers(int $num = null) : array
    {
        $users = [];
        $amountSql = '';
        if ($num) {
            $amountSql .= " LIMIT " . intval($num) ;
        }
        $sql = 'SELECT u.*,u.id as user_id FROM ' . User::TABLE_NAME . ' u INNER JOIN '. Message::TABLE_NAME  .' m ON m.user_id=u.id WHERE m.event_id=' . $this->id . ' GROUP BY u.id ORDER BY m.publ_time DESC ' . $amountSql ;
        //$sql = 'SELECT DISTINCT(user_id) FROM `' . Message::TABLE_NAME  .'`  WHERE event_id=' . $this->id . ' ORDER BY publ_time DESC ' . $amountSql;
        $sql = static::getPDO()->query($sql);
        while($user = $sql->fetch()){
            $users[] = $user;
        }
        return $users;
    }

    public function getUsersNum() : int
    {
        $sql = 'SELECT COUNT(DISTINCT(user_id)) as num FROM `' . Message::TABLE_NAME  .'`  WHERE event_id=' . $this->id . '   ';
        $sql = static::getPDO()->query($sql);
        $users = $sql->fetch(\PDO::FETCH_LAZY);
        return $users->num;
    }

    public function getMessagesNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE event_id=' . $this->id . " AND `content` != '/start' ";
        $sql = static::getPDO()->query($sql);
        $messages = $sql->fetch(\PDO::FETCH_LAZY);

        return $messages->num;
    }

    public function getQuestions()
    {
        $sql = 'SELECT user_id, content FROM ' . Message::TABLE_NAME . ' WHERE event_id=' . $this->id .  ' AND is_question=1';
        $sql = static::getPDO()->query($sql);
        $questions = [];
        while ($question = $sql->fetch(\PDO::FETCH_ASSOC) ) {
            $questions[] = $question;
        }

        return $questions;
    }

    public static function approveMessage(string $message) : bool {
        $stopWordsJson = file_get_contents('https://bit-events.ru/restrictions/stopwords.json');
        $stopWords = json_decode($stopWordsJson, true);
        $message = strtolower($message);
        foreach ($stopWords as $word) {
            if (stristr(strtolower($message),$word)) {
                return false;
            }
        }
        return true;
    }

    public function getLastQuestions()
    {
        $stopWords = file_get_contents('https://bit-events.ru/restrictions/stopwords.json');

        $sql = 'SELECT *,m.id as mid FROM ' . Message::TABLE_NAME . ' m LEFT JOIN ' . User::TABLE_NAME .' u ON m.user_id=u.id WHERE m.event_id=' . $this->id . ' AND is_question=1 ORDER BY m.id DESC';
        $sql = static::getPDO()->query($sql);
        $questions = [];
        while ($question = $sql->fetch(\PDO::FETCH_ASSOC) ) {
            $questionContent = str_replace('Вопрос','',$question['content']);
            $questionContent = str_replace('вопрос','',$questionContent);

            if(static::approveMessage($questionContent) && trim($questionContent) != null) {
                $question['content'] = $questionContent;
                $questions[] = $question;
            }

        }

        return $questions;
    }

    public function getQuestionsNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM ' . Message::TABLE_NAME . ' WHERE event_id=' . $this->id .  ' AND is_question=1';
        $sql = static::getPDO()->query($sql);
        $messages = $sql->fetch(\PDO::FETCH_LAZY);

        return $messages->num;
    }

    public function getEventById(string $furl)  : bool
    {
        $sql = 'SELECT id FROM ' . static::TABLE_NAME . ' WHERE furl=:furl';
        $sql = static::getPDO()->prepare($sql);
        $sql->bindParam(':furl',$furl);
        $sql->execute();
        $event = $sql->fetch(\PDO::FETCH_LAZY);

        if ($event->id) {
            $this->id = $event->id;
            return true;
        }
        return false;

    }


    public function getRedNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM `' . Message::TABLE_NAME . '` WHERE `event_id` = ' . $this->id .  " AND `content` LIKE '%/red%' GROUP BY user_id ";
        $sql = static::getPDO()->query($sql);
        $messages = $sql->fetch(\PDO::FETCH_LAZY);

        return $messages->num ?? 0;
    }

    public function getBlueNumber() : int
    {
        $sql = 'SELECT COUNT(*) as num FROM `' . Message::TABLE_NAME . '` WHERE `event_id` = ' . $this->id .  " AND `content` LIKE '%/blue%' GROUP BY user_id ";
        $sql = static::getPDO()->query($sql);
        $messages = $sql->fetch(\PDO::FETCH_LAZY);

        return $messages->num ?? 0;
    }

    public function getLastMessages(int $num = null) : array
    {
        $messages = [];
        $amountSql = '';
        if ($num) {
            $amountSql .= " LIMIT $num ";
        }
        $sql = 'SELECT *,m.id as mid FROM ' . Message::TABLE_NAME . ' m LEFT JOIN ' . User::TABLE_NAME .' u ON m.user_id=u.id WHERE m.event_id=' . $this->id . " AND `content` != '/start' ORDER BY m.id DESC " . $amountSql;
        $sql = static::getPDO()->query($sql);
        while ($message = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $message['content'] = strip_tags($message['content']);
            $message['publ_time'] = (time() < $message['publ_time'] + 86400) ? date('H:i',$message['publ_time']) : date('d-m-Y',$message['publ_time']);
            $messages[] = (object)$message;
        }

        return $messages;
    }
}