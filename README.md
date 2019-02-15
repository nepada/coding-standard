Nepada - Coding Standard
========================

[![Build Status](https://travis-ci.org/nepada/coding-standard.svg?branch=master)](https://travis-ci.org/nepada/coding-standard)
[![Downloads this Month](https://img.shields.io/packagist/dm/nepada/coding-standard.svg)](https://packagist.org/packages/nepada/coding-standard)
[![Latest stable](https://img.shields.io/packagist/v/nepada/coding-standard.svg)](https://packagist.org/packages/nepada/coding-standard)

Based on [Consistence - Coding Standard](https://github.com/consistence/coding-standard) and [Slevomat - Coding Standard](https://github.com/slevomat/coding-standard).


Installation
------------

Via Composer:

```sh
$ composer require nepada/coding-standard
```


Usage
-----

You can either use the ruleset as-is, or customize it to suit your needs:

```xml
<?xml version="1.0"?>
<ruleset>
    <config name="installed_paths" value="../../nepada/coding-standard/src"/><!-- relative path from PHPCS source location -->

    <arg value="ps"/><!-- show progress of the run, show sniff names -->
    <arg name="cache" value=".phpcs-cache"/>

    <file>src</file>
    <file>tests</file>

    <rule ref="Nepada">
    </rule>
</ruleset>
```

To check your code base for violations, run PHP CodeSniffer from the command line:

```
vendor/bin/phpcs
```
