<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 5/11/17
 * Time: 1:50 PM
 */

namespace DAPClientBundle\ElasticDocs;


use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Collection\Collection;
use ONGR\ElasticsearchBundle\Annotation as ES;
/**
 * @ES\Document(type="daprecord")
 */
class DAPRecord
{

    /**
     * @var string
     *
     * @ES\Id()
     */
    public $dapid;


    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $isBasedOn;


    /**
     * @var DAPCreator
     *
     * @ES\Embedded(class="DAPClientBundle:DAPCreator")
     *
     */
    public $creator;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $name;

    /**
     * @var DAPAlternateName
     *
     * @ES\Embedded(class="DAPClientBundle:DAPAlternateName")
     */
    public $alternateName;

    /**
     * @var string
     *
     * @ES\Property(type="integer")
     */
    public $dateCreated;

    /**
     * @var DAPDatePublished
     *
     * @ES\Embedded(class="DAPClientBundle:DAPDatePublished")
     */
    public $datePublished;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $publisher;

    /**
     * @var DAPLocation
     *
     * @ES\Embedded(class="DAPClientBundle:DAPLocation")
     *
     */
    public $locationCreated;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $extent;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $size;

    /**
     * @var string
     *
     * @ES\Embedded(class="DAPClientBundle:DAPDescription", multiple=true)
     */
    public $description; //WE WOULD PREFER THIS TO JUST BE AN ARRAY/COLLECTION OF STRINGS, BUT... APPARENTLY THIS REQUIRES AN OBJECT/CLASS

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $disambiguatingDescription;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $genre;

    /**
     * @var DAPGenre
     *
     * @ES\Embedded(class="DAPClientBundle:DAPGenre", multiple=true)
     *
     */
    public $folgerGenre;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $folgerCallNumber;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $format;

    /**
     * @var DAPAgent
     *
     * @ES\Embedded(class="DAPClientBundle:DAPAgent", multiple=true)
     */
    public $agent;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $inLanguage;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $folgerProvenance;

    /**
     * @var DAPAbout
     *
     * @ES\Embedded(class="DAPClientBundle:DAPAbout", multiple=true)
     */
    public $about;

    /**
     * @var DAPRelatedItems
     *
     * @ES\Embedded(class="DAPClientBundle:DAPRelatedItems", multiple=true)
     */
    public $folgerRelatedItems;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $searchText;


    public function __construct()
    {
        $this->description = new Collection();
        $this->folgerGenre = new Collection();
        $this->agent = new Collection();
        $this->about = new Collection();
        $this->folgerRelatedItems = new Collection();
    }

    public function setMy($setMe, $withVal)
    {
        if (!isset($withVal) || is_null($withVal)) {
            return false;
        }

        $this->$setMe = $withVal;

        return true;
    }

    public function fill($invar)
    {

        if (!isset($invar) || is_null($invar)) {
            return false;
        }
        try {
            //postgres info
            $this->setMy('dapid', $invar->dapID);

            //reset to deal with just metadata
            $invar = (object)$invar->metadata;

            if (isset($invar->name)) {
                $this->setMy('name', $invar->name);
            }

            if (isset($invar->alternateName)) {
                $invar->alternateName = (object)$invar->alternateName;
                $this->setMy('alternateName', new DAPAlternateName($invar->alternateName->uri, $invar->alternateName->description));
            }

            if (isset($invar->createdDate)) {
                if (!is_integer($invar->createdDate)) {
                    $invar->createdDate = (int) $invar->createdDate->format('Y');
                }
                $this->setMy('dateCreated', $invar->createdDate);
            }

            if (isset($invar->datePublished)) {
                $invar->datePublished = (object)$invar->datePublished;
                $tStart = isset($invar->datePublished->startDate) ? $invar->datePublished->startDate : null;
                $tEnd = isset($invar->datePublished->endDate) ? $invar->datePublished->endDate : null;
                $this->setMy('datePublished', new DAPDatePublished($tStart, $tEnd));
            }

            if (isset($invar->publisher)) {
                $this->setMy('publisher', $invar->publisher);
            }

            if (isset($invar->locationCreated)) {
                $invar->locationCreated = (object)$invar->locationCreated;
                $ttype = isset($invar->locationCreated->type) ? $invar->locationCreated->type : null;
                $taddressLocality = isset($invar->locationCreated->addressLocality) ? $invar->locationCreated->addressLocality : null;
                $taddressCountry = isset($invar->locationCreated->addressCountry) ? $invar->locationCreated->addressCountry : null;
                $this->setMy('locationCreated', new DAPLocation($ttype, $taddressLocality, $taddressCountry));
            }

            if (isset($invar->extent)) {
                $this->setMy('extent', $invar->extent);
            }

            if (isset($invar->size)) {
                $this->setMy('size', $invar->size);
            }


            //description
            if (isset($invar->description)) {
                $tdesc = $invar->description;
                if (isset($tdesc)) {
                    $myDesc = array();
                    if (gettype($tdesc) == 'string') {
                        //turn single item into array
                        array_push($myDesc, new DAPDescription($tdesc));
                    } else if (gettype($tdesc) == 'array') {
                        //build array of DAPDescriptions
                        for ($i = 0; $i < count($tdesc); $i++) {
                            array_push($myDesc, new DAPDescription($tdesc[$i]));
                        }
                    } else {
                        // not array, not string, not workable
                    }
                    if (count($myDesc) > 0) { //if we've added some description(s)
                        $this->setMy('description', new Collection($myDesc));
                    }
                }
            }

            if (isset($invar->disambiguatingDescription)) {
                $this->setMy('disambiguatingDescription', $invar->disambiguatingDescription);
            }

            if (isset($invar->genre)) {
                if (gettype($invar->genre) == "string") { //old data uses array, so lets make sure we ignore that
                    $this->setMy('genre', $invar->genre);
                }
            }

            //folger genre
            if (isset($invar->folgerGenre)) {
                $tfolGenre = $invar->folgerGenre;
                if (isset($tfolGenre)) {
                    $myFolgerGenre = array();
                    if (gettype($tfolGenre) == 'array' || gettype($tfolGenre) == 'object') {
                        //build array
                        for ($i = 0; $i < count($tfolGenre); $i++) {
                            array_push($myFolgerGenre, new DAPGenre($tfolGenre->search, $tfolGenre->terms, $tfolGenre->uri));
                        }

                    } else {
                        // not array, not workable
                    }
                    if (count($myFolgerGenre) > 0) { //if we've added some description(s)
                        $this->setMy('folgerGenre', $myFolgerGenre);
                    }
                }
            }
            //end folger genre

            if (isset($invar->folgerCallNumber)) {
                $this->setMy('folgerCallNumber', $invar->folgerCallNumber);
            }

            if (isset($invar->format)) {
                $this->setMy('format', $invar->format);
            }

            if (isset($invar->isBasedOn)) {
                $this->setMy('isBasedOn', $invar->isBasedOn);
            }

            //agent
            if (isset($invar->Agent)) {
                $tAgent = $invar->Agent;
                //die(var_dump($tAgent));
                if (isset($tAgent)) {
                    $myAgent = array();
                    if (gettype($tAgent) == 'array') {
                        //build array
                        for ($i = 0; $i < count($tAgent); $i++) {
                            $tname = isset($tAgent[$i]['name']) ? $tAgent[$i]['name'] : null;
                            $tdescription = isset($tAgent[$i]['description']) ? $tAgent[$i]['description'] : null;
                            $turi = isset($tAgent[$i]['uri']) ? $tAgent[$i]['uri'] : null;
                            $tDAPAgent = new DAPAgent($tname, $tdescription, $turi);
                            array_push($myAgent, $tDAPAgent);
                        }

                    } else {
                        // not array, not workable
                    }
                    if (count($myAgent) > 0) { //if we've added some description(s)
                        $this->setMy('agent', new Collection($myAgent));
                    }
                }
            }
            //end agent

            if (isset($invar->inLanguage)) {
                $this->setMy('inLanguage', $invar->inLanguage);
            }

            if (isset($invar->folgerProvenance)) {
                $this->setMy('folgerProvenance', $invar->folgerProvenance);
            }

            //about
            if (isset($invar->about)) {
                $tAbout = $invar->about;
                $myAbout = array();
                if (gettype($tAbout) == 'array') {
                    //build array
                    for ($i = 0; $i < count($tAbout); $i++) {
                        $turi = isset($tAbout->uri) ? $tAbout->uri : null;
                        $tdescription = isset($tAbout->description) ? $tAbout->description : null;
                        array_push($myAbout, new DAPAbout($turi, $tdescription));
                    }
                } else {
                    // not array, not workable
                }
                if (count($myAbout) > 0) { //if we've added some description(s)
                    $this->setMy('about', new Collection($myAbout));
                }
            }
            //end about

            //related items
            if (isset($invar->folgerRelatedItems)) {
                $tRelated = (object)$invar->folgerRelatedItems;
                $myRelated = array();
                if (gettype($tRelated) == 'array') {
                    //build array
                    for ($i = 0; $i < count($tRelated); $i++) {
                        $tcallhack = '@id'; // b/c calling $thing->@id doesn't work
                        $tid = isset($tRelated->$tcallhack) ? $tRelated->$tcallhack : null;
                        $trootfile = isset($tRelated->rootfile) ? $tRelated->rootfile : null;
                        $tlabel = isset($tRelated->label) ? $tRelated->label : null;
                        $tmpso = isset($tRelated->mpso) ? $tRelated->mpso : null;
                        $tdescription = isset($tRelated->description) ? $tRelated->description : null;
                        array_push($myRelated, new DAPRelatedItems($tid, $trootfile, $tlabel, $tmpso, $tdescription));
                    }
                } else {
                    // not array, not workable
                }
                if (count($myRelated) > 0) { //if we've added some description(s)
                    $this->setMy('folgerRelatedItems', $myRelated);
                }
            }
            //end related items

            if (isset($invar->searchText)) {
                $this->setMy('searchtext', $invar->searchText);
            }

            if (isset($invar->creator)) {
                $tc = new DAPCreator();
                if (isset($invar->creator->givenName)) {
                    $tc->givenName = $invar->creator->givenName;
                }
                if (isset($invar->creator->familyName)) {
                    $tc->givenName = $invar->creator->familyName;
                }
                if (isset($invar->creator->authority)) {
                    $tc->givenName = $invar->creator->authority;
                }
            }

            return isset($this->dapid) ? $this->dapid : -1;
        } catch (\Exception $ex) {
            return -1;
        }
    }
}