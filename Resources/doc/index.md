Getting Started
===============

## Prerequisites

This version of the bundle requires Symfony 3.

## Installation

Installation is a quick, 2 step process:

1. Download the bundle using composer
2. Enable the bundle

### Step 1: Download the bundle using composer

Tell composer to download the bundle by running the command:

```bash
$ composer require klipper/default-value-bundle
```

Composer will install the bundle to your project's `vendor/klipper` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Klipper\Bundle\DefaultValueBundle\KlipperDefaultValueBundle(),
    );
}
```

### Next Steps

Now that you have completed the basic installation and configuration of the
Klipper DefaultValueBundle, you are ready to learn about usages of the bundle.
