# yii2-mail-queue

Yii2 Module to put mails into the queue. Module replaces ```mailer``` component.

1. Installation

```
composer require dimichspb\yii2-mail-queue
```

2. Configuration

add to config files ```web.php```, ```console.php``` or ```main.php``` for advanced yii2 template

```
'components' => [
    'mailer' => [
        'class' => Mailer::className(),
        'mailerOptions' => [
            'useFileTransport' => true,
        ],
        'useFileTransport' => false,
    ],
    'queue' => [
        'class' => \yii\queue\file\Queue::class,
    ]
],
```

3. Usage

Use ```mailer``` component in common way

```
$this->app->mailer->compose('example')
    ->setFrom('from@domain.com')
    ->setTo('to@domain.com')
    ->setSubject('Test message subject')
    ->setTextBody('Test message plain body');
    ->send();
```

This will put your message into MailQueue. To process the queue use common queue run command:

```
yii queue/run
```

or use yii2 queue listener
```
yii queue/listen
```

4. Have fun