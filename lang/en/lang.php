<?php

return [
    'plugin' => [
        'name' => 'Image Resizer',
        'description' => 'Adds filters for templates, allowing you to resize, crop, etc, images on the fly with caching',
    ],
    'settings' => [
        'tabs' => [
            'main' => 'Main',
            'filters' => 'Filters'
        ],
        'sections' => [
            '_404' => [
                'label' => 'Image Not Found',
                'comment' => 'Specify what to do when an image is not found'
            ]
        ],
        'fields' => [
            'driver' => [
                'label' => 'Image Driver for PHP',
                'comment' => 'Choose the image driver (only supports drivers supported by intervention\image library)',
                'options' => [
                    'gd' => 'GD Library',
                    'imagick' => 'Imagick Extension'
                ],
            ],
            'mode' => [
                'label' => 'Default resizing mode',
                'comment' => 'Choose the different mode to use when resizing to a specific size (CSS "background-size" options are supported, with an additional mode "stretch" which acts like an img element)',
                'options' => [
                    'auto' => 'Auto',
                    'contain' => 'Contain',
                    'cover' => 'Cover',
                    'stretch' => 'Stretch',
                ],
            ],
            'image_not_found' => [
                'label' => '404 Image Source',
                'comment' => 'Select a different image to be displayed when images are not found',
            ],
            'image_not_found_background' => [
                'label' => '404 Image Background Color',
                'comment' => 'Background color for the image above',
            ],
            'image_not_found_mode' => [
                'label' => '404 Image Resize Mode',
                'comment' => 'Resizing mode for image above',
            ],
            'image_not_found_quality' => [
                'label' => '404 Image Quality',
                'comment' => 'Default output quality for the image above',
            ],
            'format' => [
                'label' => 'Default Output Format',
                'comment' => 'Select the default format to resize/export images to',
                'options' => [
                    'auto' => 'Automatic (Use input format)',
                    'jpg' => 'JPG',
                    'png' => 'PNG',
                    'webp' => 'WEBP',
                    'bmp' => 'BMP',
                    'gif' => 'GIF',
                    'ico' => 'ICO',
                ]
            ],
            'background' => [
                'label' => 'Default Background Color',
                'comment' => 'When converting alpha-channel images (png, webp) to flat images (jpg, etc), define the default color of the background',
            ],
            'filters' => [
                'label' => 'Filters',
                'prompt' => 'Add a new Filter',
                'fields' => [
                    'code' => [
                        'label' => 'Code',
                        'comment' => 'Used in reference when using filters',
                    ],
                    'description' => [
                        'label' => 'Description',
                        'comment' => 'Description of this filter',
                    ],
                    'rules' => [
                        'label' => 'Rules / Modifications',
                        'prompt' => 'Add a new rule',
                        'fields' => [
                            'modifier' => [
                                'label' => 'Modifier Rule',
                                'comment' => 'Add a modifier',
                                'options' => [
                                    'width' => 'Width',
                                    'height' => 'Height',
                                    'min_width' => 'Min Width',
                                    'min_height' => 'Min Height',
                                    'max_width' => 'Max Width',
                                    'max_height' => 'Max Height',
                                    'blur' => 'Blur',
                                    'sharpen' => 'Sharpen',
                                    'brightness' => 'Brightness',
                                    'contrast' => 'Contrast',
                                    'pixelate' => 'Pixelate',
                                    'greyscale' => 'Greyscale',
                                    'invert' => 'Invert',
                                    'opacity' => 'Opacity',
                                    'rotate' => 'Rotate',
                                    'flip' => 'Flip',
                                    'background' => 'Background',
                                    'colorize' => 'Colorize',
                                    'format' => 'Format',
                                    'quality' => 'Quality',
                                    'mode' => 'Mode',
                                ]
                            ],
                            'value' => [
                                'label' => 'Modifier Value',
                                'comment' => 'Specify the value for this modifier',
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],
];
