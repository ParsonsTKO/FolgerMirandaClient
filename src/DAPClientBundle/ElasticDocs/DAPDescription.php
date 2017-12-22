<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 5/18/17
 * Time: 2:29 PM
 */

namespace DAPClientBundle\ElasticDocs;
use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPDescription
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */


class DAPDescription
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $text;

    public function __construct($invar="") {
        $this->text = $invar;
    }
}