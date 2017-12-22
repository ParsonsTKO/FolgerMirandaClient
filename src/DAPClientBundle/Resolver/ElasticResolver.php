<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 6/6/17
 * Time: 3:10 PM
 */

namespace DAPClientBundle\Resolver;

use ONGR\ElasticsearchDSL;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;

/*
 * cannot extend AbstractResolver b/c it is tied to Doctrine
 */
class ElasticResolver
{
    public $em;

    public $repo;           //reference to our elasticsearch index

    public $search;         //the search object

    public $queryHolder;    //we'll be adding all our query details to this object until we get more complicated

    public $results;        //put results here, then return them

    public $documents;

    public $facetsList;     //keep list of aggregations/facets as we add them

    public $facets;

    public $pageSize;

    public $pageIndex;

    public $anythingGoesFlag = false;

    public function init()
    {
        // $this->em = $em;

        $this->repo = $this->em->getRepository('DAPClientBundle:DAPRecord');

        $this->search = $this->repo->createSearch();

        $this->resetQueryHolder();

        $this->results = null;

        $this->documents = null;

        $this->facets = null;

        $this->facetsList = array();

        $this->pageSize = 10;

        $this->pageIndex = 0;
    }

    public function __construct(\ONGR\ElasticsearchBundle\Service\Manager $em)
    {
        $this->em = $em;
    }

    /*
     * This function takes a text string and runs a naive search across all fields. It also returns the results.
     * It should be an example of how to construct searches based on the tools this class offers.
     */
    public function doFullTextSearch($intext)
    {
        if (!isset($intext) || $intext == '') {
            return false;
        }

        $this->clearResults();

        $this->addFullTextSearch($intext);

        $this->doSearch();

        return $this->getDocuments(); //by default, return search results (documents found)

    }

    public function addFullTextSearch($intext)
    {
        //won't try to add null, blank, or asterisk to search
        if (!isset($intext) || $intext == '') {
            return false;
        }
        if ($intext == '*') {
            $this->anythingGoesFlag = true;
            $matchAllQuery = new ElasticsearchDSL\Query\MatchAllQuery();
            $this->queryHolder->add($matchAllQuery);

        } else {
            $textSearch = new MatchQuery("_all", $intext);
            $this->queryHolder->add($textSearch);
            $this->search->addQuery($this->queryHolder);
        }
    }
    public function addFullPhraseSearch($intext)
    {
        //won't try to add null, blank, or asterisk to search
        if (!isset($intext) || $intext == '') {
            return false;
        }
        $this->anythingGoesFlag = true;
        $phraseSearch = new MatchPhraseQuery("_all", $intext);
        $this->queryHolder->add($phraseSearch);
        $this->search->addQuery($this->queryHolder);
    }
    public function addPhraseSearch($field, $intext)
    {
        //won't try to add null, blank, or asterisk to search
        if (!isset($intext) || $intext == '') {
            return false;
        }
        $this->anythingGoesFlag = true;
        $phraseSearch = new MatchPhraseQuery($field, $intext);
        $this->queryHolder->add($phraseSearch);
        $this->search->addQuery($this->queryHolder);
    }

    public function getSearchJSON()
    {
        return json_encode($this->search->toArray(), JSON_PRETTY_PRINT);
    }

    /*
     * Set the current page, but don't act on it
     */
    public function setPage($whichPage)
    {
        $this->pageIndex = $whichPage;

        $this->search->setFrom($this->pageIndex * $this->pageSize);
    }

    /*
     * Find out home many pages there are in the results
     */
    public function getPageCount()
    {
        if (is_null($this->results)) {
            $this->doSearch();
        }
        if (is_null($this->results)) { //search is un-runnable, so no pages of results
            return 0;
        }
        $t = $this->results->getRaw()['hits']['total'];
        return ceil($t / $this->pageSize);
    }

    /*
     * Sets the "page" of the search results by using the pageSize and pageIndex to set the search results
     * offset and act upon it. Returns the search results. (A side effect is refreshing the aggregations info.)
     */
    public function getPage($whichPage)
    {
        $this->setPage($whichPage);

        $this->doSearch();

        return $this->getDocuments();
    }

    /*
     * Just gets the next page.
     */
    public function getNextPage()
    {
        $this->pageIndex = ++$this->pageIndex;

        return $this->getPage($this->pageIndex);
    }

    /*
     * Set how many results should be in a page. If changed in the middle of paging, will cause confusion.
     */
    public function setPageSize($inPageSize)
    {
        if (!$inPageSize || ($inPageSize != (int)$inPageSize)) {
            return false;
        }
        $this->pageSize = $inPageSize;

        $this->search->setSize($this->pageSize);

        return true;
    }

    /*
     * How many pages of results are there? (number of results divided by page size, rounded up to whole number)
     */
    public function getNumberOfPages()
    {
        return ceil(count($this->documents) / $this->pageSize);
    }

    /*
     * Ensure the search is fully assembled, get the results from elastic,
     * then save the results, documents, and aggregations.
     */
    public function doSearch()
    {
        //       try {
        $t = $this->queryHolder->toArray();

        if (!$this->anythingGoesFlag) {
            //check for boolean search
            if (!isset($t['bool'])  || (count($t['bool']) == 0)) {
                //check for match or match_all search
                if (!isset($t['match']) || (!isset($t['match']['_all']) && count($t['match']) == 0)) {
                    //therefore no valid search
                    return false;
                }
            }
        }

        $this->search->addQuery($this->queryHolder);

        //perform find
        $this->results = $this->repo->findDocuments($this->search);


        //take results into array
        $this->documents = array();
        $this->results->rewind();
        while ($this->results->count() > 0) {
            $temp = $this->results->current();
            array_push($this->documents, $temp);
            $this->results->next();
            if (!$this->results->valid()) {
                break;
            }
        }

        //gather aggregation information
        $this->facets = array();
        for ($i = 0; $i < count($this->facetsList); $i++) {
            $resultAggs = $this->results->getAggregation($this->facetsList[$i]);
            $thisFacet = $this->facetsList[$i];
            $thisFacet = str_replace('_', ' ', $thisFacet);
            foreach ($resultAggs as $bucket) { //$aggIter = 0; $aggIter < count($resultAggs['buckets']); $aggIter++) {
                $key = $bucket->getValue('key');
                //for grouped, keyed range aggregations like our byCentury, ONGR loses the ability to retrieve the keys
                if (is_null($key)) {
                    $a = $bucket->getValue('from');
                    $b = $bucket->getValue('to');
                    if (!$a && $b) {
                        $key = 'Until '.$b;
                    } elseif (!$b && $a) {
                        $key = 'From '.$a;
                    } elseif ($a && $b) {
                        $key = $a . '-'. $b;
                    }
                }
                $count = $bucket['doc_count'];
                if (!is_null($key) && $count > 0) {
                    $temp = (object)array('facet' => $this->facetsList[$i], 'key' => $key, 'count' => $count);
                    if (!isset($this->facets[$thisFacet]) || !is_array($this->facets[$thisFacet])) {
                        $this->facets[$thisFacet] = array();
                    }
                    array_push($this->facets[$thisFacet], $temp);
                }
            }
        }

        return true;
        //       } catch (\Exception $ex) {
        //           return false;
        //       }
    }

    /*
     * Apply a filter to any field. We'll use this to apply facets.
     */
    public function addFilter($inField, $inVal)
    {
        if ($inField && $inVal) {
            $filterQuery = new MatchQuery($inField, $inVal);
            $this->queryHolder->add($filterQuery, BoolQuery::MUST);
            return true;
        } else {
            return false;
        }
    }

    /*
     * Add an aggregation, which gives us faceting information back after a search.
     */
    public function addAggregation($label, $inField)
    {
        if (!($label && $inField)) {
            return false;
        }
        $termAggregation = new ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation($label, $inField);
        $this->search->addAggregation($termAggregation);
        array_push($this->facetsList, $label);
        return true;
    }

    /*
     * Handle Range Aggregations
     * the ranges array should be associative with 'from', 'to', and 'key'
     */
    public function addRangeAggregation($name = null, $field = null, $ranges = null)
    {
        if (is_null($name) || is_null($field) || is_null($ranges) || gettype($ranges) != 'array' || count($ranges) < 1) {
            return false;
        }
        $rangeAggregation = new ElasticsearchDSL\Aggregation\Bucketing\RangeAggregation($name, $field, $ranges, true);
        $this->search->addAggregation($rangeAggregation);
        array_push($this->facetsList, $name);
        return true;
    }

    /*
     * Add default aggregations (facets) that
     */
    public function addDefaultAggregations()
    {
        //By Century
        $this->addCenturyAggregation();
        //By Media Type (aka format)
        $this->addAggregation("media_format", "format");
        //By genre
        $this->addAggregation("folger_genres", "folger_genre.terms");
    }

    /*
     * Aggregation - Date By Century
     */
    public function addCenturyAggregation()
    {
        $rangeAggRanges = array();
        array_push($rangeAggRanges, array('key' => '<1600', 'to' => '1600'));
        array_push($rangeAggRanges, array('key' => '1600-1700', 'from' => 1600, 'to' => 1700));
        array_push($rangeAggRanges, array('key' => '1700-1800', 'from' => 1700, 'to' => 1800));
        array_push($rangeAggRanges, array('key' => '1800-1900', 'from' => 1800, 'to' => 1900));
        array_push($rangeAggRanges, array('key' => '1900-2000', 'from' => 1900, 'to' => 2000));
        array_push($rangeAggRanges, array('key' => '>2000', 'from' => 2000));
        $this->addRangeAggregation("era", 'date_created', $rangeAggRanges);
    }


    /*
     * Add a search specific to the location of the item
     */
    public function addLocationSearch($address = null, $locality = null)
    {
        if (is_null($address) && is_null($locality)) {
            return false;
        }

        if ($address) {
            $this->addFilter('location_created.address', $address);
        }
        if ($locality) {
            $this->addFilter('location_created.address_locality', $locality);
        }
    }

    /*
     * This gets the documents found as part of the search.
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /*
     * This gets the full set of search information returned.
     */
    public function getResults()
    {
        return $this->results;
    }

    /*
     * Process results into tidy array as expected by DAPClient code
     * This makes some decisions about which fields are valuable data.
     */
    public function getSearchResults()
    {
        $out = array();

        $this->results->rewind();
        while ($this->results->count() > 0) {
            $ot = new \stdClass();
            $t= $this->results->current();
            if (!$t || is_null($t)) {
                return $out;
            }
            $ot->dapID = $t->dapid;
            $ot->name = $t->name;
            $ot->creator = $t->creator;

            if ($t->dateCreated) {
                $ot->dateCreated = $t->dateCreated;
            } elseif ($t->datePublished) {
                $temp = '';
                if ($t->datePublished->startDate) {
                    $temp .= $t->datePublished->startDate;
                    if ($t->datePublished->endDate) {
                        $temp .= ' - ' . $t->datePublished->endDate;
                    }
                } elseif ($t->datePublished->endDate) {
                    $temp .= $t->datePublished->endDate;
                }
                if ($temp != '') {
                    $ot->dateCreated = $temp;
                }
            }

            if ($t->locationCreated) {
                $ot->locationCreated = (object)array('address' => $t->locationCreated->address, 'addressLocality' => $t->locationCreated->addressLocality);
            }

            if ($t->format) {
                $ot->format = $t->format;
            }

            if ($t->folgerRelatedItems) {
                //should be getting this from the parameters.yml file eventually
                $images_endpoint = 'http://dapdev.dev/';
                $images_path = 'var/folger/storage/images/';
                //end should be getting this from the parameters.yml file eventually
                $img = $t->folgerRelatedItems->current();
                if ($img && isset($img->rootfile)) {
                    $img = $img->rootfile;
                    $img = $images_endpoint . $images_path . $img . '/' . $img . '_thumb.jpg';
                    if (stripos(get_headers($img)[0], "200 OK")) {
                        $ot->thumbnail = $img;
                    }
                }
            }

            if ($t->folgerCallNumber) {
                $ot->folgerCallNumber = $t->folgerCallNumber;
            }

            array_push($out, $ot);

            $this->results->next();
            if (!$this->results->valid()) {
                break;
            }
        }

        return $out;
    }

    /*
     * This returns facet information. Each facet has a name which should be used to indicate its field,
     * a key that gives the value in that field, and a count of matching items.
     */
    public function getFacets()
    {
        return $this->facets;
    }

    public function clearResults()
    {
        $this->results = null;
    }
    public function resetQueryHolder()
    {
        $this->queryHolder = new ElasticsearchDSL\Query\Compound\BoolQuery();
    }

    /*
     * Add a range filter to an arbitrary field.
     */
    public function addRangeFilter($inField, $inMin = null, $inMax = null, $isTopLevel = true)
    {
        if(is_null($inField) && (is_null($inMin) && is_null($inMax)) )
        {
            return false;
        }
        $range = array();
        if(!is_null($inMin))
        {
            $range['from'] = $inMin;
        }
        if(!is_null($inMax))
        {
            $range['to'] = $inMax;
        }
        $rangeQuery = new ElasticsearchDSL\Query\TermLevel\RangeQuery($inField, $range);

        if($isTopLevel) {
            echo "asd";
            $this->search->addQuery($rangeQuery);
        }
        else
        {
            $this->queryHolder->add($rangeQuery);
        }
        return true;
    }
    //convenience functions for specific search UI items
    /*
     * Takes in the text from the search UI and makes it a full text search of Elastic
     */
    public function addSearchText($intext)
    {
        //this might be more complicated later, but for now, just do the naive thing
        $this->addFullTextSearch($intext);
    }
    /*
     * Takes stop/start dates and filters results based on that
     */
    public function addCreatedIn($inFrom = null, $inUntil = null)
    {
        if(is_null($inFrom) && is_null($inUntil))
        {
            return false;
        }

        /*
         * Fields to consider:
         *  - date_published.start_date
         *  - date_published.end_date
         *  - date_created
         *
         */

        $bool = new BoolQuery();

        $range = array();

        if(!is_null($inFrom))
        {
            $range['from'] = $inFrom;
        }
        if(!is_null($inUntil))
        {
            $range['to'] = $inUntil;
        }

        $dpQ = new ElasticsearchDSL\Query\TermLevel\RangeQuery("date_published.start_date", $range);
        $dc = new ElasticsearchDSL\Query\TermLevel\RangeQuery("date_created", $range);

        $bool->add($dpQ, BoolQuery::SHOULD);
        $bool->add($dc, BoolQuery::SHOULD);

        $this->search->addQuery($bool);

        return true;
    }
    public function addLanguageFilter($inLang = null)
    {
        return $this->addFilter('in_language', $inLang);
    }
    public function addFormatFilter($inFormat)
    {
        return $this->addFilter('format', $inFormat);
    }
    public function addGenreFilter($inGenre)
    {
        return $this->addFilter('genre', $inGenre);
    }
    //end convenience functions for specific search UI items

    //from AbstractResolver Class
    protected function createNotFoundException($message = 'Entity not found')
    {
        return new \Exception($message, 404);
    }

    protected function createInvalidParamsException($message = 'Invalid params')
    {
        return new \Exception($message, 400);
    }

    protected function createAccessDeniedException($message = 'No access to this action')
    {
        return new \Exception($message, 403);
    }
}