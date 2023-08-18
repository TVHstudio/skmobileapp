Установка
---------

1. В самом начале нужно установить flutter `SDK`, как это сделать описано - https://flutter.dev/docs/get-started/install/macos 
(можете выбрать свою ось). Для установки можно  выбрать любую директорию для этого не обязательно устанавливать `SDK` в папку с вашими сайтами.
По умолчанию можно ставить его в `~/flutter`

2. Затем нужно не забыть прописать путь к `flutter`, подробнее : https://flutter.dev/docs/get-started/install/macos#update-your-path
К примеру на моем маке я сделал (открыв файл `.bash_profile`):

```
export PATH=$PATH:/Users/esase/flutter/bin
```

3. Нужно добавить поддержку веб версии во флаттер (по умолчанию ее нет), выполняем комманды в директории с приложением `/application`:

```
flutter create .
```

4. Чтобы убедиться, что все работает можно выполнить комманду: `flutter doctor` (Нужно иметь установленные **Xcode** и **Android studio**) ну или что то одно.

5. Также нужно установить `Visual Studio Code` редактор для полноценной работы с приложением + нужно добавить в него некоторые плагины. Как это сделать описано здесь - https://flutter.dev/docs/get-started/editor?tab=vscode

6. Изучите конфиг файл - `application/config/application.config` который содержит все возможные
настройки приложения.

7. В самый первый раз перед запуском приложения нужно выполнить комманду `./app.sh prepare` которая генерит нужные конфиги, а также добавляет сплеш скрин в IOS и Android затем выполните команду `./app.sh build_dart` которая на основе ваших файлов генерит дополнительные файлы, для последующей работы приложения


Запуск WEB
----------

Откройте `Visual Studio Code`. При открытии файла `application/main.dart` автоматически появляется менюшка 
сверху справа с возможностью запуска приложения. Для того, чтобы запустить веб версию нужно в меню снизу справа 
нажать на кнопку `device` и выбрать `Chrome web`. После этого смело запускайте проект.

Подробнее: https://flutter.dev/docs/get-started/web

Компиляция WEB
--------------

Для того, чтобы сбилдить приложение нужно выполнить комманду - `./app.sh build_web`.
Сбилденный контент появится в папке `application/build/web` его и нужно использовать в качестве `PWA` приложения.

Подробнее: https://flutter.dev/docs/get-started/web

Запуск IOS
----------

1. Прежде чем запускать IOS нужно установить `Xode` и выполнить в консоли несколько комманд:
https://flutter.dev/docs/get-started/install/macos#install-xcode

2. Запустите симулятор коммандой  - `open -a Simulator`

3. В  `Visual Studio Code` в меню снизу есть пункт - выбор  `device` нужно нажать на него и выбрать `Iphone`
ну и запустить приложение. В первый раз это будет долго так как `flutter` скачивает нужные либы

Компиляция IOS
--------------

Для того, чтобы сбилдить приложение и нужно выполнить комманду - `./app.sh build_ios`.
Сбилденный контент появится в папке `application/build/ios`.

Компиляция Android
------------------

Для того, чтобы сбилдить приложение и нужно выполнить комманду - `./app.sh build_android`.
Сбилденный контент появится в папке `application/build/ios`.

Настройка Deep Links
--------------------

на сайт клиента в корень нужно залить два файла в папку `.well-known`:

1. `apple-app-site-association` 

```
{
    "applinks": {
        "apps": [],
        "details": [{
            "appID": "XBKQ82P782.com.esase.com",
            "paths": ["*"]
        }]
    }
```

`XBKQ82P782.com.esase.com"` - это teamId взятый с настроек апла  (https://support.customchurchapps.com/hc/article_attachments/360050899593/Screen_Shot_2018-04-18_at_10.45.16_AM.png) 
+ бандл id  приложения

2. `assetlinks.json`

```
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.esase.com",
    "sha256_cert_fingerprints":
    ["5C:62:E2:E1:6F:1C:24:72:E4:BF:E0:91:46:75:59:4A:7F:E4:93:A2:30:A8:DE:17:02:AE:2C:E7:2B:C4:C8:C2"]
  }
}]
```

здесь нужно заменить только бандл клиента `com.esase.com` + забрать с файла `jks` sha256, которым вы подписываете приложения в андроид студио. Как это сделать? выполняете комманду:
`keytool -list -v -keystore my-release-key.keystore` где `my-release-key.keystore` путь до вашего jks файла

подробнее здесь - https://developer.android.com/training/app-links/verify-site-associations#web-assoc

Настройка Firebase
------------------

Firebase используется для авторизиции, пуш уведомлений, admob, и аналитики в проекте. Сам проект уже настроен на использование дефолтных
настроек для firebase которые должны быть переопределенны для каждого клиента индивидуально 

1. Создаем новое приложение в консоли гугл - https://console.firebase.google.com/u/0/ (для Android см. информацию ниже)
2. На странице настроек добавляем 3 платформы `Web`, `Ios`, `Android` (не забываем сразу указывать корректный `bandle id`)
3.  Скачиваем файл `GoogleService-Info.plist` и ложим его в `config/platform/ios` заменяя дефолтный
4.  Скачиваем файл `google-services.json` и ложим его в `config/platform/android` заменяя дефолтный
5. Настройки с платформы `Web` нужно в ручную скопировать в файл `config/application.config` заменяя дефолтные настройки
6. Необходимо включить опцию - `Multiple accounts per email address` на странице `Firebase` -> `App` -> `Authentication` -> `Sign in Methods` (для того чтобы дать возможность пользователяем логинитья с одного и тогоже майл
с разных провайдеров - `Facebook`, `Twitter`, etc)
7. Не забываем настроить `config/application.config` (`bundle id`, `api url`, итд)
8. Также необходимо активировать аналитику для проекта. При успешной активации аналитики
вы должны увидеть в настройках для Веб что то типа - `measurementId: "G-8D8LQHSE5R"`
9. Инструкция по настройки apple connect




Процесс настройки Apple Sign In:
--------------------------------

Открываем страницу https://developer.apple.com/account/resources/identifiers/serviceId/add/
Даем Description например "Firebase Apple Sign In" и Identifier например "com.esase"
Чекаем Sign In with Apple и жмем Configure
В селекте Primary App ID выбираем созданный бандл. Если там ничего нет - тогда надо:
Открываем страницу https://developer.apple.com/account/resources/identifiers/bundleId/add
Заполняем "Description" и "Bundle ID"
Внизу чекаем Sign In with Apple и жмем Configure
Enable as a primary App ID должна быть выбрана, жмем Save
Вверху жмем Continue, потом Register
В пункте 4 выбираем новый App ID
В поле Web Domain вводим домен сайте (без схемы http:// или https://)

```
примеры как настроено у нас :

domain: skmobile-new.skalfa.com
```

конечно нужно ввести домен клиента

В поле Return URLs указываем 2 urls

```
примеры как настроено у нас :

redirect Urls:

https://skmobile-new.skalfa.com/firebaseauth/android-redirect?package=com.esase.com
https://skmobile-new.firebaseapp.com/__/auth/handler
```

`skmobile-new.skalfa.com` - введите клиенсткий домен
`com.esase.com` - введите клиенсткий bundle name
`skmobile-new` - проект из файрбейза


Жмем Save
Вверху жмем Continue, потом Register
Кликаем на ново-созданный Service Id,


Открываем страницу https://developer.apple.com/account/resources/authkeys/add
Даем Key Name, чекаем Sign In with Apple, жмем Configure
В Primary App ID выбираем App ID из пункта 4
Жмем Save, потом Continue и Register
Жмем Download и копируем в буфер Key ID
Открываем страницу консоли Firebase -> Authentication -> Sign-in methods
Кликаем на Apple и активируем
В поле Services ID заполняем Identifier из пункта номер 2
Кликаем на OAuth code flow configuration (optional)
Заполняем Apple team ID и Key ID (который мы скопировали в буфер в пункте 19)
Открываем файл из пункта 19, копируем содержимое и вводим в поле Private key
Жмем Save

И последнее чтобы иметь возможность получать письма для клиентов которые
используют анонимные майл адреса нужно корректно настроить почтовый сервер и прописать его в настройках апл

https://developer.apple.com/account/resources/services/configure


### Создание приложения Firebase (Android)

На странице создания Android-приложения в Firebase необходимо заполнить пункт "Debug signing certificate SHA-1".

![](.static/create_firebase_app_android.png)

Хэш отладочного сертификата (как и сам сертификат) уникален для каждого компьютера, на котором собирается отладочная версия приложения. Данный сертификат генерируется при установке Android SDK и сохраняетса под именем `androiddebugkey`.

Для хеша сертификата разработчика необходимо вывести информацию о нем:

```
keytool -list -v -alias androiddebugkey -keystore ~/.android/debug.keystore
```

Данная команда попросит у вас пароль от хранилища сертификатов. Пароль по-умолчанию: `android`.

### Настройка Facebook, Twitter, Google providers

Переходим `Firebase` -> `App` -> `Authentication` -> `Sign in Methods`

1. Активируем `Google` большие ничего не требуеться 
2. Активируем `Facebook`, чтобы корректно его настроить вам сперва нужно создать приложение
в facebook отуда копируем `App ID` и `App Secret`

В самом приложении `Facebook` необходимо указать:

```
[Valid OAuth Redirect URIs]: https://PROJECT_NAME.firebaseapp.com/__/auth/handler
(Название проекта (PROJECT_NAME) можно взять с Firebase -> App -> Settings -> General)

Для того, чтобы появились настройки IOS и Android нужно сперва активировать эти платформы:
Facebook -> App -> quickstart

IOS
---

[Bundle ID]: YOUR_BUNDLE - укажите бандл (название) вашего приложения 
(Bundle проекта можно взять с Firebase -> App -> Settings -> General -> IOS -> Bundle ID)

Android
-------

a. [Google Play Package Name]: YOUR_BUNDLE - укажите бандл (название) вашего приложения 
(Bundle проекта можно взять с Firebase -> App -> Settings -> General -> Android -> Package name)

b. [Class Name]: YOUR_BUNDLE.MainActivity - где взять Bundle описано выше

c. [Key Hashes]: хэши ключей подписи приложения для сабмита в стор. Генерируются в консоли разработчика Google Play, необходимы для работы аутентификации Facebook в релизных сборках.
```

3. Активируем `Twitter`, чтобы корректно его настроить вам сперва нужно создать приложение в 
в twitter  отуда копируем `Api key` и `Api Secret` из `Apps` -> `Keys` and `Tokens` -> `Api key`

В самом приложении `Twitter` необходимо указать (`Twitter` -> `Apps` -> `App` -> `App details`):

```
[Callback URLs]: 

https://PROJECT_NAME.firebaseapp.com/__/auth/handler
twittersdk://
twitterkit-API_KEY://

(Название проекта (PROJECT_NAME) можно взять с Firebase -> App -> Settings -> General)
API_KEY - копируем из настроек самого витера Twitter -> Apps -> Keys and Tokens -> Api key

```


4. Запусукаем комманду `./app.sh prepare` которая скопирует конфиги в нужные места. После этого степа все должно работать

### Push notifications

Push-уведомления на всех платформах отправляются через Firebase.

#### Общая настройка

В Firebase Console в левом верхнем углу нужно найти надпись "Project Overview" и нажать на шестеренку рядом с ней. В открывшемся меню нажать на "Project settings".

![](.static/project_settings.png)

На открывшейся странице нужно прокрутить вниз и найти созданное *веб-приложение* (не iOS и не Android), затем нажать на него. На появившемся виджете с параметрами будет пункт "Firebase SDK snippet", на котором будет кусок кода, похожий на этот:

```js
var firebaseConfig = {
    apiKey: "...",
    authDomain: "...",
    projectId: "...",
    storageBucket: "...",
    messagingSenderId: "...",
    measurementId: "...",
    appId: "..."
};
```

Значение каждого ключа необходимо скопировать в `application.config.tmpl` в секцию `PWA FIREBASE`. Легко определить какие значения куда копировать, посмотрев на их названия в конфиге. Например, `apiKey` нужно скопировать в `PWA_FIREBASE_API_KEY`, `authDomain` в `PWA_FIREBASE_AUTH_DOMAIN`, и т. д.

Когда это будет сделано, необходимо проскроллить страницу до конца вверх и перейти на вкладку "Cloud Messaging."

На открывшейся странице внизу будет пункт "Web configuration", а под ним виджет, в котором нужно выбрать "Web Push certificates."

С правой стороны будет таблица со столбцами "Key pair" и "Date added".

![](./.static/web_push_certificates.png)

Этот ключ называется VAPID key. Его необходимо скопировать в конфиг в параметр `PWA_FIREBASE_VAPID_KEY`.

#### Настройка iOS

В первую очередь нужно открыть проект в Xcode, в левом меню выбрать Runner, на открывшейся странице в списке Targets выбрать Runner, перейти на вкладку "Signing & Capabilities", и убедиться, что в списке возможностей приложения добавлено "Push notifications."

Если этой capability нет, нужно нажать на кнопку "+ Capability", найти в поиске "Push notifications" и добавить ее.

![](./.static/push_notifications_capability.png)

Также необходимо добавить capability "Background Modes" если она отсутствует, и убедиться, что разрешены "Background fetch" и "Remote notifications."

![](./.static/background_modes_capability.png)

Затем нужно зайти в Apple Developer account, в левом меню перейти на "Certificates, Identifiers & Profiles", на открывшейся странице в левом меню выбрать пункт "Keys."

Там нужно создать новый ключ, нажав на + рядом с надписью Keys, присвоить ему какое-нибудь имя, и в появившемся списке выбрать "Apple Push Notifications service (APNs)", затем нажать Continue > Register.

![](./.static/certificates.png)

После создания ключа необходимо скопировать его Key ID и скачать сам ключ, нажав на кнопку Download. Это можно сделать только один раз! После закрытия этой страницы, скачать ключ будет невозможно.

Желательно сохранять ключ каждого клиента в отдельной папке, при этом ключи не должны попасть в чужие руки (в репозиторий коммитить нельзя!)

![](./.static/download_key.png)

Затем в Firebase Console в Project settings > Cloud Messaging нужно найти виджет "iOS app configuration" нужно залить скачанный ключ.

Для этого нужно нажать на кнопку "Upload", в появившемся окне в "APNs auth key" выбрать скачанный ключ, ввести скопированный ранее ID ключа, и нажать на кнопку Upload.

![](./.static/upload_key.png)

Мануал на английском расположен по ссылке [https://firebase.flutter.dev/docs/messaging/apple-integration](https://firebase.flutter.dev/docs/messaging/apple-integration).

### Admob

В файле `application.config` есть параметр `ADMOB_APP_ID`. По умолчанию туда вписан тестовый app ID от гугла, который нужно использовать для тестирования. **Настоящий app ID для тестирования использовать нельзя!** За это могут заблокировать аккаунт.

Если тестовый app ID потерян, его можно взять здесь: [https://developers.google.com/admob/android/quick-start#update_your_androidmanifestxml](https://developers.google.com/admob/android/quick-start#update_your_androidmanifestxml) (пункт "sample AdMob app ID").

После смены app ID необходимо запустить `./app.sh prepare` и пересобрать приложение.

На стороне самого плагина настройка Admob производится так же, как и в старом приложении. В настройках плагина нужно прописать ad unit ID, который берется в консоли Admob.

![](./.static/ad_network.png)

Для тестирования **настоящий ad unit ID использовать нельзя!** За это могут заблокировать аккаунт. Тестовые unit IDs берутся здесь: [https://developers.google.com/admob/android/test-ads#sample_ad_units](https://developers.google.com/admob/android/test-ads#sample_ad_units).

На этой же странице есть таблица "Advertisement pages." В этой таблице можно включать/отключать рекламу на конкретных страницах приложения. Все изменения применяются динамически через server events.

После отключения/включения рекламы нужно нажать на кнопку "Save."

![](./.static/ad_pages.png)

Комманды
--------

1. Всякий раз когда вы меняете файл `application/config/application.config` нужно запустить комманду: `./app.sh prepare` 
чтобы применить измения в файлах.

2. Сбилдить `web`  - `./app.sh build_web`

3. Сбилдить `ios`  - `./app.sh build_ios`

4. Сбилдить `android`  - `./app.sh build_android`

5. Сбилдить дарт код (иногда нужно превратить анотации в код) `./app.sh build_dart`

Настройка nginx
---------------

Если приложение и Skadate имеют разный origin (см. [https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Origin](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Origin)), необходимо добавить заголовок`Access-Control-Allow-Origin` для ориджина, на котором находится приложение для избежания CORS-ошибок при загрузке ресурсов.

В простейшем случае это выглядит так (в регулярном выражении нужно перечислить расширения файлов ресурсов):

```
location ~* \.(jpg|jpeg|png)$ {
    add_header Access-Control-Allow-Origin *;
}
```

Однако для большей безопасности лучше прописать ориджин приложения в значении заголовка.

Сниппеты
--------

Добавлены сниппеты для vscode, позволяющие писать меньше кода руками. Видео с примером работы можно посмотреть в слаке в канале по апп поискав тег #pr11.

Для использования сниппета необходимо ввести его короткое имя и нажать `Tab`.

Например,

```
sg + Tab => serviceLocator.get<[курсор будет здесь]>();
```

Список сниппетов:

```
skserv  -- шаблон сервиса
skmod   -- шаблон модуля
skpage  -- шаблон страницы
skrm    -- шаблон экземпляра RouteModel
sg      -- serviceLocator.get
srls    -- serviceLocator.registerLazySingleton
srlsa   -- serviceLocator.registerLazySingletonAsync
srlsd   -- serviceLocator.registerLazySingletonWithDependencies
srf     -- serviceLocator.registerFactory
srfa    -- serviceLocator.registerFactoryAsync
```

Свои сниппеты можно добавить в файл `.vscode/dart.code-snippets`. При добавлении новых сниппетов этот список нужно обновить.

Разное
------

1. `Fix Imports` превращает из абсолютного пути в относительный (нужен плагин VSCode `dart-import`).
 Комманду нужно выполнять из `View` -> `Command Pallete`

2. В качестве стейт менеджера мы используем - `MobX`.  Пакет:  https://pub.dev/packages/mobx  Starter guide: https://mobx.netlify.app/getting-started

3. Установка дополнительных пакетов - Обычно хорошо описанные дополнения сами описывают как их установить во `flutter` проект, но если такого нет
то здесь можно почитать как это делается - https://flutter.dev/docs/development/packages-and-plugins/using-packages
