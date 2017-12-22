<?php
namespace DAPClientBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPLocation
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPLocation
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $type;


    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $address;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $addressLocality;

    /**
     * @ES\Property(type="keyword")
     */
    public $addressCountry;

    public function __construct($inType = null, $inAddress = null, $inAddressLocality = null, $inAddressCountry = null)
    {
        if(isset($inType)) {
            $this->uri = $inType;
        }
        if(isset($inAddress)) {
            $this->address = $inAddress;
        }
        if(isset($inAddressLocality)) {
            $this->addressLocality = $inAddressLocality;
        }
        if(isset($inAddressCountry)) {
            $this->addressCountry = $inAddressCountry;
        }
    }
}