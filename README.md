## Hello World 2017 API [![Build Status](https://travis-ci.com/jpbrabec/HelloWorldPortal2017.svg?token=ZWWEsxySq45BZfZfi25Y&branch=master)](https://travis-ci.com/jpbrabec/HelloWorldPortal2017)

This repository contains the source code for the Hello World 2017 backend api.


### Running Locally
You can follow these steps to get the server running locally:

* Have `php 7` installed
* Clone this repository
* Run `composer install` in the project directory to install dependencies. If you don't have composer installed, grab it [here](https://getcomposer.org/)
* Create a `.env` file in the project root (next to `.env.example`).    
* Configure `.env` to use your local mysql database credentials
* Run `php artisan migrate` to set up the database, and then `php artisan db:seed`
* Run php artisan `jwt:generate` to create a JWT token
* Run php artisan `key:generate` to create an app key
* Finally, run the server with `php artisan serve`.

It's not required, but you can make some optional configuration changes if needed:
* You can assign a member the devteam role by inserting a row into the row_user pivot table. Users with the devteam role can set up additional admin accounts via the stats page. The site mode can also be configured on this screen. 
* You can start up the worker queue with `php artisan queue:work`. This queue will handle sending emails; if it's not running, emails won't be sent or logged.
* By default, the `MAIL_DRIVER` setting is set to log. Emails will be saved in `storage/logs/laravel.log` instead of actually being sent. To change this, you can set the MAIL_DRIVER to `mailgun` and update the mailgun credentials.
* By default, resumes will be saved locally in `storage/app/resumes`. If you want to upload the resumes to Amazon S3, set the `FILESYSTEM_DRIVER=s3` in the `.env` file. You'll also need to configure the AWS key, secret, region, and bucket credentials.  

You can run tests with `./vendor/bin/phpunit`.
