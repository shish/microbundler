MicroBundler
============
A tiny library to turn a bunch of CSS/JS files into single CSS/JS
bundles, with source maps.

```php
use \MicroBundler\MicroBundler;

$mb = new MicroBundler();
$mb->addSource("input1.css");
$mb->addSource("input2.css");
$mb->addSource("dynamic.css", ".my_class { color: red; }");
$mb->save("output.css");  // writes output.css + output.css.map
```
