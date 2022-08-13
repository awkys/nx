<?php

namespace App\Services;

class CrispService
{
    private $website_id = "4551b328-d675-4bd1-85c0-fb7978280afd";
    private $identifier = "5440a8ad-9c30-4f5a-af3b-d9a88aea5c77";
    private $key = "8a421c437d752fb4bd905ea4de186e578cab1911a75c7ed826c88bb3f9b11541";
    private $tier = "plugin";

    public function curl($path, $method = "GET", $data = [])
    {
        $baseUrl = "https://api.crisp.chat/v1/website/{$this->website_id}/" . $path;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $baseUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if ($method === "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
        } elseif ($method === "PATCH") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
        } elseif ($method === "DELETE") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
        if ($data){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $login = sprintf('%s:%s', $this->identifier, $this->key);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-type:application/json',
            'Authorization: ' . sprintf('Basic %s', base64_encode($login)),
            'X-Crisp-Tier:' . $this->tier
        ]);
        $data = curl_exec($curl);
        curl_close($curl);

        return json_decode($data, true);
    }

    public function getUnreadMessage()
    {
        return $this->curl("conversations/1?filter_unread=1");
    }

    public function setReadMessage($session_id, $fingerprint)
    {
        $data = [
            'from' => 'operator',
            'origin' => 'urn:crisp.im:slack:0',
            'fingerprints' => [
                $fingerprint
            ]
        ];

        return $this->curl("conversation/{$session_id}/read", 'PATCH', $data);
    }

    public function getUserMessageInfo($session_id)
    {
        return $this->curl("conversation/{$session_id}");
    }

    public function getUserMessageLogs($session_id)
    {
        return $this->curl("conversation/{$session_id}/messages");
    }

    public function setState($session_id, $state)
    {
        return $this->curl("conversation/{$session_id}/state", 'PATCH', ['state' => $state]);
    }

    public function sendMessage($session_id, $content)
    {
        $data = [
            'type' => 'text',
            'from' => 'operator',
            'origin' => 'chat',
            'content' => $content,
            'user' => ['nickname'=>'管理员']
        ];

        return $this->curl("conversation/{$session_id}/message", 'POST', $data);
    }
}
