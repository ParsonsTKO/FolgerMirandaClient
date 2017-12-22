<?php
/**
 * File containing the DAPClientExtension class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPClientBundle\Twig;

class DAPClientExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('image_info', array($this, 'getImageInfo')),
        );
    }
    
    public function getImageInfo($path)
    {
        try {
            $types = array(
                1 => 'GIF',
                2 => 'JPG',
                3 => 'PNG',
                4 => 'SWF',
                5 => 'PSD',
                6 => 'BMP',
                7 => 'TIFF(intel byte order)',
                8 => 'TIFF(motorola byte order)',
                9 => 'JPC',
                10 => 'JP2',
                11 => 'JPX',
                12 => 'JB2',
                13 => 'SWC',
                14 => 'IFF',
                15 => 'WBMP',
                16 => 'XBM'
            );

            $image = get_headers($path, 1);

            $imageKb = $image["Content-Length"]/1024;



            list($width, $height, $type) = getimagesize($path);

            return array(
                'width' => $width,
                'height' => $height,
                'type' => $types[$type],
                'size' => number_format($imageKb,0) . "kb",
            );
        } catch (\Exception $e) {
            return array (
                'width' => null,
                'height' => null,
                'type' => null,
                'size' => null,
            );
        }
    }
}
