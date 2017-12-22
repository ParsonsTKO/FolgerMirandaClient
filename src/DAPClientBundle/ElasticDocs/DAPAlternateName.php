<?php
namespace DAPClientBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPAlternateName
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPAlternateName
{
    /**
     * @ES\Property(type="keyword")
     */
    public $uri;


    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $description;

    public function __construct($inUri = null, $inDescription = null)
    {
        if(isset($inUri)) {
            $this->uri = $inUri;
        }
        if(isset($inDescription)) {
            $this->description = $inDescription;
        }
    }

}