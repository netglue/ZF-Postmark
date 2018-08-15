# Zend Framework Module: Postmark Inbound & Events Webhook


[![Latest Stable Version](https://poser.pugx.org/netglue/zf-postmark/version)](https://packagist.org/packages/netglue/zf-postmark)
[![Coverage Status](https://coveralls.io/repos/github/netglue/ZF-Postmark/badge.svg)](https://coveralls.io/github/netglue/ZF-Postmark)
[![Build Status](https://travis-ci.org/netglue/ZF-Postmark.svg?branch=master)](https://travis-ci.org/netglue/ZF-Postmark)
[![Maintainability](https://api.codeclimate.com/v1/badges/9a92cc83946c205396e8/maintainability)](https://codeclimate.com/github/netglue/ZF-Postmark/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/9a92cc83946c205396e8/test_coverage)](https://codeclimate.com/github/netglue/ZF-Postmark/test_coverage)

## Purpose

This module/package can be added to a ZF3 application in order to easily process webhooks sent by
[Postmark](https://postmarkapp.com) such as delivery, click, open and bounce events. It can also be used to process 
inbound email messages.

It hasn’t been tested on a ZF2 app, so YMMV. Probably the dependencies are too recent for it to work but it wouldn't be
difficult to port for ZF2.

## Install

Install with composer using `"netglue/zf-postmark"`, enable the module in your `application.config.php` using the
module name `'NetgluePostmark'` and add custom configuration to change the route url perhaps or set up
Basic HTTP Auth _(Recommended)_.

Zend's component installer should inject the module name automatically for you during composer installation.

## Configure Basic Auth

In order to mitigate random post requests to your inbound or event webhook endpoint, you should configure basic auth,
so create a local config file based on the contents of `config/postmark.local.php.dist` and configure to suit.

Given a username and password of `postmark` and `Pa55w0rd`, by default, your webhook url would be
`https://postmark:Pa55w0rd@my-domain.com/postmark-outbound-webhook`

## Setup Webhooks at your Postmark account

Assuming you've used the default routes, and enabled basic auth, your endpoints will be:

`https://username:password@your-domain.com/postmark-outbound-webhook` for bounces, clicks, deliveries etc and
`https://username:password@your-domain.com/postmark-inbound-webhook` for inbound email messages.

Go to [your account on Postmark](https://account.postmarkapp.com/servers) and select the server you want to configure,
click settings and enter the correct URL for either the inbound or outbound webhooks and optionally provide the basic auth
credentials if you have configured them.

The outbound webhook accepts Delivery, Bounce, Spam Complaint, Open and Click events whereas the inbound webhook only
accepts inbound email events/messages.

## Listening for events

The controller triggers consistent events that you can listen for using Zend's Event Manager package. Bounces are 
subdivided into hard bounces and soft bounces, so, you may choose to log soft bounces and react in a different way to a
hard bounce. All of the event names are listed as constants in `\NetgluePostmark\EventManager\AbstractEvent` and are:

```php
const EVENT_HARD_BOUNCE    = 'postmark.event.hard_bounce';
const EVENT_SOFT_BOUNCE    = 'postmark.event.soft_bounce';
const EVENT_BOUNCE_OTHER   = 'postmark.event.bounce_other';
const EVENT_OPEN           = 'postmark.event.open';
const EVENT_CLICK          = 'postmark.event.click';
const EVENT_DELIVERY       = 'postmark.event.delivery';
const EVENT_SPAM_COMPLAINT = 'postmark.event.spam_complaint';
const EVENT_INBOUND        = 'postmark.event.inbound';
```

### Example Logging Listener

There is an example, aggregate listener in `\NetgluePostmark\Listener\LoggingListener` that you can attach by reading the
comments in the dist config file `config/postmark.local.php.dist`.

Events are triggered by `\NetgluePostmark\Service\EventEmitter` - Using a delegator factory targeting that is the 
easiest way of subscribing to events as your listeners will only be retrieved from the container if a post is made to
the webhook. 

For docs on writing listeners, refer to the [Zend Event Manager Docs](https://docs.zendframework.com/zend-eventmanager/).


## Test

`cd` to wherever the module is installed, issue a `composer install` followed by a `composer test`.

## Contributions

PR's are welcomed. Please write tests for new features.

## Support

You're welcome to file issues, but please understand that finding the time to answer support requests is very limited
so there might be a long wait for an answer.


## About

[Netglue makes websites and apps in Devon, England](https://netglue.uk).
We hope this is useful to you and we’d appreciate feedback either way :)

