<?php

namespace BoldMinded\Ansel\Tags;

use BoldMinded\Ansel\Model\InternalTagParams;
use BoldMinded\Ansel\Service\AnselImages\ImagesTag;
use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Fieldtypes\Generic;
use Expressionengine\Coilpack\Fieldtypes\Modifiers\Generic as GenericModifier;
use Expressionengine\Coilpack\Support\Parameter;

class Url extends Generic
{
    private ImagesTag $imagesTag;
    private array $imageVars;

    public function __construct(
        string $name,
        int $id,
        ImagesTag $imagesTag,
        array $imageVars
    ) {
        parent::__construct($name, $id);

        $this->imagesTag = $imagesTag;
        $this->imageVars = $imageVars;
    }

    public function callModifier(FieldtypeOutput $content, string $name, array $parameters = [])
    {
        $internalTagParams = $this->getInternalTagParams($parameters);

        $internalTag = ee('ansel:AnselInternalTag');

        return $internalTag->processTag(
            $this->imagesTag->getFile($this->imageVars['file_id']),
            $this->imagesTag->getSource((int) $this->imageVars['upload_location_id']),
            $internalTagParams
        );
    }

    private function getInternalTagParams(array $parameters = []): InternalTagParams
    {
        $internalTagParams = ee('ansel:InternalTagParams');;

        // Set the params to the array
        foreach ($parameters as $param => $value) {
            if ($internalTagParams->hasProperty($param)) {
                $internalTagParams->setProperty($param, $value);
            }
        }

        return $internalTagParams;
    }

    public function defineModifiers(): array
    {
        return [
            new GenericModifier($this, [
                'name' => 'resize',
                'description' => 'Resize an image on the fly.',
                'parameters' => [
                    new Parameter([
                        'name' => 'width',
                        'type' => 'string',
                        'description' => 'Set the width.',
                    ]),
                    new Parameter([
                        'name' => 'height',
                        'type' => 'string',
                        'description' => 'Set the height.',
                    ]),
                    new Parameter([
                        'name' => 'crop',
                        'type' => 'bool',
                        'description' => 'Crop the image from the center based on the new width and height.',
                    ]),
                    new Parameter([
                        'name' => 'background',
                        'type' => 'string',
                        'description' => 'Set a background color. Access valid hex codes.',
                    ]),
                    new Parameter([
                        'name' => 'force_jpg',
                        'type' => 'bool',
                        'description' => 'Force the output image to be a .jpg, even if the source is a .webp, .png or .gif',
                    ]),
                    new Parameter([
                        'name' => 'force_webp',
                        'type' => 'bool',
                        'description' => 'Force the output image to be a .webp, even if the source is a .jpg, .png or .gif',
                    ]),
                    new Parameter([
                        'name' => 'quality',
                        'type' => 'string',
                        'description' => 'Numeric value between 0-100. Defaults to 80.',
                    ]),
                    new Parameter([
                        'name' => 'scale_up',
                        'type' => 'bool',
                        'description' => 'Scale up the image proportionally.',
                    ]),
                ],
            ]),
        ];
    }

}
