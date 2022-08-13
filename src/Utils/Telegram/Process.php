<?php

namespace App\Utils\Telegram;

use App\Services\CrispService;
use App\Utils\Telegram;
use Telegram\Bot\Api;
use Exception;

class Process
{
    public static function index()
    {
        try {
            $bot = new Api($_ENV['telegram_token']);
            $bot->addCommands(
                [
                    Commands\MyCommand::class,
                    Commands\HelpCommand::class,
                    Commands\InfoCommand::class,
                    Commands\MenuCommand::class,
                    Commands\PingCommand::class,
                    Commands\StartCommand::class,
                    Commands\UnbindCommand::class,
                    Commands\CheckinCommand::class,
                    Commands\SetuserCommand::class,
                ]
            );
            $update = $bot->commandsHandler(true);
            $Message = $update->getMessage();
//            file_put_contents(BASE_PATH . '/storage/telegram.log', json_encode(file_get_contents("php://input")) . "\r\n", FILE_APPEND);
            if ($Message && $Message->getReplyToMessage() != null) {
                if (preg_match("/[#](.*)/", $Message->getReplyToMessage()->getText(), $match)) {
                    new Callbacks\ReplayTicket($bot, $Message, $match[1]);
                }
                $text = $Message->getReplyToMessage()->getText();
                if (strpos($text, 'Crisp新消息通知') !== false){
                    preg_match_all("/(?:\[)(.*)(?:\])/i", $text, $result);
                    file_put_contents(BASE_PATH . '/storage/crisp.log', json_encode($result) . "\r\n", FILE_APPEND);
                    $session_id = $result[1][0];
                    $crisp = new CrispService();
                    $res = $crisp->sendMessage($session_id, $Message->getText());
                    if ($res['error'] == false){
                        Telegram::Send("消息[$session_id]已回复成功", $_ENV['telegram_admin_id']);
                    } else {
                        Telegram::Send(json_encode($res), $_ENV['telegram_admin_id']);
                    }
                }
            } else if ($update->getCallbackQuery() !== null) {
                new Callbacks\Callback($bot, $update->getCallbackQuery());
            } else if ($Message !== null) {
                new Message($bot, $update->getMessage());
            }
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}
