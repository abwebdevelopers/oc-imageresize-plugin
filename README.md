# October CMS Image Resize Plugin

Resize and transform images on the fly in Twig and October CMS.

## Requirements

- October CMS
- PHP 7.0 or above
- PHP `fileinfo` extension
- PHP `gd` extension **or** `imagick` extension

## Getting started

- [Installation](#installation)
- [October CMS usage](#october-cms-usage)

### Installation

You can install this plugin in a number of ways:

#### Via Composer

Run the following commands in your October CMS project folder to install the plugin.

```bash
composer require abwebdevelopers/oc-imageresize-plugin
php artisan october:up
```

#### Via Updates & Plugins screen

In the October CMS backend, you can navigate to *Settings > Updates & Plugins* and then click the 
*Install Plugins* button to install a plugin to your October CMS install. Add
`ABWebDevelopers.ImageResize` to the search box to be able to select this plugin and install it.

#### Via command-line

Run the following command in your October CMS project folder to install the plugin.

```bash
php artisan plugin:install ABWebDevelopers.ImageResize
```

### October CMS usage

This plugin utilises [Intervention Image](https://github.com/Intervention/image)'s magical powers to resize and transform your images with ease. Please note that this plugin does not cover every feature of the Intervention Image library.

#### Basic Resizing

**Twig Filter:** `| resize(int $width, int $height, array $options)`

Basic resizing in Twig is done using the `| resize` filter. Resizing requires at least one of the two dimension arguments.

```
Resize to width 1000px and height 700px:
<img src="{{ image | media | resize(1000, 700) }}">

Resize to width 1000px and automatically calculate height:
<img src="{{ image | media | resize(1000) }}">

Resize to height 700px and automatically calculate width:
<img src="{{ image | media | resize(null, 700) }}">
```

A third argument is available, `options`, which allows you specify the resizing mode, along with any other image modifications which are detailed below.

#### Resizing Modes

Resizing modes are almost synonymous to CSS3 `background-size` modes to make it easier to remember. Available options are: `auto` (default), `cover` and `contain`, each doing the same as their CSS equivalent, with one additional mode: `stretch` which behaves how a basic `<img>` element would:

```
Default (image is displayed in its original size):
<img src="{{ image | media | resize(1000, 700, { mode: 'auto' }) }}">

Resize the background image to make sure the image is fully visible
<img src="{{ image | media | resize(1000, 700, { mode: 'contain' }) }}">

Resize the background image to cover the entire container, even if it has to stretch the image or cut a little bit off one of the edges
<img src="{{ image | media | resize(1000, 700, { mode: 'cover' }) }}">

Stretch and morph it to fit exatly in the defined dimensions
<img src="{{ image | media | resize(1000, 700, { mode: 'stretch' }) }}">
```

**Further Modifications**

A few image adjustment tools and filters have been implemented into this plugin, which utilise their Intervention Image library counterparts.

Usage of the modifiers is simple, either add them in a `key: value` fashion in the 3rd argument of the resize filter, or by using the modify filter, as such:

```
<img src="{{ image | media | resize(1000, 700, { modifier: value }) }}">
<img src="{{ image | media | modify({ modifier: value }) }}"> <!-- Same size, just modified -->
```

| Modifier Name | Code       | Rules                  | Examples                 | Details |
| ------------- | ---------- | ---------------------- | ------------------------ | ------- |
| Format        | format     | in:jpg,png,webp,bmp,gif,ico,auto | `jpg`, `png`, `auto`, ...     | Change the format of the image.
| Blur          | blur       | min:0 max:100          | `0`, `50`, `100`         | Blurs the image
| Sharpen       | sharpen    | min:0 max:100          | `0`, `50`, `100`         | Sharpens the image
| Brightness    | brightness | min:-100 max:100       | `-100`, `50`, `100`      | Brightens (or darkens) the image
| Contrast      | contrast   | min:-100 max:100       | `-100`, `50`, `100`      | Increases/decreases the contrast of the image
| Pixelate      | pixelate   | min:1 max:1000         | `1`, `500`, `1000`       | Pixelates the image
| Greyscale     | greyscale/grayscale  | accepted               | `true`, `1`              | See [accepted](https://octobercms.com/docs/services/validation#rule-accepted) rule. Sets the image mode to greyscale. Both codes are accepted (one just maps to the other) |
| Invert        | invert     | accepted               | `true`, `1`              | See [accepted](https://octobercms.com/docs/services/validation#rule-accepted) rule. Inverts all image colors |
| Opacity       | opacity    | min:0 max:100          | `0`, `50`, `100`         | Set the opacity of the image
| Rotate        | rotate     | min:0 max:360          | `45`, `90`, `360`        | Rotate the image (width / height does not constrain the rotated image, the image is resized prior to modifications)
| Flip          | flip       | 'h' or 'v'             | `h`, `v`                 | Flip horizontally (h) or vertically (v) |
| Background    | fill/background | Hex color              | `#fff`, `#123456`, `000` | Set a background color - Hex color (with or without hashtag). Both codes are accepted (one just maps to the other) |
| Colorize      | colourise/colorize   | string (format: r,g,b) | `255,0,0`, `0,50,25`     | Colorize the image. String containing 3 numbers (0-255), comma separated. Both codes are accepted (one just maps to the other) |

A couple examples from the above:
```
<img src="{{ image | media | resize(1000, 700, { brightness: 50 }) }}">
<img src="{{ image | media | resize(1000, 700, { invert: true }) }}">
<img src="{{ image | media | resize(1000, 700, { rotate: 45 }) }}">
<img src="{{ image | media | resize(1000, 700, { background: '#fff' }) }}">
<img src="{{ image | media | resize(1000, 700, { colorize: '65,35,5' }) }}">
```

### Filters (templates for configuration)

Filters in the Image Resize plugin, while following a similar concept to filters in Intervention Image, are handled differently in this plugin.

Filters are specified in the *Settings > Image Resizer* page. By clicking the *Filters* tab at the top, you can specify a filter "code" which can apply a set of enhancements and modifications to an image. Once saved, you can then use the `filter` option in the `resize` and `modify` Twig filters to specify the filter to use.

 A common example would be a basic thumbnail - you want this to always be `format: jpg`, `mode: cover`, `quality: 60`, `max_width: 200`, `max_height: 200` and maybe `background: #fff`.

With filters, you can specify the above, call it something useful like `thumbnail`, then simply do the following:
```
<!-- display thumbnail -->
<img src="{{ image | media | modify({ filter: 'thumbnail' }) }}">
or
<!-- display thumbnail, but 150x150 -->
<img src="{{ image | media | resize(150, 150, { filter: 'thumbnail' }) }}">
```

Which will use the predefined list of modifiers and have them overwritten by any that are supplied, for example:

```
<img src="{{ image | media | modify({ filter: 'thumbnail', brightness: -30, contrast: 30 }) }}">
```

> There are a couple new modifiers for filters which include: `min_width`, `max_width`, `min_height`, `max_height` which all act as constraints for the dimensions of the images using filters.
>
>Should you use one, please note that if you use it with the `| resize(w, h)` function, your supplied dimensions will be ignored *if* they are out of bounds of the constraints.


**Using the library in PHP**

Should you want to implement your own use of this library outside of Twig, you can use it in a very similar manner:

```
$resizer = new \ABWebDevelopers\ImageResize\Classes\Resizer($image);
$resizer->resize(800, 250, [
    'rotate' => 45
]);
// $resizer->render(); // only use this if you intend on aborting the script immediately at this point
```

Which is synonymous to:

```
<img src="{{ image | resize(800, 250, { rotate: 45 }) }}">
```

### Bugs and New Features

We encourage you to open PRs and/or issues relating to any bugs or features so that everyone can benefit from them.

### Special thanks to

- [Intervention Image](https://github.com/Intervention/image)
