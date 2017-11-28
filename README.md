# ICQ-Class
An old ICQ(OSCAR) protocol implementation in PHP 5.1<br />
I wrote this class in 2006 when ICQ messenger was very popular in Europe, class has a few features like:<br />
SendSSIxReply, SendSSI, SendSSIxReply, SendMsg. <br /><br />

## Usage example
```php
$cicq = new ICQ();
if($cicq->connect("362555877","GUPLTWCC"))
{
  $cicq->icqLoop("458172","hi");
}
```
