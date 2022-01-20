<?php

    declare(strict_types=1);


    use IOL\Mailer\v1\DataSource\Queue;
    use IOL\Mailer\v1\Enums\QueueType;

    $basePath = __DIR__;
    for ($returnDirs = 0; $returnDirs < 1; $returnDirs++) {
        $basePath = substr($basePath, 0, strrpos($basePath, '/'));
    }


    require_once $basePath . '/_loader.php';

    $userQueue = new Queue(new QueueType(QueueType::MAILER));
    $userQueue->addConsumer(
        callback: static function (\PhpAmqpLib\Message\AMQPMessage $message): void {
            echo '[o] New Message on queue "' . QueueType::MAILER . '": ' . $message->body . "\r\n";
            $mail = new \IOL\Mailer\v1\Content\Mailer();
            try {
                $mail->importQueueMail($message->body);
            } catch(\IOL\Mailer\v1\Exceptions\IOLException $e){
                $message->reject(!$message->isRedelivered());
                echo '[!] Got error: ' . $e->getMessage() . "\r\n";
                return;
            }
            $mail->send();
            echo '[x] Sent Mail with ID ' . $mail->getUuid() . ' to receiver ' . $mail->getReceiver() . "\r\n\r\n";
            $message->ack();
        },
        type: new QueueType(QueueType::MAILER)
    );

    while ($userQueue->getChannel()->is_open()) {
        $userQueue->getChannel()->wait();
    }
