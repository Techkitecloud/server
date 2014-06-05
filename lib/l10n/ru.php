<?php
$TRANSLATIONS = array(
"App \"%s\" can't be installed because it is not compatible with this version of ownCloud." => "Приложение \"%s\" нельзя установить, так как оно не совместимо с текущей версией ownCloud.",
"No app name specified" => "Не выбрано имя приложения",
"Help" => "Помощь",
"Personal" => "Личное",
"Settings" => "Конфигурация",
"Users" => "Пользователи",
"Admin" => "Admin",
"Failed to upgrade \"%s\"." => "Не смог обновить \"%s\".",
"Unknown filetype" => "Неизвестный тип файла",
"Invalid image" => "Изображение повреждено",
"web services under your control" => "веб-сервисы под вашим управлением",
"No source specified when installing app" => "Не указан источник при установке приложения",
"No href specified when installing app from http" => "Не указан атрибут href при установке приложения через http",
"No path specified when installing app from local file" => "Не указан путь при установке приложения из локального файла",
"Archives of type %s are not supported" => "Архивы %s не поддерживаются",
"Failed to open archive when installing app" => "Не возможно открыть архив при установке приложения",
"App does not provide an info.xml file" => "Приложение не имеет файла info.xml",
"App can't be installed because of not allowed code in the App" => "Приложение невозможно установить. В нем содержится запрещенный код.",
"App can't be installed because it is not compatible with this version of ownCloud" => "Приложение невозможно установить. Не совместимо с текущей версией ownCloud.",
"App can't be installed because it contains the <shipped>true</shipped> tag which is not allowed for non shipped apps" => "Приложение невозможно установить. Оно содержит параметр <shipped>true</shipped> который не допустим для приложений, не входящих в поставку.",
"App can't be installed because the version in info.xml/version is not the same as the version reported from the app store" => "Приложение невозможно установить. Версия в info.xml/version не совпадает с версией заявленной в магазине приложений",
"App directory already exists" => "Папка приложения уже существует",
"Can't create app folder. Please fix permissions. %s" => "Не удалось создать директорию. Исправьте права доступа. %s",
"Application is not enabled" => "Приложение не разрешено",
"Authentication error" => "Ошибка аутентификации",
"Token expired. Please reload page." => "Токен просрочен. Перезагрузите страницу.",
"Unknown user" => "Неизвестный пользователь",
"Files" => "Файлы",
"Text" => "Текст",
"Images" => "Изображения",
"%s enter the database username." => "%s введите имя пользователя базы данных.",
"%s enter the database name." => "%s введите имя базы данных.",
"%s you may not use dots in the database name" => "%s Вы не можете использовать точки в имени базы данных",
"MS SQL username and/or password not valid: %s" => "Имя пользователя и/или пароль MS SQL не подходит: %s",
"You need to enter either an existing account or the administrator." => "Вы должны войти или в существующий аккаунт или под администратором.",
"MySQL/MariaDB username and/or password not valid" => " Имя пользователя и/или пароль MySQL/MariaDB не действительны.",
"DB Error: \"%s\"" => "Ошибка БД: \"%s\"",
"Offending command was: \"%s\"" => "Вызываемая команда была: \"%s\"",
"MySQL/MariaDB user '%s'@'localhost' exists already." => "Пользователь MySQL '%s'@'localhost' уже существует.",
"Drop this user from MySQL/MariaDB" => "Удалить данного участника из MySQL/MariaDB",
"MySQL/MariaDB user '%s'@'%%' already exists" => "Пользователь MySQL '%s'@'%%' уже существует.",
"Drop this user from MySQL/MariaDB." => "Удалить данного участника из MySQL/MariaDB.",
"Oracle connection could not be established" => "соединение с Oracle не может быть установлено",
"Oracle username and/or password not valid" => "Неверное имя пользователя и/или пароль Oracle",
"Offending command was: \"%s\", name: %s, password: %s" => "Вызываемая команда была: \"%s\", имя: %s, пароль: %s",
"PostgreSQL username and/or password not valid" => "Неверное имя пользователя и/или пароль PostgreSQL",
"Set an admin username." => "Установить имя пользователя для admin.",
"Set an admin password." => "становит пароль для admin.",
"Your web server is not yet properly setup to allow files synchronization because the WebDAV interface seems to be broken." => "Ваш веб сервер до сих пор не настроен правильно для возможности синхронизации файлов, похоже что проблема в неисправности интерфейса WebDAV.",
"Please double check the <a href='%s'>installation guides</a>." => "Пожалуйста, дважды просмотрите <a href='%s'>инструкции по установке</a>.",
"%s shared »%s« with you" => "%s поделился »%s« с вами",
"Sharing %s failed, because the user %s is the item owner" => "Не удалось установить общий доступ для %s, пользователь %s уже  является владельцем",
"Sharing %s failed, because the user %s does not exist" => "Не удалось установить общий доступ для %s, пользователь %s не существует.",
"Sharing %s failed, because this item is already shared with %s" => "Не удалось установить общий доступ для %s ,в виду того что, объект уже находиться в общем доступе с %s",
"Sharing %s failed, because the group %s does not exist" => "Не удалось установить общий доступ для %s, группа %s не существует.",
"Sharing %s failed, because %s is not a member of the group %s" => "Не удалось установить общий доступ для %s, %s не является членом группы %s",
"Sharing %s failed, because sharing with links is not allowed" => "Не удалось установить общий доступ для %s, потому что обмен со ссылками не допускается",
"Share type %s is not valid for %s" => "Такой втд общего доступа как %s не допустим для %s",
"Setting permissions for %s failed, because the permissions exceed permissions granted to %s" => "Настройка прав доступа для %s невозможна, поскольку права доступа превышают предоставленные права доступа %s",
"Setting permissions for %s failed, because the item was not found" => "Не удалось произвести настройку прав доступа для %s , элемент не был найден.",
"Could not find category \"%s\"" => "Категория \"%s\"  не найдена",
"seconds ago" => "несколько секунд назад",
"_%n minute ago_::_%n minutes ago_" => array("%n минута назад","%n минуты назад","%n минут назад"),
"_%n hour ago_::_%n hours ago_" => array("%n час назад","%n часа назад","%n часов назад"),
"today" => "сегодня",
"yesterday" => "вчера",
"_%n day go_::_%n days ago_" => array("%n день назад","%n дня назад","%n дней назад"),
"last month" => "в прошлом месяце",
"_%n month ago_::_%n months ago_" => array("%n месяц назад","%n месяца назад","%n месяцев назад"),
"last year" => "в прошлом году",
"years ago" => "несколько лет назад",
"Only the following characters are allowed in a username: \"a-z\", \"A-Z\", \"0-9\", and \"_.@-\"" => "Только следующие символы допускаются в имени пользователя: \"a-z\", \"A-Z\", \"0-9\", и \"_.@-\"",
"A valid username must be provided" => "Укажите правильное имя пользователя",
"A valid password must be provided" => "Укажите валидный пароль",
"The username is already being used" => "Имя пользователя уже используется"
);
$PLURAL_FORMS = "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);";
