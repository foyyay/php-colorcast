ColorCast
=========

Given a configuration of hue points to named saturation and value entries you can provide a hue and the saturation and value will be interpolated and the resulting color returned as a hex string.

Install

```
composer require foyyay/colorcast;
```

Use

```
use Foyyay\ColorCast\ColorCast;

$caster = new ColorCast(config);
$colors = $caster->fromHue(90);
// or
$colors = $caster->fromColor('#87d936');
```

An example config could look like:

```PHP
$config = [
    "0" => [
        "primary" => ["saturation" => 80, "value" => 70],
        "accent" => ["saturation" => 75, "value" => 85],
    ],
    "20" => [
        "primary" => ["saturation" => 40, "value" => 50],
        "accent" => ["saturation" => 80, "value" => 90],
    ],
];
```

So given a hue of 90 the you'll get back an array with two keys, "primary", and "accent". The hue for both colors will be 90, the saturation for primary will be 60 and the value will be 60.

You may add as many hue values from 0 to < 360 as you want. You can add as many named configs as you want.

### Thank you.
Bootstrapped using the [Composer Library Template](https://github.com/buonzz/composer-library-template). 
