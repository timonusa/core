<?php

class Telegram
{

    public function __construct($token){
        $this->token = $token;
        $this->url = "https://api.telegram.org/bot$token/";
    }

    public function sendMessage(string $msg, int $chat) : bool
    {
        if (file_get_contents($this->url."sendmessage?parse_mode=HTML&text=$msg&chat_id=$chat")) {
            return true;
        }
        return false;
    }

    public function sendPhoto(string $photo, int $chat) : bool
    {
        if (file_get_contents($this->url."sendPhoto?parse_mode=HTML&photo=$photo&chat_id=$chat")) {
            return true;
        }
        return false;
    }

    public function getFileUrl($file_id){
        if($response = file_get_contents($this->url."getFile?file_id=$file_id")){
            $response_obj = json_decode($response);
            $file_path = $response_obj->result->file_path;
            return 'https://api.telegram.org/file/bot'.$this->token.'/'.$file_path;
        }else{
            return false;
        }
    }

    public function hasAvatar($id){
        var_dump(json_decode($this->getUserProfilePhotos($id),true)['result']['photos'][0][2]['file_id']);

    }

    public function getAvatar($id){
        if (isset(json_decode($this->getUserProfilePhotos($id),true)['result'])) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/users/' . $id;
            $fileId = json_decode($this->getUserProfilePhotos($id),true)['result']['photos'][0][2]['file_id'];
            mkdir($path,'0777');
            chmod($path, 0777);
            file_put_contents($path.'/avatar.jpg',file_get_contents($this->getFileUrl($fileId)));
            $url = 'users/' . $id . '/avatar.jpg';
            return $url;
        }
        return null;
    }

    public function getPhoto($photoId){
        return $this->getFileUrl($photoId);
    }

    public function getUserProfilePhotos($id){
        return file_get_contents($this->url."getUserProfilePhotos?user_id=$id");
    }

    public function sendPhotoBulk(string $photo, array $ids)
    {
        $data = [
            'token' => $this->token,
            'method' => 'sendMPhoto',
            'photo' => $photo,
            'users' => $ids
        ];
        //сериализуем
        $jsonData = json_encode($data);

        $this->sendJson($jsonData);
    }

    public function sendMessageBulk(string $msg, array $ids)
    {
        $data = [
            'token' => $this->token,
            'method' => 'sendMessage',
            'message' => strip_tags($msg),
            'users' => $ids
        ];
        //сериализуем
        $jsonData = json_encode($data);

        $this->sendJson($jsonData);

    }

    public function sendJson(string $json) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"http://194.58.107.117:8010/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$json);

        $server_output = curl_exec($ch);
        curl_close ($ch);
    }

}
