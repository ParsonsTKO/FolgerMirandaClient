<?php
namespace DAPClientBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPAbout
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPAbout
{
    /**
     * @ES\Property(type="text")
     */
    public $uri;


    /**
     * @ES\Property(type="text")
     */
    public $description;

    public function __construct($inUri = null, $inDesc = null) {
        if(isset($inUri)) {
            $this->uri = intval($inUri);
        }
        if(isset($inDesc)) {
            $this->description = intval($inDesc);
        }
    }

}
