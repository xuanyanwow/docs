# События

В этом разделе представлены все обратные связи Swoole, каждая из которых является функцией PHP, соответствующая определенному событию.


## onStart

?> **После запуска эта функция будет вызвана в главной нити главного процесса (master)**

```php
function onStart(Swoole\Server $server);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

* **До этого события `Server` выполнил следующие действия**

    * Завершил создание [Manager процесса](/learn?id=manager进程)
    * Завершил создание [Worker дочерних процессов](/learn?id=worker进程)
    * Слушает все TCP/UDP/[unixSocket](/learn?id=что такое IPC) порты, но не начал принимать соединения и запросы
    * Слушает таймеры

* **Далее следует выполнить**

    * Главный [Reactor](/learn?id=reactor线程) начинает принимать события, клиенты могут `connect` к `Server`

В `onStart` обратной связи разрешены только операции `echo`, печать `Log`, изменение имени процесса. Не следует выполнять другие действия (не можно вызывать функции, связанные с `server`, поскольку служба еще не готова). `onWorkerStart` и `onStart` обратные связи выполняются параллельно в разных процессах, нет последовательности.

В `onStart` обратной связи можно сохранить значения `$server->master_pid` и `$server->manager_pid` в файле. Таким образом, можно написать сценарий, чтобы отправить сигналы этим двум `PID` для выполнения операций закрытия и перезапуска.

`onStart` событие вызывается в главной нити `Master` процесса.

!> Globальные ресурсы, созданные в `onStart`, не могут быть использованы в `Worker` процессе, поскольку вызов `onStart` происходит до создания `worker` процесса  
Новые объекты создаются в главном процессе, `Worker` процесс не может получить доступ к этой области памяти  
Поэтому код создания глобальных объектов должен быть размещен до `Server::start`, типичным примером является [Swoole\Table](/memory/table?id=полный пример)

* **Опасения безопасности**

В `onStart` обратной связи можно использовать API асинхронных и корутинных функций, но следует помнить, что это может столкнуться с конфликтом с `dispatch_func` и `package_length_func`, **не следует использовать их одновременно**.

Пожалуйста, не запускайте таймеры в `onStart`, если в коде выполняется операция `Swoole\Server::shutdown()`, то из-за постоянного выполнения таймера программа не сможет завершиться.

В `onStart` обратной связи серверная программа не примет никаких подключений от клиентов до `return`, поэтому можно безопасно использовать синхронные блокирующие функции.

* **Базовый режим**

В режиме [SWOOLE_BASE](/learn?id=swoole_base) нет `master` процесса, поэтому нет события `onStart`, пожалуйста, не используйте обратную связь `onStart` в `BASE` режиме.

```
WARNING swReactorProcess_start: Использование события onStart с SWOOLE_BASE устарело
```


## onBeforeShutdown

?> **Это событие происходит перед нормальным завершением `Server`** 

!> Доступно с версии Swoole >= `v4.8.0`. В этом событии можно использовать API корутин.

```php
function onBeforeShutdown(Swoole\Server $server);
```


* **Параметры**

    * **`Swoole\Server $server`**
        * **Функция**: Объект Swoole\Server
        * **По умолчанию**: Нет
        * **Другие значения**: Нет


## onShutdown

?> **Это событие происходит после нормального завершения `Server`**

```php
function onShutdown(Swoole\Server $server);
```

  * **Параметры**

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

  * **До этого `Swoole\Server` выполнил следующие действия**

    * Закрыл все [Reactor](/learn?id=reactor线程) нити, нити `HeartbeatCheck`, нити `UdpRecv`
    * Закрыл все `Worker` процессы, [Task processes](/learn?id=taskworker进程), [User processes](/server/methods?id=addprocess)
    * Закрыл все `TCP/UDP/UnixSocket` 监听 порты
    * Закрыл главный [Reactor](/learn?id=reactor线程)

  !> принудительное `kill` процесса не вызовет `onShutdown`, например, `kill -9`  
  Для нормального завершения необходимо отправить сигнал `SIGTERM` главному процессу с помощью `kill -15`  
  Откройте программу в командной строке с помощью `Ctrl+C`, чтобы немедленно остановить ее, и нижестоящая часть не вызовет `onShutdown`

  * **Примечания**

  !> Не следует в `onShutdown` вызывать никаких асинхронных или корутинных API, поскольку при возникновении `onShutdown` нижестоящая часть уже уничтожила все события и Loop;  
В это время уже не существует корутинной среды, и если разработчикам нужно использовать корутинные API, им необходимо вручную вызвать `Co\run`, чтобы создать [корутинный контейнер](/coroutine?id=что такое корутинный контейнер).


## onWorkerStart

?> **Это событие происходит при запуске Worker процесса/ [Task processes](/learn?id=taskworker进程), здесь созданные объекты могут быть использованы в течение жизни процесса.**

```php
function onWorkerStart(Swoole\Server $server, int $workerId);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $workerId`**
      * **Функция**: `ID` `Worker` процесса (не PID процесса)
      * **По умолчанию**: Нет
      * **Другие значения**: Нет

  * `onWorkerStart/onStart` выполняются параллельно, нет последовательности
  * Можно определить, является ли текущий процесс `Worker` или [Task processes](/learn?id=taskworker进程) с помощью свойства `$server->taskworker`
  * Когда установлены `worker_num` и `task_worker_num` больше `1`, каждое событие будет вызвано для каждого процесса, и можно отличить различные рабочие процессы с помощью проверки [$worker_id](/server/properties?id=worker_id)
  * `Worker` процесс отправляет задания `Task` процессу, который после обработки всех заданий уведомляет `Worker` процесс с помощью обратной связи [onFinish](/server/events?id=onfinish). Например, можно отправить уведомления всем десятью тысячам пользователей в фоне, после завершения операции состояние показывает как отправка进行中, в это время можно продолжать другие операции, и когда отправка писем будет завершена, состояние операции автоматически изменится на отправлено.

Ниже приведен пример для переименования Worker процесса/ [Task processes](/learn?id=taskworker进程).

```php
$server->on('WorkerStart', function ($server, $worker_id){
    global $argv;
    if($worker_id >= $server->setting['worker_num']) {
        swoole_set_process_name("php {$argv[0]} task worker");
    } else {
        swoole_set_process_name("php {$argv[0]} event worker");
    }
});
```

Если вы хотите использовать механизм [Reload](/server/methods?id=reload) для пересоздания кода, вам необходимо `require` свои бизнес-файлы в `onWorkerStart`, а не в начале файла. Файлы, включенные до вызова `onWorkerStart`, не будут пересозданы.

Вы можете разместить общие, неизменные PHP-файлы перед `onWorkerStart`. Таким образом, хотя код не может быть пересоздан, все `Worker`进程 делятся ими, и не требуется дополнительные памяти для хранения этих данных.
Кода после `onWorkerStart` каждый процесс должен сохранять копию в памяти

  * `$worker_id` обозначает `ID` этого `Worker` процесса, диапазон см. в [$worker_id](/server/properties?id=worker_id)
  * [$worker_id](/server/properties?id=worker_id) не имеет никакого отношения к PID процесса, можно использовать функцию `posix_getpid` для получения PID

  * **Поддержка корутин**

    * В обратной связи `onWorkerStart` автоматически создаются корутины, поэтому в `onWorkerStart` можно использовать API корутин

  * **Примечание**

    !> В случае возникновения критической ошибки или явного вызова `exit` в коде, `Worker/Task` процесс будет завершен, а управляющий процесс создаст новый процесс. Это может привести к бесконечному циклу создания и уничтожения процессов

## onWorkerStop

?> **Этот инцидент возникает при завершении процесса `Worker`. В этой функции можно освободить различные ресурсы, allocated进程`Worker`.**

```php
function onWorkerStop(Swoole\Server $server, int $workerId);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $workerId`**
      * **Функция**: ID процесса `Worker` (не PID процесса)
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Примечание**

    !> - Процесс завершается необычно, например, при принужденном `kill`, критической ошибке, `core dump` `onWorkerStop` обратный вызов не будет выполнен.  
    - Не следует в `onWorkerStop` вызывать какие-либо асинхронные или CORO-поставленные `API`, поскольку при срабатывании на `onWorkerStop` все [loop событий](/learn?id=что такоеeventloop) на уровне уже уничтожены.


## onWorkerExit

?> **Только если включена особенность [reload_async](/server/setting?id=reload_async). Смотрите [как правильно перезапустить обслуживание](/question/use?id=как правильно перезапустить обслуживание)**

```php
function onWorkerExit(Swoole\Server $server, int $workerId);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $workerId`**
      * **Функция**: ID процесса `Worker` (не PID процесса)
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Примечание**

    !> - Процесс `Worker` не выходит, `onWorkerExit` будет постоянно срабатывать  
    - `onWorkerExit` будет срабатывать внутри процесса `Worker`, если в [Taskprocess](/learn?id=taskworker进程) есть [loop событий](/learn?id=что такоеeventloop), они также будут срабатывать  
    - В `onWorkerExit` по возможности удаляйте/закрывать асинхронные `Socket` соединения, в конце концов, когда основной уровень обнаруживает, что количество хndenды для прослушивания событий в [loop событий](/learn?id=что такоеeventloop) составляет `0`, процесс выходит  
    - Когда у процесса нет хndenды для прослушивания событий, функция этого вызова не будет вызвана при завершении процесса  
    - Отвечает после того, как процесс `Worker` выйдет, Callback события `onWorkerStop` будет Executed


## onConnect

?> **Когда новый соединение становится accessible, callback выполняется внутри worker进程. **

```php
function onConnect(Swoole\Server $server, int $fd, int $reactorId);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $fd`**
      * **Функция**: описатель файла соединения
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $reactorId`**
      * **Функция**: ID线程 Reactor, в котором было установлено соединение
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Примечание**

    !> Эти два callback – `onConnect/onClose` – происходят внутри worker-process, а не в главном процессе.  
    При использовании протокола UDP есть только событие [onReceive](/server/events?id=onreceive), нет событий `onConnect/onClose`
    
    * **[dispatch_mode](/server/setting?id=dispatch_mode) = 1/3**
    
      В этом режиме `onConnect/onReceive/onClose` могут быть доставлены на разные процессы. PHP-объекты, связанные с подключением, не могут быть инициализированы в callback [onConnect](/server/events?id=onconnect), и очищены в [onClose](/server/events?id=onclose).
      
      Эти три события `onConnect/onReceive/onClose` могут выполняться одновременно и могут привести к необычным ситуациям.


## onReceive

?> **Когда получена данные,回调 эта функция, происходит внутри worker进程.**

```php
function onReceive(Swoole\Server $server, int $fd, int $reactorId, string $data);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $fd`**
      * **Функция**: описатель файла соединения
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $reactorId`**
      * **Функция**: ID线程 Reactor, в котором установлено TCP-соединение
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`string $data`**
      * **Функция**: Содержание полученной данные, которое может быть текстом или бинарным содержанием
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **О целостности пакетов в протоколе TCP, смотрите [Проблема границ пакетов TCP](/learn?id=tcp%D0%B8%D0%BC%D0%B5%D1%82)**

    Использование нижних уровней для настройки `open_eof_check/open_length_check/open_http_protocol` и других настроек может обеспечить целостность пакетов
   不使用 нижних уровней для обработки протокола, после обработки данных в PHP-коде [onReceive](/server/events?id=onreceive), анализируйте данные самостоятельно и объединяйте/разделяйте пакеты.

Например: можно добавить `$buffer = array()`, использовать `$fd` в качестве ключа для хранения上下文ных данных. Каждый раз, получая данные, необходимоConcatenate字符串, `$buffer[$fd] .= $data`, затем проверять, является ли строка `$buffer[$fd]` полным пакетом данных. По умолчанию один и тот же `$fd` будет распределен одному и тому же `Worker`, так что данные могут быть拼接起来的。 Когда используется [dispatch_mode](/server/setting?id=dispatch_mode) = 3, данные запроса являются прерывистыми, и данные, отправленные с одного и того же `$fd`, могут быть распределены на разные процессы, поэтому вышеупомянутый метод связывания пакетов не может быть использован.

  * **Слушание на нескольких портах, смотрите [эта секция](/server/port)**

Когда главный сервер установлена протокол, дополнительные слушаемые порты по умолчанию наследуют настройки главного сервера. Необходимо явно вызвать метод`set` для пересоздания настроек порта.    

```php
$server = new Swoole\Http\Server("127.0.0.1", 9501);
$port2 = $server->listen('127.0.0.1', 9502, SWOOLE_SOCK_TCP);
$port2->on('receive', function (Swoole\Server $server, $fd, $reactor_id, $data) {
        echo "[#".$server->worker_id."]\tКлиент[$fd]: $data\n";
    });
```

Хотя здесь и был вызван метод`on`, чтобы зарегистрировать callback функции [onReceive](/server/events?id=onreceive), но поскольку не был вызван метод `set`, чтобы пересмотреть протокол главного сервера, новый слушаемый порт `9502` по-прежнему использует HTTP-протокол. Когда вы используете клиент `telnet` для подключения к порту `9502` и отправки строки, сервер не будет активирован для событий [onReceive](/server/events?id=onreceive).

  * **Примечание**

    !> Если не включено автоматическое опция протокола, максимальное количество данных, полученное за один раз с помощью [onReceive](/server/events?id=onreceive), составляет `64K`  
    Если включена опция автоматического обработки протокола, [onReceive](/server/events?id=onreceive) будет получать полные пакеты данных, не exceedый максимальное значение [package_max_length](/server/setting?id=package_max_length)  
    Поддерживается бинарных формат, `$data` может быть двоичной данные
## onPacket

?> **При получении пакета `UDP` будет вызвана эта функция в процессе `worker`.**

```php
function onPacket(Swoole\Server $server, string $data, array $clientInfo);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`string $data`**
      * **Функция**: Содержание полученной данных, которое может быть текстом или двоичным контентом
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`array $clientInfo`**
      * **Функция**: Информация о клиенте, включая `адрес/порт/серверский сокет` и другие данные о клиенте, [см. UDP сервер](/start/start_udp_server)
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Примечание**

    !> Когда сервер одновременно слушает порты `TCP/UDP`, данные, полученные по `TCP`, будут вызывать [onReceive](/server/events?id=onreceive), а пакеты `UDP` будут вызывать `onPacket`. Автоматическое обработку протоколов, такие как `EOF` или `Length` (см. [проблема границы TCP-пакетов](/learn?id=tcp数据包边界问题)), не работает для `UDP` порта, поскольку `UDP` пакеты сами по себе имеют границы сообщения и не требуют дополнительной обработки протокола.


## onClose

?> **После закрытия соединения с `TCP` клиентом, эта функция будет вызвана в процессе `Worker`.**

```php
function onClose(Swoole\Server $server, int $fd, int $reactorId);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $fd`**
      * **Функция**: Фilenдент соединений
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $reactorId`**
      * **Функция**: Из какого `reactor` потока, если закрыто активно, то отрицательное значение
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Подсказки**

    * **Активное закрытие**

      * Когда сервер активно закрывает соединение, это значение будет установлено на `-1`, и можно отличить, было ли закрытие инициировано сервером или клиентом, проверяя, что `$reactorId < 0`.
      * Только когда в `PHP` коде активно вызван метод `close`, это считается активным закрытием.

    * **Сердцебиение**

      * Сердцебиение [сердцебиение](/server/setting?id=heartbeat_check_interval) уведомляет об открытии, и когда закрывается, параметр `$reactorId` в [onClose](/server/events?id=onclose) не равен `-1`.

  * **Примечание**

    !> - Если в функции обратного вызова [onClose](/server/events?id=onclose) происходит критическая ошибка, это приведет к утечке соединений. С помощью команды `netstat` можно увидеть множество `TCP` соединений в состоянии `CLOSE_WAIT`.
    - Независимо от того, закрывает ли соединение клиент или сервер активно вызывает `$server->close()`, это событие будет вызвано. Таким образом, как только соединение закрывается, эта функция будет всегда вызвана.  
    - В [onClose](/server/events?id=onclose) все еще можно вызвать метод [getClientInfo](/server/methods?id=getClientInfo) чтобы получить информацию о соединении, и только после выполнения функции обратного вызова [onClose](/server/events?id=onclose) будет вызван метод `close` для закрытия `TCP` соединения.  
    - Здесь в обратном вызове [onClose](/server/events?id=onclose) означает, что соединение клиента уже закрыто, поэтому нет необходимости выполнять `$server->close($fd)`. Выполнение `$server->close($fd)` в коде приведет к ошибочному предупреждению PHP.


## onTask

?> **Внутри процесса `task` будет вызвана. Процессы `worker` могут использовать функцию [task](/server/methods?id=task) для отправки новых задач в процесс `task_worker`. Текущий [процесс Task](/learn?id=taskworker进程) при вызове обратного вызова функции [onTask](/server/events?id=ontask) переключит состояние процесса на занятый, и в это время он больше не будет принимать новые Task. Когда функция [onTask](/server/events?id=ontask) вернется, она переключит состояние процесса на свободное и продолжит прием новых `Task`.**

```php
function onTask(Swoole\Server $server, int $task_id, int $src_worker_id, mixed $data);
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $task_id`**
      * **Функция**: ID процесса `task`, выполняющего задачу 【`$task_id` и `$src_worker_id` вместе создают глобально уникальный идентификатор, разные процессы `worker` могут отправить задачи с одинаковыми ID】
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $src_worker_id`**
      * **Функция**: ID процесса `worker`, отправляющего задачу 【`$task_id` и `$src_worker_id` вместе создают глобально уникальный идентификатор, разные процессы `worker` могут отправить задачи с одинаковыми ID】
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`mixed $data`**
      * **Функция**: Содержание задачи
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Подсказки**

    * **Начиная с v4.2.12, если включено [task_enable_coroutine](/server/setting?id=task_enable_coroutine), то прототип обратного вызова будет**

      ```php
      $server->on('Task', function (Swoole\Server $server, Swoole\Server\Task $task) {
          var_dump($task);
          $task->finish([123, 'hello']); // Завершить задачу, закончить и вернуть данные
      });
      ```

    * **Отправка результатов выполнения в `worker` процесс**

      * **В функции [onTask](/server/events?id=ontask) `return` строка означает вернуть этот контент в `worker` процесс. В `worker` процессе будет вызвана функция [onFinish](/server/events?id=onfinish), что означает, что отправленная `task` завершена. Конечно, вы также можете вызвать функцию [Swoole\Server->finish()](/server/methods?id=finish) для вызова функции [onFinish](/server/events?id=onfinish), не нужно больше `return`**

      * Переменная `return` может быть любой не `null` переменной PHP

  * **Примечание**

    !> Если функция [onTask](/server/events?id=ontask)遇到一个 критическую ошибку и завершается или ее принудительно убивает внешний процесс, текущая задача будет выброшена, но это не повлияет на другие задачи, находящиеся в очереди


## onFinish

?> **Эта функция обратного вызова будет вызвана в процессе worker, когда задача, отправленная процессом worker, будет завершена в процессе task. [Процесс Task](/learn?id=taskworker进程) будет отправить результаты обработки задачи в процесс worker с помощью метода $server->finish().**

```php
function onFinish(Swoole\Server $server, int $task_id, mixed $data)
```

  * **Параметры** 

    * **`Swoole\Server $server`**
      * **Функция**: Объект Swoole\Server
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`int $task_id`**
      * **Функция**: ID процесса task, выполняющего задачу
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

    * **`mixed $data`**
      * **Функция**: Результат обработки задачи
      * **Значение по умолчанию**: Нет
      * **Другие значения**: Нет

  * **Примечание**

    !> - В событии onTask процесса task нет вызова метода finish или возврата результата, worker процесс не будет вызывать onFinish  
    - Процесс worker, выполняющий логику onFinish, и процесс worker, отправляющий task, являются одним и тем же процессом
## onPipeMessage

?> Когда рабочий процесс получает сообщение [unixSocket](/learn?id=что такоеIPC), отправленное с помощью `$server->sendMessage()`, генерируется событие `onPipeMessage`. Это может происходить с процессов `worker/task`.

```php
функция onPipeMessage(Swoole\Server $server, int $src_worker_id, mixed $message);
```

  * **Параметры**

    * **`Swoole\Server $server`**
      * **Функция**: объект Swoole\Server
      * **По умолчанию**: нет
      * **Другие значения**: нет

    * **`int $src_worker_id`**
      * **Функция**: ID процесса `Worker`, от которого пришло сообщение
      * **По умолчанию**: нет
      * **Другие значения**: нет

    * **`mixed $message`**
      * **Функция**: содержание сообщения, может быть любого PHP типа
      * **По умолчанию**: нет
      * **Другие значения**: нет


## onWorkerError

?> Когда `Worker/Task` процесс сталкивается с excepcией, эта функция будет вызвана в процессе `Manager`.

!> Эта функция в основном используется для оповещения и мониторинга. Как только обнаруживается необычное завершение работы进程 `Worker` (например, из-за серьезной ошибки или崩溃а), это может означать, что был случай, требующий внимания от разработчиков. Для выявления проблемы и ее решения необходимо записать日志 или отправить сообщение с информацией о возникновении ошибки.

```php
функция onWorkerError(Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal);
```

  * **Параметры**

    * **`Swoole\Server $server`**
      * **Функция**: объект Swoole\Server
      * **По умолчанию**: нет
      * **Другие значения**: нет

    * **`int $worker_id`**
      * **Функция**: ID进程 `Worker`, столкнувшийся с ошибкой
      * **По умолчанию**: нет
      * **Другие значения**: нет

    * **`int $worker_pid`**
      * **Функция**: PID进程 `Worker`, столкнувшийся с ошибкой
      * **По умолчанию**: нет
      * **Другие значения**: нет

    * **`int $exit_code`**
      * **Функция**: Код выхода进程, диапазон от `0` до `255`
      * **По умолчанию**: нет
      * **Другие значения**: нет

    * **`int $signal`**
      * **Функция**: Сигнал, вызвавший завершение进程
      * **По умолчанию**: нет
      * **Другие значения**: нет

  * **Частые ошибки**

    * `signal = 11`: указывает на возникновение ошибки сегментации (`segment fault`) в процессе `Worker`. Это может быть следствием ошибки в базовом коде. Для решения проблемы необходимо собрать информацию о `coredump` и провести анализ памяти с помощью `valgrind`. [Подать отчет в desenvolvimento Swoole по этой проблеме](/other/issue).
    * `exit_code = 255`: указывает на `'fatal error'` в процессе `Worker`. Необходимо проверить лог PHP ошибок и найти код PHP, вызвавший проблему, после чего решить проблему.
    * `signal = 9`: означает, что进程 был принудительно `'killed'` системой. По возможности, проверьте, не было ли実行ено 手动ное действие `kill -9`, а также проверите информацию `dmesg` на наличие указаний на `'OOM' (Out of Memory)`.
    * В случае возникновения `'OOM'` и большой потребления памяти: 1. проверьте настройки `Server`, в частности значение [`socket_buffer_size`](/server/setting?id=socket_buffer_size), и не превышает ли его установленные значения; 2. проверите наличие очень больших памяти модулей Swoole, таких как [`Swoole\Table`](/memory/table).


## onManagerStart

?> Это событие触发ается при запуске управления процессом.

```php
функция onManagerStart(Swoole\Server $server);
```

  * **Примечания**

    * В этой обратной связи можно изменить имя управления процессом.
    * В версиях до `4.2.12` в процессе `manager` нельзя устанавливать таймеры, отправлять задачи и использовать корутины.
    * В версиях `4.2.12` и выше в процессе `manager` можно использовать таймеры, основанные на сигналах.
    * В процессе `manager` можно вызвать метод [`sendMessage`](/server/methods?id=sendMessage) для отправки сообщений другим рабочим процессам.

    * **Порядок запуска**

      * Процессы `Task` и `Worker` уже созданы.
      * Состояниеmaster-процесса неизвестно, поскольку manager и master работают параллельно, и невозможно определить, готов ли процесс master к работе, когда срабатывает обратная связь onManagerStart.

    * **BASE режим**

      * В режиме SWOOLE_BASE, если установлены параметры worker_num, max_request, task_worker_num, то на низком уровне будет создан процесс manager для управления рабочими процессами. В результате будут вызваны обратные связи onManagerStart и onManagerStop.


## onManagerStop

?> Это событие срабатывает при завершении управления процессом.

```php
функция onManagerStop(Swoole\Server $server);
```

* **Примечания**

  * Когда активируется onManagerStop, это означает, что процессы `Task` и `Worker` завершили свою работу и были забыты процессом Manager.


## onBeforeReload

?> Это событие активируется перед 'перек LOAD' рабочим процессом и вызвается в процессе Manager.

```php
функция onBeforeReload(Swoole\Server $server);
```

  * **Параметры**

    * **`Swoole\Server $server`**
      * **Функция**: объект Swoole\Server
      * **По умолчанию**: нет
      * **Другие значения**: нет


## onAfterReload

?> Это событие активируется после 'перек LOAD' рабочим процессом и вызвается в процессе Manager.

```php
функция onAfterReload(Swoole\Server $server);
```

  * **Параметры**

    * **`Swoole\Server $server`**
      * **Функция**: объект Swoole\Server
      * **По умолчанию**: нет
      * **Другие значения**: нет


## Порядок выполнения событий

* Все обратные связи событий происходят после запуска `$server->start`.
* Последний событии при закрытии и завершении программы服务器 - это `onShutdown`.
* После успешного запуска, `onStart/onManagerStart/onWorkerStart` выполняются параллельно в разных процессах.
* `onReceive/onConnect/onClose` срабатывают в `Worker` процессе.
* В момент запуска/закрытия `Worker/Task` процессов, один раз вызываются `onWorkerStart/onWorkerStop`.
* [onTask](/server/events?id=ontask) события происходят только в [task进程中](/learn?id=taskworker进程).
* [onFinish](/server/events?id=onfinish) события происходят только в `worker` процессе.
* Порядок выполнения `onStart/onManagerStart/onWorkerStart` трех событий не определен.

## Объектно-ориентированный стиль

После активации [event_object](/server/setting?id=event_object), параметры следующих обратных связей событий изменятся.

* client connection [onConnect](/server/events?id=onconnect)
```php
$server->on('Connect', function (Swoole\Server $serv, Swoole\Server\Event $object) {
    var_dump($object);
});
```

* menerima данные [onReceive](/server/events?id=onreceive)
```php
$server->on('Receive', function (Swoole\Server $serv, Swoole\Server\Event $object) {
    var_dump($object);
});
```

* Соединение закрыто [onClose](/server/events?id=onclose)
```php
$server->on('Close', function (Swoole\Server $serv, Swoole\Server\Event $object) {
    var_dump($object);
});
```


* UDP пакет принят [onPacket](/server/events?id=onpacket)
```php
$server->on('Packet', function (Swoole\Server $serv, Swoole\Server\Packet $object) {
    var_dump($object);
});
```


* межпроцессный общение [onPipeMessage](/server/events?id=onpipemessage)
```php
$server->on('PipeMessage', function (Swoole\Server $serv, Swoole\Server\PipeMessage $msg) {
    var_dump($msg);
    $object = $msg->data;
    $serv->sendto($object->address, $object->port, $object->data, $object->server_socket);
});
```


* Ошибка в процессе [onWorkerError](/server/events?id=onworkererror)
```php
$serv->on('WorkerError', function (Swoole\Server $serv, Swoole\Server\StatusInfo $info) {
    var_dump($info);
});
```


*task进程 принимает задание [onTask](/server/events?id=ontask)
```php
$server->on('Task', function (Swoole\Server $serv, Swoole\Server\Task $task) {
    var_dump($task);
});
```


*worker进程 получает результат обработки задания отtask进程 [onFinish](/server/events?id=onfinish)
```php
$server->on('Finish', function (Swoole\Server $serv, Swoole\Server\TaskResult $result) {
    var_dump($result);
});
```

* [Swoole\Server\Event](/server/event_class)
* [Swoole\Server\Packet](/server/packet_class)
* [Swoole\Server\PipeMessage](/server/pipemessage_class)
* [Swoole\Server\StatusInfo](/server/statusinfo_class)
* [Swoole\Server\Task](/server/task_class)
* [Swoole\Server\TaskResult](/server/taskresult_class)
