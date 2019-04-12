[Russian version (Русская версия)](https://github.com/epicoon/lx-core/blob/master/README-ru.md)

# Lx - web-application development platform

This repository contains platform core. The core is enough for web application developing. But we recommend check other useful repositories containing documentation, tools and examples for this platform:
* [lx-doc](https://github.com/epicoon/lx-doc/blob/master/README.md)
* [lx-demo](https://github.com/epicoon/lx-demo/blob/master/README.md)
* [lx-dev-wizard](https://github.com/epicoon/lx-dev-wizard/blob/master/README.md)
* [lx-tools](https://github.com/epicoon/lx-tools/blob/master/README.md)


## Contents:
* [Basic principles](#properties)
* [Deploy](#deploy)
* [Architecture](#architecture)
* [CLI](#cli)
* [Application developing example](https://github.com/epicoon/lx-doc-articles/blob/master/en/app-dev/expl1/main.md)


<a name="properties"><h2>Basic principles</h2></a>
* The platform belongs to the category of full-stack technologies. Combines backend and frontend.
* Multiservice architecture. The application consists of services - pieces of logic that are independent of each other.
* Reuse code. All elements of the architecture are reusable.
* Flexible routing.
* Work with a minimum of page reloads. Simple work via AJAX.
* Object-oriented approach to building graphical interfaces.
* Simple, easily expandable internationalization.


<a name="deploy"><h2>Deploy</h2></a>
1. For deploy the platform use PHP-package manager `Composer`.<br>
   Composer configuration file example (`composer.json`):
   ```
   {
       "require":{
           "lx/lx-core":"dev-master"
       },
       "repositories":[
           {
               "type":"git",
               "url":"https://github.com/epicoon/lx-core"
           }
        ]
   }
   ```
   To use other lx-packages, simply add them to the composer configuration file. [Example](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/composer-example.md).<br>
   At the root of the project, run the command `composer install`.<br>
   As a result a directory `vendor` will be created. It will contain composer packages in a directory `lx`.
2. Setting up a server for `nginx` for `Ubuntu`.<br>
   Configuration:
   ```
   server {
      charset utf-8;
      client_max_body_size 128M;
      listen 80;
      listen [::]:80;

      server_name server.name;
      root /path/to/project;
      index path/to/index.php;
	
      location / {
         try_files $uri /path/to/index.php?$args;
      }

      location ~ \.php$ {
         include fastcgi_params;
         fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
         fastcgi_pass unix:/run/php/php7.1-fpm.sock;
      }
   }
   ```
   Note the paths and version of php-fpm, substitute your values.<br>
   Add an entity in `/etc/hosts`.<br>
   Restart server.<br>
3. To deploy the platform in the project run php-script `vendor/lx/lx-core/lx-install`.<br>
   As a result in the root of the project follow directories will be created:
   * lx - directory for platform configuration and system files. Mandatory.
   * services - catalog for application services (details below). It contains the first service for the application.. Optional. Work with services is configured in the application configuration.
4. It remains to trigger the launch of the lx application in your code.
   To do this, add the code in the index file:
   ```php
   /* An example is given for the situation when the index file is in the project root
    * If it is in a directory (for example web), you need to correct the path
    */
   require_once __DIR__ . '/vendor/lx/lx-core/main.php';
   lx::run();
   ```
5. If in the browser on the domain specified in the server configuration and the file `/etc/hosts` you see the page:
   ![lx start page](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/images/lx-start-page.png)
   then everything worked out.


<a name="architecture"><h2>Architecture</h2></a>
Elements of the application that make up the architecture:
* [Packages](#arch-package)
* [Services](#arch-service)
* [Application router](#arch-router)
* [Service routers](#arch-service-router)
* [Controllers](#arch-controller)
* [Actions](#arch-action)
* [Modules](#arch-module)
* [Blocks](#arch-block)
* [Widgets](#arch-widget)
* [Respondents](#arch-respondent)

Of all the elements of the architecture a service and a module are configurable with special files. And also the application is configurable.

* <a name="arch-package"><h3>Packages</h3></a>
  The application consists of packages.
  A package is a directory with a specific configuration file. Variants of the names of the configuration file:
  * `composer.json`
  * `lx-config.php`
  * `lx-config.yaml`
  * `lx-config/main.php`
  * `lx-config/main.yaml`<br>
  The presence of the configuration file `composer.json` means that the directory is a composer-package.<br>
  The presence of a configuration file with the prefix `lx` means that the directory is an lx-package.<br>
  A package can be a composer package and an lx package at the same time (if it has both configuration files).<br>
  It is recommended to describe the autoload rules in the lx-configuration file (and not in `composer.json`), since the platform has its own autoloader which does not contradict the autoloader of the composer but has advanced capabilities.<br>
  The `composer.json` file can be used to describe dependencies.<br>
  Packages can be located in multiple directories within the application, in which ones it is defined in the application configuration. [Application configuration details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/app-config.md)

* <a name="arch-service"><h3>Services</h3></a>
  Service is a package that can respond to requests. It has a special field with settings in the lx-configuration. Configuration example in `yaml`:
  ```yaml
  # Service name
  name: lx/lx-dev-wizard

  # Autoload rules
  autoload:
    psr-4:
      lx\devWizard\: ''

  # The field with the service settings
  # the presence of this field turns the package into a service
  service:
    # The name of the service class (if exists)
    class: lx\devWizard\Service

    # Other service settings
    modules: module
    models: model
    ...
  ```
  Service has a specific infrastructure described using the lx-configuration.
  [Service configuration details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/service-config.md)

* <a name="arch-router"><h3>Application router</h3></a>
  The application distributes requests for services by the router. It exists in the application in the singular.<br>
  The router is configured in the application configuration. Example:
  ```yaml
  ...
  # Available types:
  # - map
  #   require parameter 'routes' - map of the routes or 'path' - of file with map
  #   map of the routes - associative array:
  #     key - URL of the query (or regular expression if it starts with '~')
  #     value - data for the object where the request is redirected
  # - class 
  #   require parameter 'name' - name of the router class
  #   the router is extended from [[lx\Router]]
  router:
    type: map
    routes:
      # Home page
      /: your/home-service
      # URL oriented directly to the service module
      # working only for a specific application mode
      test-page: {service-module: 'your/some-service:some-module', on-mode: dev}
  ...
  ```
  ![Application routing schema](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/images/architecture-scheme.png)
  [Routing details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/routing.md)

* <a name="arch-service-router"><h3>Service routers</h3></a>
  Request management within services is handled by service routers.<br>
  By analogy with the application’s router, the service’s router can be configured by the service’s lx-configuration file or it can be extended by class `lx.ServiceRouter`.<br>
  Example of configuration via the lx-configuration file:
  ```yaml
  name: lx/lx-dev-wizard
  ...

  service:
    class: lx\devWizard\Service

    # Service router settings
    router:
      type: map
      routes:
        # Request to controller
        # The result of the controller method [[run()]] will be returned
        some-route-1: ControllerClassName
        # Request to controller
        # The result of the controller method [[actionName()]] will be returned
        some-route-2: ControllerClassName::actionName
        # Request to action. The result of the method [[run()]] will be returned
        some-route-3: {action: ActionClassName}
        # Request to module. Module rendering result will be returned
        some-route-4: {module: moduleName}
    ...
  ```
  ![Service routing schema](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/images/service-routing.png)

* <a name="arch-controller"><h3>Controllers</h3></a>
  The controller is such a service element that responds to requests and can handle many different URLs.<br>
  If only the name of the controller class is specified in the settings of the service router for a specific URL, the method `run()` will be called to process the request.<br>
  
  If the name of the controller class and the name of the method are specified in the settings of the service router for a specific URL (f.e.: `ControllerClassName::actionName`), this method will be called to process the request.

* <a name="arch-action"><h3>Actions</h3></a>
  Action is such an element of the service, which responds to a single request.<br>
  The `run()` method will be called to process the request.

* <a name="arch-module"><h3>Modules</h3></a>
  The module is a service element representing a set of logic running on the client, the graphical interface and the server part represented by the respondents (AJAX-controllers operating in the context of specific modules). In its idea reminds SPA.
  
  Module features:
  * rendered and loaded by the browser once
  * runs without page reload
  * in the project structure has its own directory with a specific infrastructure
  * has its own lx-configuration file to configure some parameters and infrastructure
  * has its own resources (JS code, CSS code, images, etc.)
  * the module is available as a context variable `$Module` in its blocks code (view PHP-files)
  * the module is available as a context variable `Module` in its JS code
  * requests data from the server via AJAX
  * to generate data sent by the server has its own tools (respondents)
  * any module can load any other module and place it in an element on its page
  * module rendering can be initiated with passing parameters if they are provided
  
  List of module infrastructure elements:
  * Module JS-code. There are two main executed files: the first one will be executed before the module is loaded on the client (the file path specified by the configuration key `jsMain`), the second one will be executed after the module is loaded on the client (the file path specified by the configuration key `jsBootstrap`).
  * Respondents. PHP Classes. They are AJAX controllers that send data to the module client side (configuration key `respondents`).
  * View. Module rendering starts from the root block, whose code is described in the root view file (the file path specified by the configuration key `view`).
  * Images (configuration key `images`). You can set the directory in which the images of the module will lie.
  * CSS files (configuratoin key `css`). You can set the directory in which the CSS files will lie. When the module is loaded, these files will be automatically added to the page.<br>
  An example of setting up the elements of the module infrastructure in the lx-configuration:
  ```yaml
  # JS-code that runs before the module is loaded, will be in the file
  # 'frontend/_bootstrap.js' relative to the root directory of the module
  jsBootstrap: frontend/_bootstrap.js
  # JS-code that runs after the module is loaded, will be in the file
  # 'frontend/_main.js' relative to the root directory of the module
  jsMain: frontend/_main.js

  # Respondents map
  respondents:
    # Key - respondent alias for client side
    # Value - respondent class name
    # (!)the namespace is specified relative to the module namespace
    Respondent: backend\Respondent

  # GUI root file will be 'view/_root.php',
  # it contains the module root block code
  viewIndex: view/_root.php

  # Image directory path
  # You can use application aliases - then the path will be constructed according to the alias
  # You can start with the symbol '/' - then the path will be considered relative to the root of the site
  images: assets/images

  # CSS files directory path
  css: assets/css
  ```
  [Module configuration details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/module-config.md)

  Underscore in file names is the author of the platform select to indicate the special status of files (root files, code enter points etc.), and also simplify their visual search in the project explorer when sorting directories and files in alphabetical order. If desired, all path agreements are changed by configuration.

* <a name="arch-block"><h3>Blocks</h3></a>
  The blocks are separate parts of the graphical interface. The code of the blocks is written in a procedural style (as opposed to widgets whose code is written in the OOP style, see below). They can be either a separate php file or a directory in which there should be a php file with a view (in this case, the name of the php file must match the name of the block directory and may begin with an underscore)<br>
  Path to the block named `blockName` variants:
  * path/to/block/blockName.php
  * path/to/block/blockName/blockName.php
  * path/to/block/blockName/_blockName.php
  Thus, the philosophy of the blocks implies their use in situations where the use of OO techniques is not necessary for an interface fragment (encapsulation, inheritance, polymorphism). This part of the GUI is specific to this part of the application. However, this does not mean that there is no mechanism for reusing the code for blocks.

  The block can have its own JS-code that controls the described fragment of the graphical interface (just for this a directory block version is exist). In this case, the file name requirements are the same as for the view file (but with the extension `.js`).
  Examples:
  * path/to/block/blockName/blockName.php
  * path/to/block/blockName/blockName.js
  or:
  * path/to/block/blockName/_blockName.php
  * path/to/block/blockName/_blockName.js

  Two context variables are available while writing PHP block code:
  * $Module - объект модуля, к которому относится блок
  * $Block - объект самого блока, с которым можно работать как с обычным виджетом класса `lx\Box`

  При написании JS-кода блока доступны три контекстные переменные:
  * Module - the module object to which the block belongs
  * Block - the object of the block itself. You can work with it like this is instance of `lx\Box`
  * clientParams - an object with fields that can be set in PHP when a block is loaded into an element

  Blocks are built from widgets. Blocks can include other blocks by invoking rendering. Example:
  ```php
  // Make a widget
  $menu = new lx\Box($menuConfig);

  // The widget is added inside another widget
  $button = $menu->add(lx\Button::class, $buttonConfig);

  // Add a couple of widgets to put blocks into them
  $box1 = new lx\Box($config1);
  $box2 = new lx\Box($config2);

  // Insert the block - in this case the name is the path relative to the code file
  // You can use application aliases
  // If the path starts with '/', then it is considered relative to the root of the site.
  $box1->setBlock('blockName1');

  // Insert one more block and specify the configuration
  // @param path - the same path as in the previous case
  // @param renderParams - array of parameters that will be available as variables
  //                       in the file describing the embed block
  // @param clientParams - array of parameters that will be available in the client-side
  //                       block js-code in the context object 'clientParams'
  $box2->setBlock([
    'path' => 'blockName2',
    'renderParams' => [],
    'clientParams' => [],
  ]);

  // Block adding: $blockName - the block name, $config - configuration for widget
  // in which the block is being rendered
  $Block->addBlock($blockName, $config);

  // Several blocks adding
  $Block->addBlocks([
    $blockName1 => $config,
    $blockName2 => $config,
  ]);

  // Several blocks-popups adding 
  // they will be rendered to the lx\ActiveBox widget and initially hidden
  $Block->addPopups([
    $popupName1 => $config,
    $popupName2 => $config,
  ]);

  // It is very easy to write a method for a service that will render frequently used blocks:
  // Get such a service
  $tools = \lx::getService('lx/lx-tools');
  // Render a couple of blocks into the current block
  $tools->renderBlock('inputPopup');
  $tools->renderBlock('confirmPopup');
  ```



  When the block js code is executed in the browser, the execution context of the parent block is available, but execution contexts of the nested blocks are not available. For example, the `B` block is nested in the` A` block, the `C` block is nested in the` B` block: `A -> B -> C`. In the blocks, the variables `a`,` b` and `c` are declared respectively. Then in the `A` block only the variable `a` will be available. The variables `a` and `b` will be available in the `B` block. The variables` a`, `b` and` c` will be available in the `C` block.

  The path to the module root block is specified in the module lx-configuration by the key `view`.

* <a name="arch-widget"><h3>Widgets</h3></a>
  Widgets are specialized parts of the graphical interface. The widget code is written in OOP style (as opposed to blocks).<br>
  The mechanism of the widget has significant features:
  * The widget instance can be created both on the server side and on the client side
  * Creating a widget on the server side, initiates the creation of an instance on the client side
  * The widget code is represented by two classes - one on the server side (PHP code), the second on the client side (JS code)
  * Widget classes (PHP and JS) should be in separate files in a shared directory. Example:
    * path/to/widget/MyWidget/MyWidget.php
    * path/to/widget/MyWidget/MyWidget.js
    or:
    * path/to/widget/MyWidget/_MyWidget.php
    * path/to/widget/MyWidget/_MyWidget.js
    The class name of such widget must match the directory name `MyWidget`.
    PHP code:
    ```php
    <?php

    namespace nmsp\example;

    use lx\Box;

    class MyWidget extends Box
    {
      // ... code
    }
    ```
    JS code:
    ```js
    class MyWidget extends lx.Box #lx:namespace nmsp.example
    {
      // ... code
    }
    ```
  * A widget constructor takes a configuration as an associative array. Configuration parameter `key` is important for easy search on the client side.
  * A server-side widget instance supports creation of JS-style dynamic properties. These properties will be available on the client side.
  Example:
  PHP code:
  ```php
  // Make a widget with a key
  $button = new lx\Button(['key' => 'myButton']);

  // Add a dynamic property
  $button->testField = 'some text';
  ```
  JS code:
  ```js
  // Get the widget instance by the key
  // Operator "->>" means widget finding by the key
  const myButton = Module->>myButton;

  // By checking the dynamic property we can see 'some text' in the console
  console.log(myButton.testField);
  ```
  Thus, the philosophy of widgets involves using them to describe the most frequently used and universal fragments of the graphical interface. You can draw an analogy - if the blocks are buildings, the widgets are bricks.

* <a name="arch-respondent"><h3>Respondents</h3></a>
  Respondents are functional elements of a module. In fact, are php classes. They are AJAX-controllers that give data to the client part of a module.
  Example:
  * Definition in the module configuration
    ```yaml
    respondents:
      Respondent: backend\Respondent
    ```
  * Respondent code (according to the given configuration, it should be in the file `backend/Respondent.php` relative to the module root)
    ```php
    <?php

    namespace path\to\module\backend;

    class Respondent extends \lx\Respondent
    {
      public function test()
      {
        return 'Hello from server';
      }
    }
    ```
  * Using respondent in the JS code
    ```js
    ^Respondent.test() : (result) => {
      // result contains string 'Hello from server'
      console.log(result);
    };
    ```


<a name="cli"><h2>CLI</h2></a>
The application supports command line interface.<br>
You may run it by going to the directory `path\to\project\lx` and executing the command `php lx cli`.<br>
The command `\h` (or `help`) display a list of available commands.

Let's create your service. To do this, enter the command `\cs` (or `create-service`).<br>
We will be prompted to enter the name of the service. Let's enter something like `i-am-vendor/my-service`.<br>
Since the application configuration contains several directories for packages (and services in particular), we will be asked to select the desired directory. Let's select the second (services) - enter `2`.<br>
Done!<br>
At the specified address you can check what exactly was created, verify with the service infrastructure described in this documentation.

Now let's create your module in the service. At first we need enter the service. Enter the command `\g i-am-vendor/my-service` (use your name if you called the service in your own way). The console location `lx-cli<app>` should change to `lx-cli<service:i-am-vendor/my-service>`. Another way to enter a service is using its index. You can find out a service index by command `\sl`. You will see numbered list of services. Each number is a service index. So in case of index is 2 you can enter the service by command `\g -i=2`.<br>
Finaly we can create a new module by command `\cm`. We will again be asked to enter a name. Enter something like `myModule`.<br>
It's done!<br>
The module has created. At the specified address you can check what exactly was created, verify with the module infrastructure described in this documentation.

Now you can study the way to develop your own application by [link](https://github.com/epicoon/lx-doc-articles/blob/master/en/app-dev/expl1/main.md).
