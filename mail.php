<?php
/**
* smtp_mail() - Отправка электронной почты с авторизацией через SMTP сервер
* v1.0.0
*
* smtp_read(); smtp_write() - вторичные
* Подключаемая пользовательская функция для отправки сообщений по электронной почте 
* с использованием аутентификации пользователя на почтовом сервере SMTP.
* Рекомендуется использовать в том случае, если стандартная функция mail()
* на хостинге работает неправильно или с ошибками. Данная функция корректно 
* работает на PHP 4 и выше с установленным модулем расширения php_sockets
* 
*
* http://koks-host.ru
* Оригинальная кодировка UTF-8
*/

function smtp_mail ($smtp,			// SMTP-сервер
          $port,			// порт SMTP-сервера
          $login,			// имя пользователя для доступа к почтовому ящику
          $password, 		// пароль для доступа к почтовому ящику
          $from,			// адрес электронной почты отправителя
          $from_name,		// имя отправителя
          $to, 			// адрес электронной почты получателя
          $subject, 		// тема сообщения
          $message,		// текст сообщения
          $res)			// сообщение, выводимое при успешной отправке
{	

//    header('Content-Type: text/plain;');	// необязательный параметр, особенно если включаем через include()
//    error_reporting(E_ALL ^ E_WARNING);	// необязательный параметр, включает отображение всех ошибок и предупреждений
//    ob_implicit_flush();					// необязательный параметр, включает неявную очистку

//    блок для других кодировок, отличных от UTF-8
//    $message = iconv("UTF-8","KOI8-R",$message); // конвертируем в koi8-r
//    $message = "Content-Type: text/plain; charset=\"koi8-r\"\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$message; // конвертируем в koi8-r
//    $subject=base64_encode(iconv("UTF-8","KOI8-R",$subject)); // конвертируем в koi8-r
//    $subject=base64_encode($subject); // конвертируем в koi8-r

  $from_name = base64_encode($from_name);
  $subject = base64_encode($subject);
  $message = base64_encode($message);
    $message = "Content-Type: text/plain; charset=\"utf-8\"\r\nContent-Transfer-Encoding: base64\r\nUser-Agent: Koks Host Mail Robot\r\nMIME-Version: 1.0\r\n\r\n".$message;
    $subject="=?utf-8?B?{$subject}?=";
    $from_name="=?utf-8?B?{$from_name}?=";

    try {
        
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket < 0) {
            throw new Exception('socket_create() failed: '.socket_strerror(socket_last_error())."\n");
        }

        $result = socket_connect($socket, $smtp, $port);
        if ($result === false) {
            throw new Exception('socket_connect() failed: '.socket_strerror(socket_last_error())."\n");
        } 

        smtp_read($socket);
        
        smtp_write($socket, 'EHLO '.$login);
        smtp_read($socket); 
        smtp_write($socket, 'AUTH LOGIN');
        smtp_read($socket);        
        smtp_write($socket, base64_encode($login));
        smtp_read($socket);
        smtp_write($socket, base64_encode($password));
        smtp_read($socket); 
        smtp_write($socket, 'MAIL FROM:<'.$from.'>');
        smtp_read($socket); 
        smtp_write($socket, 'RCPT TO:<'.$to.'>');
        smtp_read($socket); 
        smtp_write($socket, 'DATA');
        smtp_read($socket); 
        $message = "FROM:".$from_name."<".$from.">\r\n".$message; 
        $message = "To: $to\r\n".$message; 
        $message = "Subject: $subject\r\n".$message;

  date_default_timezone_set('UTC');
  $utc = date('r');

        $message = "Date: $utc\r\n".$message;
        smtp_write($socket, $message."\r\n.");
        smtp_read($socket); 
        smtp_write($socket, 'QUIT');
        smtp_read($socket); 
        return $res;
        
    } catch (Exception $e) {
        echo "\nError: ".$e->getMessage();
    }

   
    if (isset($socket)) {
        socket_close($socket);
    }
}

function smtp_read($socket) {
  $read = socket_read($socket, 1024);
        if ($read{0} != '2' && $read{0} != '3') {
            if (!empty($read)) {
                throw new Exception('SMTP failed: '.$read."\n");
            } else {
                throw new Exception('Unknown error'."\n");
            }
        }
}
    
function smtp_write($socket, $msg) {
  $msg = $msg."\r\n";
  socket_write($socket, $msg, strlen($msg));
}

?>
