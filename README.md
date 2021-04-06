# Robo Drupal Coding

Extension to apply Drupal Coding Standards with commands [Robo](http://robo.li/).

## Table of contents

- [Installation](#installation)
- [Usage](#usage)

## Installation

Add `"amarie88/robo-drupal-coding": "~0.1"` to your composer.json:

```json
{
    "require-dev": {
        "amarie88/robo-drupal-coding": "~0.1"
    }
}
```

and execute `composer update`.

OR

```bash
composer require --dev amarie88/robo-drupal-coding:~0.1
```

## Usage

Check Drupal coding standards and best practices:

```bash
vendor/bin/robo drupal-coding:phpcs
```

Check Drupal code with `drupal-check`:

```bash
vendor/bin/robo drupal-coding:check
```
