<?php
namespace DAPClientBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPCreator
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPCreator
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $givenName;


    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $familyName;

    /**
     * @ES\Property(type="text")
     */
    public $authority;

    public function __construct($inGivenName = null, $inFamilyName = null, $inAuthority = null)
    {
        $this->givenName = $inGivenName;
        $this->familyName = $inFamilyName;
        $this->authority = $inAuthority;
    }
}