<?php
namespace DAPClientBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DAPGenre
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPGenre
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $search;


    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $terms;

    /**
     * @ES\Property(type="text")
     */
    public $uri;

    public function __construct($inSearch = null, $inTerms = null, $inUri = null) {
        $this->terms = new ArrayCollection();

        if(isset($inSearch)) {
            $this->search = $inSearch;
        }

        if(isset($inTerms) && is_array($inTerms)) {
            $this->terms = $inTerms;
        }

        if(isset($inUri)) {
            $this->uri = $inUri;
        }

    }

}
