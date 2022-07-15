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
           "lx/core":"dev-master"
       },
       "repositories":[
           {
               "type":"git",
               "url":"https://github.com/epicoon/lx-core"
           }
        ],
        "scripts": {
           "post-update-cmd": [
               "php vendor/lx/core/lx-install"
           ]
        }
   }
   ```
   To use other lx-packages, simply add them to the composer configuration file. [Example](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/composer-example.md).<br>
   At the root of the project, run the command `composer install`.<br>
   As a result a directory `vendor` will be created. It will contain composer packages in a directory `lx`.
   Note the "scripts" section. This script will create the `lx` folder at the root of the application. The `lx` folder is necessary for the platform to function. Inside there will be configuration files, a CLI launch file, system directories etc. This script will also create (or modify) the `.gitignore` file so that the platform system files are not monitored by the version control system.
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
3. It remains to trigger the launch of the lx application in your code.
   To do this, add the code in the index file:
   ```php
   /* An example is given for the situation when the index file is in the project root
    * If it is in a directory (for example web), you need to correct the path
    */
   require_once __DIR__ . '/vendor/lx/core/main.php';
   $app = new lx\HttpApplication();
   $app->run();
   ```
4. If in the browser on the domain specified in the server configuration and the file `/etc/hosts` you see the page:
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
* [Plugins](#arch-plugin)
* [Snippets](#arch-snippet)
* [Widgets](#arch-widget)
* [Respondents](#arch-respondent)

Of all the elements of the architecture a service and a plugin are configurable with special files. And also the application is configurable.

* <a name="arch-package"><h3>Packages</h3></a>
  The application consists of packages.
  A package is a directory with a specific configuration file. Variants of the names of the configuration file:
  * `composer.json`
  * `lx-config.php`
  * `lx-config.yaml`
  * `lx-config/main.php`
  * `lx-config/main.yaml`<br>
  The presence of the configuration file `composer.json` means that the directory is a composer-package.<br>
  The presence of a configuration file with the prefix `lx` means that the directory is an lx-package (service).<br>
  A package can be a composer package and an lx package at the same time (if it has both configuration files).<br>
  It is recommended to describe the autoload rules in the lx-configuration file (and not in `composer.json`), since the platform has its own autoloader which does not contradict the autoloader of the composer but has advanced capabilities.<br>
  The `composer.json` file can be used to describe dependencies.<br>
  Packages can be located in multiple directories within the application, in which ones it is defined in the application configuration. [Application configuration details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/app-config.md)

* <a name="arch-service"><h3>Services</h3></a>
  Service is an lx-package that can respond to requests.<br>
  If has a set of tools to do this:
  * [Service routers](#arch-service-router)
  * [Controllers](#arch-controller)
  * [Actions](#arch-action)
  * [Plugins](#arch-plugin)

  It has a special field with settings in the lx-configuration. Configuration example in `yaml`:
  ```yaml
  # Service name
  name: i-am-vendor/service-name

  # Autoload rules
  autoload:
    psr-4:
      lx\devWizard\: ''

  # The field with the service settings
  # the presence of this field turns the package into a service
  service:
    # The name of the service class (if exists)
    class: psrNamespace\Service

    # Other service settings
    plugins: plugin
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
  #     value - requested source data
  # - class 
  #   require parameter 'name' - name of the router class
  #   the router is extended from [[lx\Router]]
  router:
    type: map
    routes:
      # Home page
      /: your/home-service

      # URL oriented directly to the service plugin
      # working only for a specific application mode
      test-page: {service-plugin: 'your/some-service:some-plugin', on-mode: dev}
  ...
  ```
  ![Application routing schema](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/images/architecture-scheme.png)
  [Routing details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/routing.md)

* <a name="arch-service-router"><h3>Service routers</h3></a>
  Request management within services is handled by service routers.<br>
  By analogy with the application’s router, the service’s router can be configured by the service’s lx-configuration file or it can be extended by class `lx\ServiceRouter`.<br>
  Example of configuration via the lx-configuration file:
  ```yaml
  name: i-am-vendor/service-name
  ...

  service:
    class: psrNamespace\Service

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

        # Request to plugin. Plugin rendering result will be returned
        some-route-4: {plugin: pluginName}
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

* <a name="arch-plugin"><h3>Plugins</h3></a>
  The plugin is a service element representing a set of logic running on the client, the graphical interface and the server part represented by the respondents (AJAX-controllers operating in the context of specific plugins). In its idea reminds SPA.
  
  Plugin features:
  * rendered and loaded by the browser once
  * runs without page reload
  * in the project structure has its own directory with a specific infrastructure
  * has its own lx-configuration file to configure some parameters and infrastructure
  * has its own resources (JS code, CSS code, images, etc.)
  * the plugin is available as a context variable `Plugin` in its snippets code
  * the plugin is available as a context variable `Plugin` in its client-side code
  * requests data from the server via AJAX
  * to generate data sent by the server has its own tools ([respondents](#arch-respondent))
  * any plugin can load any other plugin and place it in an element on its page
  * plugin rendering can be initiated with passing parameters if they are provided
  
  List of plugin infrastructure elements:
  * Plugin JS-code. The file path is specified by the configuration key `jsMain`.
  * Respondents. PHP Classes. They are AJAX controllers that send data to the plugin client side (configuration key `respondents`).
  * Snippets. Plugin rendering starts with the root snippet, the code of which is described in the file, the path to which determines the `rootSnippet` configuration key. In certain situations, it is convenient to call snippets by the name of their files (or directories) relative to the common root directory for snippets. A list of such directories can also be specified in the configuration (the `snippets` key).
  * Images (configuration key `images`). You can set the directory in which the images of the plugin will lie.
  * CSS files (configuratoin key `css`). You can set the directory in which the CSS files will lie. When the plugin is loaded, these files will be automatically added to the page.<br>
  An example of setting up the elements of the plugin infrastructure in the lx-configuration:
  ```yaml
  # JS-code that runs after the plugin is loaded, will be in the file
  # 'frontend/_main.js' relative to the root directory of the plugin
  jsMain: frontend/_main.js

  # Respondents map
  respondents:
    # Key - respondent alias for client side
    # Value - respondent class name
    # (!)the namespace is specified relative to the plugin namespace
    Respondent: backend\Respondent

  # GUI root file will be 'snippet/_root.js',
  # it contains the plugin root snippet code
  rootSnippet: snippets/_root.js

  # Image directory path
  # You can use application aliases - then the path will be constructed according to the alias
  # You can start with the symbol '/' - then the path will be considered relative to the root of the site
  images: assets/images

  # CSS files directory path
  css: assets/css
  ```
  [Plugin configuration details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/plugin-config.md)

  Underscore in file names is the author of the platform select to indicate the special status of files (root files, code enter points etc.), and also simplify their visual search in the project explorer when sorting directories and files in alphabetical order. If desired, all path agreements are changed by configuration.

* <a name="arch-snippet"><h3>Snippets</h3></a>
  The snippets are separate parts of the graphical interface. The code of the snippets is written in a procedural style (as opposed to widgets whose code is written in the OOP style, see below). They can be either a separate file or a directory in which there should be a file with a view code (in this case, the name of the file must match the name of the snippet directory and may begin with an underscore)<br>
  Path to the snippet named `snippetName` variants:
  * path/to/snippet/snippetName.js
  * path/to/snippet/snippetName/snippetName.js
  * path/to/snippet/snippetName/\_snippetName.js<br>
  Thus, the philosophy of the snippets implies their use in situations where the use of OO techniques is not necessary for an interface fragment (encapsulation, inheritance, polymorphism). This part of the GUI is specific to this part of the application. However, this does not mean that there is no mechanism for reusing the code for snippets.

  A snippet can have its own JS-code running on the client side. To do this, you need to define a function in the snippet code and pass it to the `Snipept.onLoad ()` method:
  ```js
  /**
   * @const {lx.Application} App
   * @const {lx.Plugin} Plugin
   * @const {lx.Snippet} Snippet
   */

   Snippet.onLoad(()=>{
      console.log('This code has executed on the client side!');
   });
  ```
  Three context variables are available while writing snippet code:
  * App - the application object
  * Plugin - the plugin object to which the snippet belongs
  * Snippet - snippet object. It has property `widget` which is the instance of the `lx.Box` class. It also has the `attributes` property, a JS-object that will be available on the client side.

  Two context variables are available while writing functoion for client-side snippet code:
  * Plugin - the plugin object to which the snippet belongs
  * Snippet - snippet object. It has property `widget` which is the instance of the `lx.Box` class. It also has the `attributes` property, a JS-object that can contain fields defined on the server side.

  Snippets are built from widgets. Snippets can include other snippets. Example:
  ```js
  // We need include JS modules containing the code of the necessary widgets
  #lx:use lx.Box;
  #lx:use lx.Button;
  #lx:use lx.ActiveBox;

  // Make a widget
  let menu = new lx.Box(menuConfig);

  // The widget is added inside another widget
  button = menu.add(lx.Button, buttonConfig);

  // Add a couple of widgets to put snippets into them
  let box1 = new lx.Box(config1);
  let box2 = new lx.Box(config2);

  // Insert the snippet - in this case the name is the path relative to the code file
  // You can use application aliases
  // If the path starts with '/', then it is considered relative to the root of the site.
  box1.setSnippet('snippetName1');

  // Insert one more snippet and specify the configuration
  // @param path - the same path as in the previous case
  // @param attributes - array of parameters that will be available in the 
  //                     snippet code by property [[Snippet.attributes]]
  box2.setSnippet({
    path: 'snippetName2',
    attributes: {},
  });

  // Snippet adding: $snippetName - the snippet name, $config - configuration for widget
  // in which the snippet is being rendered
  let innerSnippet = Snippet.addSnippet(snippetName, config);

  // Several snippets adding
  Snippet.addSnippets({
    snippetName1: config1,
    snippetName2: config2,
  });

  // Several snippets-popups adding 
  // they will be rendered to the lx.ActiveBox widget
  let newSnippetsArray = Snippet.addSnippets([
    {
      path: 'pathToSnippet1',
      widget: lx.ActiveBox,
      // ... конфигурирование виджета
      attributes: {/*...*/}
    },
    {
      path: 'pathToSnippet2',
      widget: lx.ActiveBox,
      // ... конфигурирование виджета
      attributes: {/*...*/}
    }
  ]);

  // Add a snippet from another plugin
  Snippet.addSnippet({
    plugin:'lx/tools:snippets',
    snippet:'confirmPopup'
  });
  ```

  When the snippet js code is executed in the browser, the execution context of the parent snippet is available, but execution contexts of the nested snippets are not available. For example, the `B` snippet is nested in the` A` snippet, the `C` snippet is nested in the` B` snippet: `A -> B -> C`. In the snippets, the variables `a`,` b` and `c` are declared respectively. Then in the `A` snippet only the variable `a` will be available. The variables `a` and `b` will be available in the `B` snippet. The variables` a`, `b` and` c` will be available in the `C` snippet.

  The path to the plugin root snippet is specified in the plugin lx-configuration by the key `rootSnippet`.

  Paths to directories containing snippets can be defined in the plugin's lx-configuration using the `snippets` key.

* <a name="arch-widget"><h3>Widgets</h3></a>
  Widgets are specialized parts of the graphical interface. The widget code is written in OOP style (as opposed to snippets).<br>
  The mechanism of the widget has significant features:
  * The widget instance can be created both on the server side and on the client side
  * Creating a widget on the server side, initiates the creation of an instance on the client side
  * The widget code may contain fragments compiled only for the server, or only for the client
  * It is recommended to define the widget code as a JS-module so that it is convenient to include it in snippet code. [JS-module details](https://github.com/epicoon/lx-doc-articles/blob/master/en/lx-core/doc/js-modules.md)
    Example:
    ```js
    #lx:module nmsp.example.MyWidget

    #lx:use lx.Box;

    class MyWidget extends lx.Box #lx:namespace nmsp.example
    {
        // ... common code

        #lx:server {
            // ... server code
        }

        #lx:client {
            // ... client code
        }
    }
    ```
  * A widget constructor takes a configuration as an associative array. Configuration parameter `key` is important for easy search on the client side.
  * A server-side widget instance supports creation of dynamic properties. These properties will be available on the client side.
  Example:
  Code on the server side:
  ```js
  // Make a widget with a key
  let button = new lx.Button({key: 'myButton'});

  // Add a dynamic property
  button.testField = 'hello from server!';
  ```
  Code on the client side:
  ```js
  // Get the widget instance by the key
  // Operator "->>" means widget finding by the key
  const myButton = Plugin->>myButton;

  // By checking the dynamic property we can see 'hello from server!' in the console
  console.log(myButton.testField);
  ```
  Thus, the philosophy of widgets involves using them to describe the most frequently used and universal fragments of the graphical interface.

* <a name="arch-respondent"><h3>Respondents</h3></a>
  Respondents are functional elements of a plugin. In fact, are php classes. They are AJAX-controllers that give data to the client part of a plugin.
  Example:
  * Definition in the plugin configuration
    ```yaml
    respondents:
      Respondent: backend\Respondent
    ```
  * Respondent code (according to the given configuration, it should be in the file `backend/Respondent.php` relative to the plugin root)
    ```php
    <?php

    namespace path\to\plugin\backend;

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
    ^Respondent.test().then((result) => {
      // result contains string 'Hello from server'
      console.log(result);
    });
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

Now let's create your plugin in the service. At first we need enter the service. Enter the command `\g i-am-vendor/my-service` (use your name if you called the service in your own way). The console location `lx-cli<app>` should change to `lx-cli<service:i-am-vendor/my-service>`. Another way to enter a service is using its index. You can find out a service index by command `\sl`. You will see numbered list of services. Each number is a service index. So in case of index is 2 you can enter the service by command `\g -i=2`.<br>
Finaly we can create a new plugin by command `\cp`. We will again be asked to enter a name. Enter something like `myPlugin`.<br>
It's done!<br>
The plugin has created. At the specified address you can check what exactly was created, verify with the plugin infrastructure described in this documentation.

Now you can study the way to develop your own application by [link](https://github.com/epicoon/lx-doc-articles/blob/master/en/app-dev/expl1/main.md).
