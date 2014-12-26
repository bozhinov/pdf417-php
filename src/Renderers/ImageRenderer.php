<?php

namespace BigFish\PDF417\Renderers;

use BigFish\PDF417\BarcodeData;
use BigFish\PDF417\RendererInterface;

use Intervention\Image\Image;

class ImageRenderer extends AbstractRenderer
{
    /** Supported image formats and corresponding MIME types. */
    protected $formats = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    protected $options = [
        'format' => 'png',
        'quality' => 90,
        'scale' => 3,
        'ratio' => 3,
        'padding' => 20,
        'color' => "#000",
        'bgColor' => "#fff",
    ];

    /**
     * {@inheritdoc}
     */
    public function validateOptions()
    {
        $errors = [];

        $format = $this->options['format'];
        if (!isset($this->formats[$format])) {
            $formats = implode(", ", array_keys($this->formats));
            $errors[] = "Invalid option \"format\": \"$format\". Expected one of: $formats.";
        }

        $scale = $this->options['scale'];
        if (!is_numeric($scale) || $scale < 1 || $scale > 20) {
            $errors[] = "Invalid option \"scale\": \"$scale\". Expected an integer between 1 and 20.";
        }

        $ratio = $this->options['ratio'];
        if (!is_numeric($ratio) || $ratio < 1 || $ratio > 10) {
            $errors[] = "Invalid option \"ratio\": \"$ratio\". Expected an integer between 1 and 10.";
        }

        $padding = $this->options['padding'];
        if (!is_numeric($padding) || $padding < 0 || $padding > 50) {
            $errors[] = "Invalid option \"padding\": \"$padding\". Expected an integer between 0 and 50.";
        }

        // Check colors
        $image = new Image();
        $color = $this->options['color'];
        $bgColor = $this->options['bgColor'];

        try {
            $image->parseColor($color);
        } catch (\Exception $ex) {
            $errors[] = "Invalid option \"color\": \"$color\". Supported color formats: \"#000000\", \"rgb(0,0,0)\", or \"rgba(0,0,0,0)\"";
        }

        try {
            $image->parseColor($bgColor);
        } catch (\Exception $ex) {
            $errors[] = "Invalid option \"bgColor\": \"$bgColor\". Supported color formats: \"#000000\", \"rgb(0,0,0)\", or \"rgba(0,0,0,0)\"";
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        $format = $this->options['format'];
        return $this->formats[$format];
    }

    /**
     * {@inheritdoc}
     */
    public function render(BarcodeData $data)
    {
        $pixelGrid = $data->getPixelGrid();
        $height = count($pixelGrid);
        $width = count($pixelGrid[0]);

        $options = $this->options;

        $img = Image::canvas($width, $height, $options['bgColor']);

        // Render the barcode
        foreach ($pixelGrid as $y => $row) {
            foreach ($row as $x => $value) {
                if ($value) {
                    $img->pixel($options['color'], $x, $y);
                }
            }
        }

        // Apply scaling & aspect ratio
        $width *= $options['scale'];
        $height *= $options['scale'] * $options['ratio'];
        $img->resize($width, $height);

        // Add padding
        $width += 2 * $options['padding'];
        $height += 2 * $options['padding'];
        $img->resizeCanvas($width, $height, 'center', false, '#fff');

        return $img->encode($options['format']);
    }
}
