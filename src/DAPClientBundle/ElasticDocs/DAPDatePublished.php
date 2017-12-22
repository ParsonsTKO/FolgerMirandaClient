<?php
namespace DAPClientBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPDatePublished
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPDatePublished
{
    /**
     * @var integer
     *
     * @ES\Property(type="integer")
     */
    public $startDate;

    /**
     * @var integer
     *
     * @ES\Property(type="integer")
     */
    public $endDate;

    public function __construct($instart = null, $inend = null) {
        if(isset($instart)) {
            $this->startDate = intval($instart);
        }
        if(isset($inend)) {
            $this->endDate = intval($inend);
        }
    }
}