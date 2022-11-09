Ereferer deployment
==========
Create database:
```
CREATE DATABASE ereferer;
CREATE DATABASE ereferer__test;
```

Update database schema:
`php bin/console doctrine:schema:update --force`

Update test database schema: 
`php bin/console doctrine:schema:update --force --env test`

Import with progress indicator 
`pv sqlfile.sql | mysql -u root -p ereferer`

#####Install php extensions:
Tidy: 
`sudo apt-get install php-tidy`

Restart server after change server configuration: 
`sudo service apache2 restart`
________________________
####Tests

Open `PhpStorm -> File -> Settings -> Languages & Frameworks -> PHP -> Test Frameworks`

Create configuration `PHPUnit Local`, mark `Path to phpunit.phar`, specify the path to the file `/vendor/bin/simple-phpunit`

In the `Default configuration file` specify the path to the file `/phpunit.xml.dist`

Now you can open any test, for example, `tests/CoreBundle/ArticleTest.php`, click on the green arrow to the left of the test and run it. 

To run all tests in the context menu of the tests directory, click `Run tests PHPUnit`


________________________
####Recommendations

Format for url: `<entity>/{id}/<action>`

Instead of `$.ajax()`, `$.post()`, `$.get()` use `sendRequest()`, `sendGetRequest()`, `sendPostRequest()`. Presented alternative functions handle server response codes and automatically show flash notification.



