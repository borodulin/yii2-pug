# Pug renderer for Yii2 framework

[![Latest Version](https://img.shields.io/packagist/v/conquer/pug.svg)](https://packagist.org/packages/conquer/pug)
[![Total Downloads](https://img.shields.io/packagist/dt/conquer/pug.svg)](https://packagist.org/packages/conquer/pug)
[![CI](https://github.com/borodulin/yii2-pug/actions/workflows/ci.yml/badge.svg)](https://github.com/borodulin/yii2-pug/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/conquer/pug.svg)](LICENSE)

## Description

Renders `.pug` views with [pug-php](https://github.com/pug-php/pug) 3 (Pug, formerly Jade).

## Requirements

- PHP 7.4 or 8.x
- Yii 2.0.14 or later

## Installation

```bash
composer require conquer/pug:^1.1
```

## Usage

```php
'components' => [
    'view' => [
        'renderers' => [
            'pug' => [
                'class' => conquer\pug\PugRenderer::class,
                'debug' => YII_DEBUG,
            ],
        ],
    ],
],
```

```php
return $this->render('index.pug', ['title' => 'Hello']);
```

```pug
extends layout

block content
  h1= $title
  p= $this->context->id
```

Expressions are PHP: `$this` is the view, as in PHP views. `=` HTML-encodes the value, `!=` outputs it as is.

| Property | Default | Description |
|---|---|---|
| `cachePath` | `@runtime/Pug/cache` | Directory of the compiled templates |
| `cacheDuration` | `0` | Seconds after which a template is compiled again; `0` to keep it until a template changes |
| `debug` | `false` | Compile on every render and pretty-print the HTML |
| `options` | `[]` | [Options](https://phug-lang.com/#options) of pug-php |
| `filters` | `[]` | Filters: `name => callable` |
| `keyWords` | `[]` | Keywords: `name => callable` |

A template is compiled again when it or a template it includes or extends changes.

## License

**conquer/pug** is released under the MIT License. See the bundled [`LICENSE`](LICENSE) for details.
