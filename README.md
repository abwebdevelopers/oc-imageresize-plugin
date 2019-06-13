# Image Resize

Resize and transform image on the fly in twig



## Requirements

- October CMS
- PHP >= 7.0
- Fileinfo Extension
- GD Library (>= 2.0) **or** Imagick PHP extension (>= 6.5.7)


## Getting started

- [Installation (into October CMS)]()
- [October CMS usage]()


### Installation (into October CMS)

To install this into your October site, either visit the plugin on October's Marketplace and click Add to Project.

Using composer: `composer require abwebdevelopers/oc-imageresize-plugin`


### October CMS usage

This plugin utilises [Intervention Image](https://github.com/Intervention/image)'s magical powers to easily resize and transform your images with ease, allow us to create a wrapper for it. Please note it does not do everything intervention/image does, however a fair few features are available.

**Basic Resizing**

Resizing requires at least one of the two dimension arguments, ` | resize(width, height)`
```
Resize to W 1000px * H 700px:
<img src="{{ image | media | resize(1000, 700) }}">

Resize to W 1000px * H auto:
<img src="{{ image | media | resize(1000) }}">

Resize to W auto * H 700px:
<img src="{{ image | media | resize(null, 700) }}">
```

**Resizing Modes**

Resizing Modes are almost synonymous to CSS 3 `background-size` modes to make it easier to remember. Available options are: `auto` (default), `cover`, & `contain`, each doing the same as their CSS equivalent, with one additional mode: `stretch` which behaves how a basic `<img>` element would:

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

A few image adjustment tools have been implemented into this plugin, which utilise their intervention/image counterparts:

Usage of the modifiers is simple, either add them in a `key: value` fashion in the 3rd argument of the resize filter, or by using the modify filter, as such:

```
<img src="{{ image | media | resize(1000, 700, { modifier: value }) }}">
<img src="{{ image | media | modify({ modifier: value }) }}"> <!-- Same size, just modified -->
```

| Modifier Name | Code       | Rules                  | Examples                 | Details |
| ------------- | ---------- | ---------------------- | ------------------------ | ------- |
| Blur          | blur       | min:0 max:100          | `0`, `50`, `100`         | Blurs the image
| Sharpen       | sharpen    | min:0 max:100          | `0`, `50`, `100`         | Sharpens the image
| Brightness    | brightness | min:-100 max:100       | `-100`, `50`, `100`      | Brightens (or darkens) the image
| Contrast      | contrast   | min:-100 max:100       | `-100`, `50`, `100`      | Increases/decreases the contrast of the image
| Pixelate      | pixelate   | min:1 max:1000         | `1`, `500`, `1000`       | Pixelates the image
| Greyscale     | greyscale  | accepted               | `true`, `1`              | See [accepted](https://octobercms.com/docs/services/validation#rule-accepted) rule. Sets the image mode to greyscale |
| Invert        | invert     | accepted               | `true`, `1`              | See [accepted](https://octobercms.com/docs/services/validation#rule-accepted) rule. Inverts all image colors |
| Opacity       | opacity    | min:0 max:100          | `0`, `50`, `100`         | Set the opacity of the image
| Rotate        | rotate     | min:0 max:360          | `45`, `90`, `360`        | Rotate the image (width / height does not constrain the rotated image, the image is resized prior to modifications)
| Flip          | flip       | 'h' or 'v'             | `h`, `v`                 | Flip horizontally (h) or vertically (v) |
| Background    | background | Hex color              | `#fff`, `#123456`, `000` | Set a background color - Hex color (with or without hashtag) |
| Colorize      | colorize   | string (format: r,g,b) | `255,0,0`, `0,50,25`     | Colorize the image. String containing 3 numbers (0-255), comma separated |

A couple examples from the above:
```
<img src="{{ image | media | resize(1000, 700, { brightness: 50 }) }}">
<img src="{{ image | media | resize(1000, 700, { invert: true }) }}">
<img src="{{ image | media | resize(1000, 700, { rotate: 45 }) }}">
<img src="{{ image | media | resize(1000, 700, { background: '#fff' }) }}">
<img src="{{ image | media | resize(1000, 700, { colorize: '65,35,5' }) }}">
```

### Bugs and New Features

Please feel free to open PRs and/or issues relating to any bugs or features so that everyone can benefit from them.


### Special thanks to

- [Intervention Image](https://github.com/Intervention/image)
