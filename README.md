# Query-summary
Provides a query summary in laravel-debugbar. For situations where you have many queries being made, and you need the raw queries summarised into a list of unique queries, the number of times they ran, and the total query time. Eg: 

![screenshot](https://user-images.githubusercontent.com/1218573/72647128-ade76e80-396f-11ea-8e58-8ebba144dd42.png)


## Installation

Install the package with composer. It is recommended to only require the package in development.

```shell
composer require maraful/query-summary --dev
```

A Service Provider is used to automatically add the Query Summary "collector", and you'll see the results in Laravel Debugbar.
