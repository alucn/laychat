<?php
use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $data) {
       $message = json_decode($data, true);
       switch($message['type']) {
           case 'init':
               // 设置session
               $_SESSION = array(
                   'username' => $message['username'],
                   'avatar'   => $message['avatar'],
                   'id'       => $client_id,
                   'sign'     => $message['sign']
               );
               // 获得了client_id，通知当前客户端初始化
               $init_message = array(
                   'message_type' => 'init',
                   'client_id'    => $client_id,
               );
               Gateway::sendToClient($client_id, json_encode($init_message));

               // 通知所有客户端添加一个好友
               $reg_message = array('message_type'=>'addList', 'data'=>array(
                   'type'     => 'friend',
                   'username' => $message['username'],
                   'avatar'   => $message['avatar'],
                   'id'       => $client_id,
                   'sign'     => $message['sign'],
                   'groupid'  => 1
               ));
               Gateway::sendToAll(json_encode($reg_message), null, $client_id);

               // 让当前客户端加入群组101
               Gateway::joinGroup($client_id, 101);
               return;
           case 'chatMessage':
               // 聊天消息
               $type = $message['data']['to']['type'];
               $to_id = $message['data']['to']['id'];
               $chat_message = array(
                    'message_type' => 'chatMessage',
                    'data' => array(
                        'username' => $_SESSION['username'],
                        'avatar'   => $_SESSION['avatar'],
                        'id'       => $type === 'friend' ? $client_id : $to_id,
                        'type'     => $type,
                        'content'  => $message['data']['mine']['content'],
                        'timestamp'=> time()*1000,
                    )
               );
               if($type === 'friend'){
                   // 私聊
                   Gateway::sendToClient($to_id, json_encode($chat_message));
               } else {
                   // 群聊
                   Gateway::sendToGroup($to_id, json_encode($chat_message), $client_id);
               }
               return;
           default:
               echo "unknown message $data";
       }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id) {
       $logout_message = array(
           'message_type' => 'logout',
           'id'   => $client_id
       );
       Gateway::sendToAll(json_encode($logout_message));
   }
}
