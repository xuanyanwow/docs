# Корутинный клиент TCP/UDP

`Coroutine\Client` предоставляет обертку для клиентов с сетевыми протоколами `TCP`, `UDP`, [unixSocket](/learn?id=что такое IPC) в виде [клиентов с сокетами](/coroutine_client/socket), их использование требует только создания нового объекта `Swoole\Coroutine\Client`.

* **Принцип реализации**

    * Все методы `Coroutine\Client`, связанные с сетевыми запросами, `Swoole` обрабатывает с помощью [корутинного расписания](/coroutine?id=корутинное расписание), и бизнес-уровень не должен быть осведомлен об этом
    * Использование методов полностью идентично синхронным методам [Client](/client)
    * Установка времени ожидания для `connect` также применима к `Connect`, `Recv` и `Send`

* **Синонимы**

    * `Coroutine\Client` не является потомком [Client](/client), но все методы, предоставляемые `Client`, могут быть использованы в `Coroutine\Client`. Пожалуйста, ознакомьтесь с [Swoole\Client](/client?id=Методы), и здесь мы не будем повторяться.
    * В `Coroutine\Client` можно использовать метод `set` для настройки [настройств](/client?id=настройства), и способ использования полностью идентичен методу `Client->set`. Для функций, которые отличаются в использовании, в разделе `set()` будет подробно описано отдельно

* **Пример использования**

```php
use Swoole\Coroutine\Client;
use function Swoole\Coroutine\run;

run(function () {
    $client = new Client(SWOOLE_SOCK_TCP);
    if (!$client->connect('127.0.0.1', 9501, 0.5))
    {
        echo "connect failed. Error: {$client->errCode}\n";
    }
    $client->send("hello world\n");
    echo $client->recv();
    $client->close();
});
```

* **Обработка протоколов**

Корутинный клиент также поддерживает обработку протоколов длины и `EOF`, и способ их настройки полностью идентичен [Swoole\Client](/client?id=настройства).

```php
$client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
$client->set(array(
    'open_length_check'     => true,
    'package_length_type'   => 'N',
    'package_length_offset' => 0, //Номер N-го байта - значение длины пакета
    'package_body_offset'   => 4, //От какого байта начинается расчет длины
    'package_max_length'    => 2000000, //Максимальная длина протокола
));
```


### connect()

Соединение с удаленным сервером.

```php
Swoole\Coroutine\Client->connect(string $host, int $port, float $timeout = 0.5): bool
```

  * **Параметры** 

    * **`string $host`**
      * **Функция**: Адрес удаленного сервера【внутренне автоматически происходит переключение на корутины для анализа доменного имени в IP-адрес】
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $port`**
      * **Функция**: Порт удаленного сервера
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

    * **`float $timeout`**
      * **Функция**: Время ожидания сетевого IO; включает `connect/send/recv`, при наступлении истечения времени ожидания соединение будет автоматически `close`, см. [правила истечения времени для клиентов](/coroutine_client/init?id=правила истечения времени)
      * **Единица измерения**: секунды【поддерживается точечное значение, например, `1.5` означает `1s`+`500ms`】
      * **По умолчанию**: `0.5s`
      * **Другие значения**: Нет

* **Примечание**

    * Если соединение потерпело неудачу, то вернется `false`
    * Возвращается после истечения времени ожидания, проверяйте `$cli->errCode` на значение `110`

* **Повторная попытка после неудачи**

!> После неудачи `connect` нельзя напрямую пытаться reconnect. Необходимо сначала закрыть существующий `socket` с помощью `close`, а затем попробовать `connect` заново.

```php
//Соединение потерпело неудачу
if ($cli->connect('127.0.0.1', 9501) == false) {
    //Закрыть существующий socket
    $cli->close();
    //Повторная попытка
    $cli->connect('127.0.0.1', 9501);
}
```

* **Пример**

```php
if ($cli->connect('127.0.0.1', 9501)) {
    $cli->send('data');
} else {
    echo 'connect failed.';
}

if ($cli->connect('/tmp/rpc.sock')) {
    $cli->send('data');
} else {
    echo 'connect failed.';
}
```


### isConnected()

Возвращает состояние соединения Client.

```php
Swoole\Coroutine\Client->isConnected(): bool
```

  * **Возвращаемое значение**

    * Возвращается `false`, что означает, что в настоящее время не установлено соединение с сервером
    * Возвращается `true`, что означает, что в настоящее время установлено соединение с сервером
    
!> Метод `isConnected` возвращает состояние на уровне приложения, он только показывает, что `Client` выполнил `connect` и успешно подключился к `Server`, и не выполнил `close` для закрытия соединения. `Client` может выполнять операции, такие как `send`, `recv`, `close`, но не может снова выполнять `connect`.  
Это не означает, что соединение обязательно可用, и при выполнении `send` или `recv` все равно возможно получение ошибки, потому что приложение не может получить состояние базового `TCP` соединения, и для выполнения `send` или `recv` приложение взаимодействует с ядром, чтобы получить истинное состояние доступности соединения.


### send()

Отправка данных.

```php
Swoole\Coroutine\Client->send(string $data): int|bool
```

  * **Параметры** 

    * **`string $data`**
    
      * **Функция**: Данные для отправки, должны быть типа string, поддерживаются битрейты
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

  * При успешной отправке возвращается количество字节, написанных в буфер сокета, низший уровень будет стараться отправить все данные. Если количество возвращенных字节 отличается от длины传入ной `$data`, это может означать, что сокет был закрыт другой стороной, и при следующем вызове `send` или `recv` будет возвращен соответствующий код ошибки.

  * При неудаче отправки возвращается `false`, можно использовать `$client->errCode` для получения причины ошибки.


### recv()

Метод `recv` используется для приема данных от сервера.

```php
Swoole\Coroutine\Client->recv(float $timeout = 0): string|bool
```

  * **Параметры** 

    * **`float $timeout`**
      * **Функция**: Установка времени ожидания
      * **Единица измерения**: секунды【Поддерживается точечное значение, например, `1.5` означает `1s`+`500ms`】
      * **По умолчанию**: См. [правила истечения времени для клиентов](/coroutine_client/init?id=правила истечения времени)
      * **Другие значения**: Нет

    !> При установке времени ожидания, предпочтение отдается указанному параметру, затем используется `timeout`, установленный в методе `set`. Код ошибки при истечении времени ожидания - `ETIMEDOUT`

  * **Возвращаемое значение**

    * Если установлена [протокол коммуникации](/client?id=Protocols), `recv` вернет полный данные, длина ограничена [package_max_length](/server/setting?id=package_max_length)
    * Если протокол коммуникации не установлен, `recv` максимально может вернуть `64K` данных
    * Если протокол коммуникации не установлен, `recv` вернет исходные данные, в которых необходимо самостоятельно реализовать обработку сетевого протокола в `PHP` коде
    * Возвращение пустой строки `recv` означает, что сервер активно закрыл соединение, необходимо `close`
    * При неудаче `recv`, возвращается `false`, можно проверить `$client->errCode` для получения причины ошибки, способ обработки можно посмотреть в следующем [полном примере](/coroutine_client/client?id=Полный пример)


### close()

Завершение соединения.

!> Метод `close` не имеет блокировки и немедленно возвращает результат. Операция закрытия не включает переключение на корутины.

```php
Swoole\Coroutine\Client->close(): bool
```


### peek()

Прыжок к данным.

!> Метод `peek` напрямую manipulate `socket`, поэтому не вызывает [корутинное расписание](/coroutine?id=корутинное расписание).

```php
Swoole\Coroutine\Client->peek(int $length = 65535): string
```

  * **Примечания**

    * Метод `peek` используется только для просмотра данных в буфере сокета ядра, без смещения. После использования `peek`, вызов `recv` все равно может прочитать эту часть данных
    * Метод `peek`是非блокирующий, он немедленно возвращает результат. Когда в буфере сокета есть данные, он вернет содержание данных. Если буфер пуст, он вернет `false` и установит `$client->errCode`
    * Если соединение уже закрыто, `peek` вернет пустую строку

### set()

Установка параметров клиента.

```php
Swoole\Coroutine\Client->set(array $settings): bool
```

  * **Конфигурационные параметры**

    * Пожалуйста, обратитесь к [Swoole\Client](/client?id=set) .

* **Отличия от [Swoole\Client](/client?id=set)**
    
    Корутинный клиент предлагает более детальный контроль за тайм-аутом. Можно установить:
    
    * `timeout`: Общий тайм-аут, включая соединение, отправку, прием всех тайм-аутов
    * `connect_timeout`: Тайм-аут соединения
    * `read_timeout`: Тайм-аут приема
    * `write_timeout`: Тайм-аут отправки
    * Смотрите [правила тайм-аута клиентов](/coroutine_client/init?id=правила_тайм-аута)

* **Пример**

```php
use Swoole\Coroutine\Client;
use function Swoole\Coroutine\run;

run(function () {
    $client = new Client(SWOOLE_SOCK_TCP);
    $client->set(array(
        'timeout' => 0.5,
        'connect_timeout' => 1.0,
        'write_timeout' => 10.0,
        'read_timeout' => 0.5,
    ));

    if (!$client->connect('127.0.0.1', 9501, 0.5))
    {
        echo "connect failed. Error: {$client->errCode}\n";
    }
    $client->send("hello world\n");
    echo $client->recv();
    $client->close();
});
```

### Полный пример

```php
use Swoole\Coroutine\Client;
use function Swoole\Coroutine\run;

run(function () {
    $client = new Client(SWOOLE_SOCK_TCP);
    if (!$client->connect('127.0.0.1', 9501, 0.5)) {
        echo "connect failed. Error: {$client->errCode}\n";
    }
    $client->send("hello world\n");
    while (true) {
        $data = $client->recv();
        if (strlen($data) > 0) {
            echo $data;
            $client->send(time() . PHP_EOL);
        } else {
            if ($data === '') {
                // Equals to empty directly close the connection
                $client->close();
                break;
            } else {
                if ($data === false) {
                    // You can handle it according to your business logic and error codes, for example:
                    // If timeout then do not close the connection, in other cases close the connection directly
                    if ($client->errCode !== SOCKET_ETIMEDOUT) {
                        $client->close();
                        break;
                    }
                } else {
                    $client->close();
                    break;
                }
            }
        }
        \Co::sleep(1);
    }
});
```
