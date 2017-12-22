<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 5/18/17
 * Time: 2:53 PM
 */

namespace DAPClientBundle\ElasticDocs;
use ONGR\ElasticsearchBundle\Annotation as ES;


/**
 * Class DAPRelatedItems
 * @package DAPClientBundle\ElasticDocs
 * @ES\Object
 */
class DAPRelatedItems
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $id;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $rootfile;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $label;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $mpso;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $about;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $description;

    public function __construct($inId = null, $inRootfile = null, $inLabel = null, $inMpso = null, $inAbout = null, $inDesc = null)
    {
        if (isset($inId)) {
            $this->id = $inId;
        }

        if (isset($inRootfile)) {
            $this->rootfile = $inRootfile;
        }

        if (isset($inLabel)) {
            $this->label = $inLabel;
        }

        if (isset($inMpso)) {
            $this->mpso = $inMpso;
        }

        if (isset($inAbout)) {
            $this->about = $inAbout;
        }

        if (isset($inDesc)) {
            $this->description = $inDesc;
        }
    }

}