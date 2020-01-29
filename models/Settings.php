<?php

namespace ABWebDevelopers\ImageResize\Models;

use Model;
use Validator;
use October\Rain\Database\Traits\Validation;

class Settings extends Model
{
    use Validation;

    public const DEFAULT_IMAGE_NOT_FOUND = 'plugins/abwebdevelopers/imageresize/assets/image-not-found.png';

    /**
     * Implement settings model
     *
     * @var array
     */
    public $implement = [
        'System.Behaviors.SettingsModel'
    ];

    /**
     * Define settings code
     *
     * @var string
     */
    public $settingsCode = 'abwebdevelopers_imageresize';

    /**
     * Define settings fields
     *
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    /**
     * Define validation rules for settings
     *
     * @var array
     */
    public $rules = [
        'driver' => 'required|string|in:gd,imagick',
        'background' => 'required|string|regex:/^#([a-f0-9]{3}){1,2}$/i',
        'mode' => 'required|string|in:auto,cover,contain,stretch',
        'quality' => 'required|min:1|max:100',
        'format' => 'required|string|in:auto,jpg,png,bmp,gif,ico,webp',
        'filters.*.modifier' => 'in:width,height,min_width,min_height,max_width,max_height,blur,sharpen,brightness,contrast,pixelate,greyscale,invert,opacity,rotate,flip,background,colorize,format,quality,mode',
        'filters.*.value' => 'string|min:0|max:10',
        'image_not_found' => 'nullable',
        'image_not_found_background' => 'required|regex:/^#([a-f0-9]{3}){1,2}$/i',
        'image_not_found_mode' => 'required|in:auto,cover,contain,stretch',
        'image_not_found_quality' => 'required|min:1|max:100',
    ];

    /**
     * Define cast types for each modifier type
     *
     * @var array
     */
    protected $castModifiers = [
        'width' => 'int',
        'height' => 'int',
        'min_width' => 'int',
        'min_height' => 'int',
        'max_width' => 'int',
        'max_height' => 'int',
        'blur' => 'int',
        'sharpen' => 'int',
        'brightness' => 'int',
        'contrast' => 'int',
        'pixelate' => 'int',
        'greyscale' => 'bool',
        'invert' => 'bool',
        'opacity' => 'int',
        'rotate' => 'int',
        'flip' => 'string',
        'background' => 'string',
        'colorize' => 'string',
        'format' => 'string',
        'quality' => 'int',
        'mode' => 'string',
    ];

    /**
     * Define validation rules for each modifier type
     *
     * @var array
     */
    public $modifierRules = [
        'width' => 'bail|int|min:0|max:10000',
        'height' => 'bail|int|min:0|max:10000',
        'min_width' => 'bail|int|min:0|max:10000',
        'min_height' => 'bail|int|min:0|max:10000',
        'max_width' => 'bail|int|min:0|max:10000',
        'max_height' => 'bail|int|min:0|max:10000',
        'blur' => 'bail|int|min:0|max:100',
        'sharpen' => 'bail|int|min:0|max:100',
        'brightness' => 'bail|int|min:-100|max:100',
        'contrast' => 'bail|int|min:-100|max:100',
        'pixelate' => 'bail|int|min:1|max:1000',
        'greyscale' => 'bail|accepted',
        'invert' => 'bail|accepted',
        'opacity' => 'bail|int|min:0|max:100',
        'rotate' => 'bail|int|min:0|max:360',
        'flip' => 'bail|string|in:v,h',
        'background' => 'bail|string|regex:/^#([a-f0-9]{3}){1,2}$/',
        'colorize' => 'bail|string|regex:/^(?:-?(?:100|[0-9]{2})),(?:-?(?:100|[0-9]{2})),(?:-?(?:100|[0-9]{2}))$/',
        'format' => 'bail|string|in:auto,jpg,png,bmp,gif,ico,webp',
        'quality' => 'bail|int|min:1|max:100',
        'mode' => 'bail|string|in:auto,cover,contain,stretch',
    ];

    /**
     * Before validating, cast modifier values to their respective type
     *
     * @return void
     */
    public function beforeValidate()
    {
        if (!empty($this->value)) {
            $data = $this->value;

            if (!empty($data['filters'])) {
                foreach ($data['filters'] as $filterId => $filter) {
                    foreach ($filter['rules'] as $ruleId => $rule) {
                        switch ($this->castModifiers[$rule['modifier']]) {
                            case 'int':
                                $rule['value'] = (int) $rule['value'];
                                break;
                            case 'float':
                                $rule['value'] = (float) $rule['value'];
                                break;
                            case 'bool':
                                $rule['value'] = (bool) ($rule['value'] === '1' || $rule['value'] === 'true');
                                break;
                            case 'string':
                            default:
                                $rule['value'] = (string) $rule['value'];
                                break;
                        }
                        $filter['rules'][$ruleId] = $rule;
                    }
                    $data['filters'][$filterId] = $filter;
                }
            }
        }
    }

    /**
     * Before saving, validate the modifiers which have their own validation logic. Throw an
     * exception on fail.
     *
     * @return void
     */
    public function beforeSave()
    {
        if (!empty($this->value)) {
            $data = $this->value;
            if (!empty($data['filters'])) {
                foreach ($data['filters'] as $filterId => $filter) {
                    $validationData = [];
                    $validationRules = [];

                    foreach ($filter['rules'] as $ruleId => $rule) {
                        $validationData[$rule['modifier']] = $rule['value'];
                        $validationRules[$rule['modifier']] = $this->modifierRules[$rule['modifier']];
                    }

                    $validator = Validator::make($validationData, $validationRules);

                    if (!$validator->passes()) {
                        $errors = [];
                        foreach ($validator->messages()->toArray() as $field => $fieldErrors) {
                            $errors[] = implode(", ", $fieldErrors);
                        }
                        throw new \Exception("Unable to save Settings: " . implode("\n", $errors));
                    }
                }
            }
        }
    }
}
