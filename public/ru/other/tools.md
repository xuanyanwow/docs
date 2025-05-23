# Инструменты использования


## yasd

[yasd](https://github.com/swoole/yasd)

Одиночный инструмент для отладки, который может использоваться в среде совместных процессов Swoole, поддерживает отладку в IDE и в командной строке.


## tcpdump

Когда вы отлаживаете программы сетевого общения, tcpdump является необходимым инструментом. tcpdump очень мощный, он позволяет видеть каждую деталь сетевого общения. Например, для TCP можно видеть трехстороннее握手, отправку данных PUSH/ACK, четырехстороннее прощание close, все детали. Включая количество字节 в каждом сетевом пакете и время.


### Как использовать

Самый простой пример использования:

```shell
sudo tcpdump -i any tcp port 9501
```
* -i параметр указывает сетевую карту, any означает все сетевые карты
* tcp указывает на прослушивание только по TCP протоколу
* port указывает на прослушиваемый порт

!> Для tcpdump требуется root привилегии; если нужно видеть содержание коммуникационных данных, можно добавить параметр `-Xnlps0`, для других параметров смотрите статьи в интернете


### Результаты выполнения

```
13:29:07.788802 IP localhost.42333 > localhost.9501: Флаги [S], последовательность 828582357, окно 43690, опции [MSS 65495,sackOK,TS значение 2207513 ecr 0,nop,wscale 7], длина 0
13:29:07.788815 IP localhost.9501 > localhost.42333: Флаги [S.], последовательность 1242884615, ответ 828582358, окно 43690, опции [MSS 65495,sackOK,TS значение 2207513 ecr 2207513,nop,wscale 7], длина 0
13:29:07.788830 IP localhost.42333 > localhost.9501: Флаги [.], ответ 1, окно 342, опции [nop,nop,TS значение 2207513 ecr 2207513], длина 0
13:29:10.298686 IP localhost.42333 > localhost.9501: Флаги [P.], последовательность 1:5, ответ 1, окно 342, опции [nop,nop,TS значение 2208141 ecr 2207513], длина 4
13:29:10.298708 IP localhost.9501 > localhost.42333: Флаги [.], ответ 5, окно 342, опции [nop,nop,TS значение 2208141 ecr 2208141], длина 0
13:29:10.298795 IP localhost.9501 > localhost.42333: Флаги [P.], последовательность 1:13, ответ 5, окно 342, опции [nop,nop,TS значение 2208141 ecr 2208141], длина 12
13:29:10.298803 IP localhost.42333 > localhost.9501: Флаги [.], ответ 13, окно 342, опции [nop,nop,TS значение 2208141 ecr 2208141], длина 0
13:29:11.563361 IP localhost.42333 > localhost.9501: Флаги [F.], последовательность 5, ответ 13, окно 342, опции [nop,nop,TS значение 2208457 ecr 2208141], длина 0
13:29:11.563450 IP localhost.9501 > localhost.42333: Флаги [F.], последовательность 13, ответ 6, окно 342, опции [nop,nop,TS значение 2208457 ecr 2208457], длина 0
13:29:11.563473 IP localhost.42333 > localhost.9501: Флаги [.], ответ 14, окно 342, опции [nop,nop,TS значение 2208457 ecr 2208457], длина 0
```
* `13:29:11.563473` время с точностью до микросекунды
*  localhost.42333 > localhost.9501 указывает направление коммуникации, 42333 - это клиент, 9501 - это сервер
* [S] означает, что этоSYN запрос
* [.] означает, что это ACK подтверждения пакета, (клиент)SYN->(сервер)SYN->(клиент)ACK - это процесс трехстороннего рукопожатия
* [P] означает, что это отправка данных, которая может происходить от сервера к клиенту или от клиента к серверу
* [F] означает, что это FIN пакет, это операция закрытия соединения, и может быть инициирована как клиентом, так и сервером
* [R] означает, что это RST пакет, который выполняет ту же функцию, что и F пакет, но RST указывает на то, что при закрытии соединения все еще есть неоп xửанные данные. Можно понимать как принудительное отключение соединения
* win 342 означает размер скользящего окна
* длина 12 указывает на размер пакета данных


## strace

strace может отслеживать выполнение системных вызовов, и после возникновения проблемы в программе его можно использовать для анализа и отслеживания проблемы.

!> На FreeBSD/MacOS можно использовать truss


### Как использовать

```shell
strace -o /tmp/strace.log -f -p $PID
```

* -f означает отслеживание многопутевого и многопроцессного выполнения, без `-f` параметр невозможно поймать работу дочерних процессов и потоков
* -o означает вывод результатов в файл
* -p $PID, указывает ID процесса для отслеживания, который можно увидеть с помощью ps aux
* -tt печатает время выполнения системного вызова, точное до микросекунды
* -s ограничивает длину печати строк, например, данные, полученные с вызова recvfrom, по умолчанию печатает только 32 байта
* -c мгновенно подсчитывает время выполнения каждого системного вызова
* -T печатает время выполнения каждого системного вызова


## gdb

GDB - это мощный инструмент для отладки программ под UNIX, разработанный GNU, который может использоваться для отладки программ, разработанных на C/C++, PHP и Swoole написаны на C языке, поэтому GDB может использоваться для отладки программ PHP+Swoole.

Отладка GDB - это интерактивный командный процесс, необходимо овладеть основными командами.


### Как использовать

```shell
gdb -p进程ID
gdb php
gdb php core
```

У GDB есть 3 способа использования:

* Отслеживание运行的 PHP программы с использованием gdb -p进程ID
* Использование GDB для выполнения и отладки PHP программы, для отладки используйте gdb php -> run server.php
* После возникновения coredump PHP программы используйте gdb для загрузки образа памяти core для отладки gdb php core

!> Если в переменной окружения PATH нет php, при использовании GDB необходимо указать абсолютный путь, например, gdb /usr/local/bin/php


### Основные команды

* `p`: print, печатает значение C переменной
* `c`: continue, продолжает выполнение остановленной программы
* `b`: breakpoint, устанавливает точку остановки, можно устанавливать по имени функции, например, `b zif_php_function`, а также по номеру строки исходного кода, например, `b src/networker/Server.c:1000`
* `t`: thread, переключает поток, если в процессе есть несколько потоков, можно использовать команду t для переключения на разные потоки
* `ctrl + c`: прерывает текущую Executing программу, используется в сочетании с командой c
* `n`: next, выполняет следующую строку, однопутевая отладка
* `info threads`: показывает все线程, находящиеся в exécении
* `l`: list, показывает исходный код, можно использовать `l функция名和` или `l номер строки`
* `bt`: backtrace, показывает стек вызовов во время выполнения
* `finish`: завершает текущую функцию
* `f`: frame, используется в сочетании с bt, позволяет переключиться на определенную глубину стека вызовов функции
* `r`: run, запускает программу


## zbacktrace

zbacktrace - это пользовательский командный指令 для GDB, предоставленный пакетом исходных кодов PHP, функция которого похожа на bt, но в отличие от bt, стек вызовов, видимый с помощью zbacktrace, является стеком вызовов PHP функций, а не C функций.

Скачайте php-src, распакуйте его и найдите в корневом каталоге файл `.gdbinit`, затем в оболочке GDB введите

```shell
source .gdbinit
zbacktrace
```
В `.gdbinit` также предоставлены другие команды, которые можно использовать для просмотра исходного кода и получения подробной информации.

#### Использование gdb+zbacktrace для отслеживания проблем с бесконечным циклом

```shell
gdb -p进程ID
```

* Используйте инструмент ps aux, чтобы найти ID Worker процесса, который попал в бесконечный цикл
* Используйте gdb для отслеживания указанного процесса
* Повторяйте команды `ctrl + c`, `zbacktrace`, `c`, чтобы увидеть, в какой части PHP-кода происходит цикл
* Найдите соответствующий PHP-код и решите проблему

## lsof

Платформа Linux предоставляет инструмент `lsof`, который позволяет 查看 какие файлы открыты процессом. Он может использоваться для отслеживания всех открытых сокетов, файлов и ресурсов рабочими процессами swoole.

### Как использовать

```shell
lsof -p [PID]
```

### Результаты выполнения

```shell
lsof -p 26821
lsof: WARNING: can't stat() tracefs file system /sys/kernel/debug/tracing
      Output information may be incomplete.
COMMAND   PID USER   FD      TYPE             DEVICE SIZE/OFF    NODE NAME
php     26821  htf  cwd       DIR                8,4     4096 5375979 /home/htf/workspace/swoole/examples
php     26821  htf  rtd       DIR                8,4     4096       2 /
php     26821  htf  txt       REG                8,4 24192400 6160666 /opt/php/php-5.6/bin/php
php     26821  htf  DEL       REG                0,5          7204965 /dev/zero
php     26821  htf  DEL       REG                0,5          7204960 /dev/zero
php     26821  htf  DEL       REG                0,5          7204958 /dev/zero
php     26821  htf  DEL       REG                0,5          7204957 /dev/zero
php     26821  htf  DEL       REG                0,5          7204945 /dev/zero
php     26821  htf  mem       REG                8,4   761912 6160770 /opt/php/php-5.6/lib/php/extensions/debug-zts-20131226/gd.so
php     26821  htf  mem       REG                8,4  2769230 2757968 /usr/local/lib/libcrypto.so.1.1
php     26821  htf  mem       REG                8,4   162632 6322346 /lib/x86_64-linux-gnu/ld-2.23.so
php     26821  htf  DEL       REG                0,5          7204959 /dev/zero
php     26821  htf    0u      CHR             136,20      0t0      23 /dev/pts/20
php     26821  htf    1u      CHR             136,20      0t0      23 /dev/pts/20
php     26821  htf    2u      CHR             136,20      0t0      23 /dev/pts/20
php     26821  htf    3r      CHR                1,9      0t0      11 /dev/urandom
php     26821  htf    4u     IPv4            7204948      0t0     TCP *:9501 (LISTEN)
php     26821  htf    5u     IPv4            7204949      0t0     UDP *:9502 
php     26821  htf    6u     IPv6            7204950      0t0     TCP *:9503 (LISTEN)
php     26821  htf    7u     IPv6            7204951      0t0     UDP *:9504 
php     26821  htf    8u     IPv4            7204952      0t0     TCP localhost:8000 (LISTEN)
php     26821  htf    9u     unix 0x0000000000000000      0t0 7204953 type=DGRAM
php     26821  htf   10u     unix 0x0000000000000000      0t0 7204954 type=DGRAM
php     26821  htf   11u     unix 0x0000000000000000      0t0 7204955 type=DGRAM
php     26821  htf   12u     unix 0x0000000000000000      0t0 7204956 type=DGRAM
php     26821  htf   13u  a_inode               0,11        0    9043 [eventfd]
php     26821  htf   14u     unix 0x0000000000000000      0t0 7204961 type=DGRAM
php     26821  htf   15u     unix 0x0000000000000000      0t0 7204962 type=DGRAM
php     26821  htf   16u     unix 0x0000000000000000      0t0 7204963 type=DGRAM
php     26821  htf   17u     unix 0x0000000000000000      0t0 7204964 type=DGRAM
php     26821  htf   18u  a_inode               0,11        0    9043 [eventpoll]
php     26821  htf   19u  a_inode               0,11        0    9043 [signalfd]
php     26821  htf   20u  a_inode               0,11        0    9043 [eventpoll]
php     26821  htf   22u     IPv4            7452776      0t0     TCP localhost:9501->localhost:59056 (ESTABLISHED)
```

*so файлы - это динамические библиотеки, загруженные процессом
*IPv4/IPv6 TCP (LISTEN) - это порты, на которых слушает сервер
*UDP - это порты, на которых слушает сервер UDP
*unix type=DGRAM - это [unixSocket](/learn?id=什么是IPC), созданный процессом
*IPv4 (ESTABLISHED) - это TCP-клиент, соединенный с сервером, включает в себя IP-адрес и порт клиента, а также статус (ESTABLISHED)
*9u / 10u - это значение fd (файлового дескриптора) этого файла
*Для большего количества информации можно обратиться к手册у lsof

## perf

Инструмент `perf` - это очень мощный динамический инструмент отслеживания, предоставляемый ядром Linux. Команду `perf top` можно использовать для реального времени анализа проблем с производительности выполняемого программы. В отличие от инструментов, таких как `callgrind`, `xdebug`, `xhprof`, `perf` не требует изменения кода для экспорта результатов профилирования в файлы.

### Как использовать

```shell
perf top -p [PID]
```

### Результаты вывода

![Результаты вывода perf top](../_images/other/perf.png)

Результаты `perf` четко показывают время выполнения каждой C-функции в процессе в реальном времени, что позволяет понять, какая C-функция занимает много CPU ресурсов.

Если вы хорошо знакомы с Zend VM и некоторые функции Zend вызываются слишком часто, это может означать, что ваша программа использует некоторые функции в больших количествах, что приводит к высокому использованию CPU. В таких случаях можно сосредоточиться на оптимизации.
