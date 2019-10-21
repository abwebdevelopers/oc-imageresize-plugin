<?php

return [
    'plugin' => [
        'name' => 'Kuvamuunnin',
        'description' => 'Lisää suotimia sivupohjiin mahdollistaen kuvien kokomuunnokset, rajaukset, jne. (hyödyntää välimuistia)',
    ],
    'permissions' => [
        'tab' => 'Kuvan muunnin',
        'access_settings' => 'Pääsy kuvamuutimen asetuksiin',
    ],
    'settings' => [
        'tabs' => [
            'main' => 'Asetukset',
            'filters' => 'Suotimet'
        ],
        'sections' => [
            '_404' => [
                'label' => 'Kuvaa ei löydy',
                'comment' => 'Määrittele toiminto tilanteeseen, kun kuvaa ei löydy'
            ]
        ],
        'fields' => [
            'driver' => [
                'label' => 'PHP-kuvaohjain',
                'comment' => 'Valitse kuvaohjain (tukee vain kuvakirjaston omia ominaisuuksia)',
                'options' => [
                    'gd' => 'GD Library',
                    'imagick' => 'Imagick Extension'
                ],
            ],
            'mode' => [
                'label' => 'Oletus muuntomoodi',
                'comment' => 'Choose the different mode to use when resizing to a specific size (CSS "background-size" options are supported, with an additional mode "stretch" which acts like an img element)',
                'options' => [
                    'auto' => 'Autom.',
                    'contain' => 'Säilytä',
                    'cover' => 'Täytä',
                    'stretch' => 'Venytä',
                ],
            ],
            'quality' => [
                'label' => 'Oletuslaatu',
                'comment' => 'Oletuslaatu kuville (1-100)'
            ],
            'image_not_found' => [
                'label' => '404 kuvan lähde',
                'comment' => 'Valitse kuva, joka näytetään mikäli alkuperäistä ei löydy',
            ],
            'image_not_found_background' => [
                'label' => '404 kuvan taustaväri',
                'comment' => 'Background color for the image above',
            ],
            'image_not_found_mode' => [
                'label' => '404 kuvan kokomuuntimen moodi',
                'comment' => 'Kokomuuntimen toimintatapa',
            ],
            'image_not_found_format' => [
                'label' => '404 kuvaformaatti',
                'comment' => 'Kuvan formaatti',
            ],
            'image_not_found_quality' => [
                'label' => '404 kuvan laatu',
                'comment' => 'Kuvan laatu',
            ],
            'format' => [
                'label' => 'Oletus kuvaformaatti',
                'comment' => 'Valitse oletus kuvaformaatti',
                'options' => [
                    'auto' => 'automaattinen (käytä alkuperäistä)',
                    'jpg' => 'JPG',
                    'png' => 'PNG',
                    'webp' => 'WEBP',
                    'bmp' => 'BMP',
                    'gif' => 'GIF',
                    'ico' => 'ICO',
                ]
            ],
            'background' => [
                'label' => 'Taustan oletusväri',
                'comment' => 'Määritä taustaväri tilanteisiin, jossa läpinäkyvällä taustalla oleva kuva (png, webp) muunnetaan (jpg, etc) taustalliseksi',
            ],
            'filters' => [
                'label' => 'Suotimet',
                'prompt' => 'Lisää suodin',
                'fields' => [
                    'code' => [
                        'label' => 'Koodi',
                        'comment' => 'Käytetään suotimien referenssinä',
                    ],
                    'description' => [
                        'label' => 'Kuvaus',
                        'comment' => 'Suotimen kuvaus',
                    ],
                    'rules' => [
                        'label' => 'Säännöt / Muokkaukset',
                        'prompt' => 'Lisää uusi sääntö',
                        'fields' => [
                            'modifier' => [
                                'label' => 'Muokkaussääntö',
                                'comment' => 'Lisää muokkain',
                                'options' => [
                                    'width' => 'Leveys',
                                    'height' => 'Korkeus',
                                    'min_width' => 'Min. leveys',
                                    'min_height' => 'Min. korkeus',
                                    'max_width' => 'Maks. leveys',
                                    'max_height' => 'Maks. korkeus',
                                    'blur' => 'Sumenna',
                                    'sharpen' => 'Terävöitä',
                                    'brightness' => 'Kirkkaus',
                                    'contrast' => 'Kontrasti',
                                    'pixelate' => 'Pikselöi',
                                    'greyscale' => 'Harmaasävy',
                                    'invert' => 'Käänteinen väri',
                                    'opacity' => 'Läpinäkyvyys',
                                    'rotate' => 'Pyöritä',
                                    'flip' => 'Käännä',
                                    'background' => 'Tausta',
                                    'colorize' => 'Väritä',
                                    'format' => 'Formaatti',
                                    'quality' => 'Laatu',
                                    'mode' => 'Moodi',
                                ]
                            ],
                            'value' => [
                                'label' => 'Muokkaimen arvo',
                                'comment' => 'Määritä tämän muokkaimen arvo',
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],
];
