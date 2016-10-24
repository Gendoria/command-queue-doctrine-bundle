# Command Queue Doctrine Bundle

[![Build Status](https://img.shields.io/travis/Gendoria/command-queue-doctrine-bundle/master.svg)](https://travis-ci.org/Gendoria/command-queue-doctrine-bundle)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Gendoria/command-queue-doctrine-bundle.svg)](https://scrutinizer-ci.com/g/Gendoria/command-queue-doctrine-bundle/?branch=master)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/Gendoria/command-queue-doctrine-bundle.svg)](https://scrutinizer-ci.com/g/Gendoria/command-queue-doctrine-bundle/?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/gendoria/command-queue-doctrine-bundle.svg)](https://packagist.org/packages/gendoria/command-queue-doctrine-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/gendoria/command-queue-doctrine-bundle.svg)](https://packagist.org/packages/gendoria/command-queue-doctrine-bundle)

Doctrine driver bundle for [`gendoria/command-queue-bundle`](https://github.com/Gendoria/command-queue-bundle).

Bundle created in cooperation with [Isobar Poland](http://www.isobar.com/pl/).

![Isobar Poland](doc/images/isobar.jpg "Isobar Poland logo") 

## Installation

### Step 0: Prerequisites

:warning: Before using this bundle, you should install and configure 
[`gendoria/command-queue-bundle`](https://github.com/Gendoria/command-queue-bundle) and 
[`doctrine/doctrine-bundle`](https://github.com/doctrine/DoctrineBundle).

### Step 1: Download the Bundle


Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require gendoria/command-queue-doctrine-bundle "dev-master"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle


Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Gendoria\CommandQueueDoctrineDriverBundle\GendoriaCommandQueueDoctrineDriverBundle(),
        );

        // ...
    }

    // ...
}
```

[Gendoria Command Queue Bundle](https://github.com/Gendoria/command-queue-bundle) should also be enabled and configured.


### Step 3: Add bundle configuration

The example bundle configuration looks as one below.

```yaml
gendoria_command_queue_doctrine_driver:
    drivers:
        poolname:
            serializer: '@gendoria_command_queue.serializer.jms'
            doctrine_connection: default
```

`poolname` key in `drivers` section can be any valid string. It allows to create separate driver 'pools'
for command routing.

`serializer` parameter is used to specify serializer driver used by the driver. 
You should use `jms` or `symfony` driver here, where `jms` is preferred.

Some serializer drivers are provided by [Gendoria Command Queue Bundle](https://github.com/Gendoria/command-queue-bundle).

### Step 4: Add a driver to Command Queue Bundle configuration

For each command queue pool you want to use rabbitmq driver on, you should set it as send_driver.

So for `gendoria_command_queue_doctrine_driver.driver.poolname`, your configuration should look similar 
to code below.

```yaml
gendoria_command_queue:
    ...
    pools:
        ...
        poolname:
            send_driver: '@gendoria_command_queue_doctrine_driver.driver.poolname'
```

## Usage

To start receiving commands for your pool, you have to start one rabbitmq bundle worker process.

The command to do that is:

```console
$ app/console cmq:worker:run doctrine.poolname
```

Where `poolname` is the pool name you defined in `pools` section of configuration.

You should use services like [supervisord](http://supervisord.org/) to control running and restarting your workers.