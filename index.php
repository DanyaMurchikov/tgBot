<?php
echo("<<<<<<START>>>>>>");
define ("token" ,"8045689670:AAHSvRh7XB6V8xl4N6O9hyH-xVuavWTqFG0");
$offset = 0;
$messages = [
    'welcome'=>'Привет. для получения секретной информации нужно правильно решить пример. Напиши "пример" в чат',
    'secret'=>'Вот секрет - 123',
    'secret not available'=>'Секрет будет доступен после решения примера. Напиши в чат "пример"',
    'wrong'=>'Ты неправильно решил пример. Давай попробуем ещё раз? Напиши "пример", если хочешь поменять',
    'example solved'=>'Ты правильно решил пример и тебе нвсегда доступен секрет. Просто напиши "секрет" когда захочешь',
    'unexpected message'=>'Я не совсем понимаю, что ты хочешь. Ты можешь написать "пример", чтобы получить пример, и написать "секрет", чтобы получить секрет'
];
$db = new PDO('sqlite:bot_database.sqlite');
while(true) {
    $url  = "https://api.telegram.org/bot".token."/getUpdates?offset=$offset";
    $updates = json_decode(file_get_contents($url), true);
    
    if (!isset($updates['result'])) {
        sleep(1);
        continue;
        
    }
    
    foreach ($updates['result'] as $update) {
        $offset = $update['update_id'] + 1;
        
    if (!isset($update['message'])) {
        continue; 
    }
    
    $message = $update['message'];
    
    if (!isset($message['chat']['id']) || !isset($message['text'])) {
        continue; 
    }

        $chatId = $update['message']['chat']['id'];
        $text   = trim($update['message']['text']);
        $text = mb_strtolower($text);
        $stmt = $db->prepare("select * from telegram_bot where chatId = $chatId");
        $stmt->execute();
        
        $user = $stmt->fetch();

        if($user){
            echo('чел найден');
        }else{
            $stmt = $db->prepare("insert into telegram_bot (chatId, example, answer, authorized) values (?,?,?,?)");
            $stmt->execute([$chatId, null, null, 'no']);
            error_log("data update in foreach");
            sendMessage($chatId, $messages['welcome'], token);
        }


        if (is_numeric($text)) {
            if(is_null($user["example"])){
                var_dump($user);
                var_dump($user['example']);

                sendMessage($chatId, $messages['unexpected message'], token);
            }elseif($text == $user["answer"]){
                var_dump($user['answer']);

                $stmt = $db->prepare("update telegram_bot set authorized =? where chatId = ?");
                $stmt->execute(["yes", $chatId]);

                sendMessage($chatId, $messages['example solved'], token);
            }elseif($text != $user["answer"]){

                sendMessage($chatId, $messages['wrong'], token);
            }
        }elseif($text === 'пример'){
            generateExample($chatId, $db);
        }elseif(strtolower($text) === 'секрет'){
            if ($user['authorized'] === 'no') {
                sendMessage($chatId, $messages['secret not available'], token);
            } elseif($user['authorized'] === 'yes') {
                sendMessage($chatId, $messages['secret'], token);
            }
        }elseif($text==="/start"){
            echo('user start');
        }
        else{
            sendMessage($chatId, $messages['unexpected message'], token);
        }

        continue;
        }
        sleep(1);
    }
function generateExample($chatId, $database){
    $a = rand(1,100);
    $b = rand(1,100);
    $example = "$a+$b";
    $answer = $a+$b;

    $stmt = $database->prepare("update telegram_bot set example =?, answer = ? where chatId = ?");
    $stmt->execute([$example, $answer, $chatId]);
    sendMessage($chatId,$example, token);
}
function sendMessage($chatId, $text, $token){
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($text));
}
?>