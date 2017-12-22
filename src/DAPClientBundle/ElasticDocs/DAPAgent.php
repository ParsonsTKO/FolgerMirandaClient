<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 5/18/17
 * Time: 2:49 PM
 */

namespace DAPClientBundle\ElasticDocs;
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPAgent
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */


class DAPAgent
{
    /**
     * @ES\Property(type="keyword")
     */
    public $name;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $description;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $uri;

    public function __construct($inName = null, $inDesc = null, $inUri = null)
    {
        //"Collier, John Payne, 1789-1883,", "former owner", "http://id.loc.gov/authorities/names/n50029197"

        if(isset($inName)) {
            $this->name = $inName;
        }

        if(isset($inDesc)) {
            $this->description = $inDesc;
        }

        if(isset($inUri)) {
            $this->uri = $inUri;
        }
    }
}