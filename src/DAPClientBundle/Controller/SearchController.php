<?php
/**
 * File containing the SearchController class.
 *
 * (c) http://parsonstko.com/
 * (c) Developers jdiaz, johnc, natep
 */

namespace DAPClientBundle\Controller;

use Assetic\Exception\Exception;
use function GuzzleHttp\default_ca_bundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;


use DAPClientBundle\ElasticDocs\DAPRecord;

class SearchController extends Controller
{
    /**
     * Redirect result page.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resultAction(Request $request)
    {
        try {
            /*
             * This is where I can temporarily hook in to preempt the graphQL endpoint.
             * Look to the $result data object for what I'm trying to replicate
             */

            $response = new Response();

            //get user inputs that we care about
            $userSearch = array();

            $userSearch['searchTerm'] = $request->query->get('searchterm');
            $userSearch['searchPhrase'] = $request->query->get('searchphrase');
            $userSearch['phraseField'] = $request->query->get('phrasefield');
            $userSearch['phraseFieldSearch'] = $request->query->get('phrasefieldsearch');
            $userSearch['filter'] = is_null($request->query->get('filter')) ? [] : explode("||", $request->query->get('filter'));
            $userSearch['filterValue'] = is_null($request->query->get('filtervalue')) ? [] : explode("||", $request->query->get('filtervalue'));
            $userSearch['rangeField'] = is_null($request->query->get('rangefield')) ? [] : explode("||", $request->query->get('rangefield'));
            $userSearch['rangeMin'] = is_null($request->query->get('rangemin')) ? [] : explode("||", $request->query->get('rangemin'));
            $userSearch['rangeMax'] = is_null($request->query->get('rangemax')) ? [] : explode("||", $request->query->get('rangemax'));
            $userSearch['rangeDemote'] = is_null($request->query->get('rangedemote')) ? [] : explode("||", $request->query->get('rangedemote')); //set to 1 to move the range filter into the collected query parts
            $userSearch['facetName'] = $request->query->get('facetname');
            $userSearch['facetField'] = $request->query->get('facetfield');
            $userSearch['pageNumber'] = $request->query->get('pagenumber');
            $userSearch['pageSize'] = $request->query->get('pagesize');

            $userSearch['createdFrom'] = $request->query->get('createdfrom');
            $userSearch['createdUntil'] = $request->query->get('createduntil');
            $userSearch['createdDateSearch'] = $request->query->get('createddatesearch');
            $userSearch['creatorSearch'] = $request->query->get('creatorsearch');

            $userSearch['timeperiod'] = $request->query->get('timeperiod');

            $userSearch['languagefilter'] = $request->query->get('languagefilter');


            $userSearch['refine'] = $request->query->get('refine');
            /*
            if ($userSearch['refine']) {
                $userSearch['pageNumber'] = 0;
            }
            */
            $userSearch['refineto'] = $request->query->get('refineto');

            $userSearch['format'] = ($request->query->get('format'));
            $userSearch['genre'] = ($request->query->get('genre'));

            $userSearch['address'] = $request->query->get('address');
            $userSearch['locality'] = $request->query->get('locality');

            $userSearch['callNumber'] = $request->query->get('callnumber');
            //end get user inputs that we care about

            //if certain of these are blank, try to load from search cookie. otherwise save it to cookie
            if (1==0) {
                $anysearch = $this->anythingatall($userSearch);
                if (!$anysearch) {
                    $userSearch = (array)json_decode($request->cookies->get('userSearch'));
                    if (is_null($userSearch)) { //go back w./ error message
                        return $this->redirectToRoute("dap_client_homepage", array('msg' => 1));
                    }
                } else {
                    $response->headers->setCookie(new Cookie('userSearch', \GuzzleHttp\json_encode($userSearch)));
                }
            }


            //wrangle facets - quick & dirty edition
            if ($userSearch['refine'] && $userSearch['refineto']) {
                switch (strtolower($userSearch['refine'])) {
                    //Turning the era into its ranges
                    case 'era':
                        switch (strtolower($userSearch['refineto'])) {
                            case 'until 1600':
                                array_push($userSearch['rangeField'], 'date_created');
                                array_push($userSearch['rangeMin'], null);
                                array_push($userSearch['rangeMax'], 1600);
                                break;
                            case '1600-1700':
                                array_push($userSearch['rangeField'], 'date_created');
                                array_push($userSearch['rangeMin'], 1600);
                                array_push($userSearch['rangeMax'], 1700);
                                break;
                            case '1700-1800':
                                array_push($userSearch['rangeField'], 'date_created');
                                array_push($userSearch['rangeMin'], 1700);
                                array_push($userSearch['rangeMax'], 1800);
                                break;
                            case '1800-1900':
                                array_push($userSearch['rangeField'], 'date_created');
                                array_push($userSearch['rangeMin'], 1800);
                                array_push($userSearch['rangeMax'], 1900);
                                break;
                            case '1900-2000':
                                array_push($userSearch['rangeField'], 'date_created');
                                array_push($userSearch['rangeMin'], 1900);
                                array_push($userSearch['rangeMax'], 2000);
                                break;
                            case 'from 2000':
                                array_push($userSearch['rangeField'], 'date_created');
                                array_push($userSearch['rangeMin'], 2000);
                                array_push($userSearch['rangeMax'], null);
                                break;
                        }
                        break;
                    //ultimately, this will check against a curated list, but for now, we'll take anything
                    case 'media_types':
                    case 'media_format':
                        array_push($userSearch['filter'], 'format');
                        array_push($userSearch['filterValue'], $userSearch['refineto']);
                        break;
                    case 'folger_genres':
                        array_push($userSearch['filter'], 'folger_genre.terms');
                        array_push($userSearch['filterValue'], $userSearch['refineto']);
                        break;
                }

            }
            //end quick and dirty wrangle facets
            //END GET USER INPUTS

            //DISPLAY USER INPUT FEEDBACK
            $outvar = '<div><p>Play with search features by using querystring variables matching the names below. 
            Any values will be displayed.</p>';

            $outvar .= '<ul>';

            $ttttt = str_replace('type="hidden"', 'type="text"', $this->userSearchToString($userSearch, 'hiddeninput'));
            $ttttt = preg_replace('/(<input type="text" name=")([^"]*)"/', '<br>$2: $1$2"', $ttttt);
            $outvar .= $ttttt;

            $outvar .= '</ul></div>';
            //END DISPLAY USER INPUT FEEDBACK

            //GET ELASTICSEARCH RESOLVER OBJECT TO MAKE QUERIES
            $elastic = $this->get('dap.resolver.elastic');

            if ($userSearch['searchTerm']) {
                $elastic->addFullTextSearch($userSearch['searchTerm']);
            }
            if ($userSearch['searchPhrase']) {
                $elastic->addFullPhraseSearch($userSearch['searchPhrase']);
            }
            if ($userSearch['phraseFieldSearch'] && $userSearch['phraseField']) {
                $elastic->addPhraseSearch($userSearch['phraseField'], $userSearch['phraseFieldSearch']);
            }

            //Turn the Time Periods drop down input into a filter for us
            if ($userSearch['timeperiod']) {
                switch ($userSearch['timeperiod']) {
                    case 1:
                        $elastic->addCreatedIn(null, 1600);
                        break;
                    case 2:
                        $elastic->addCreatedIn(1600, 1700);
                        break;
                    case 3:
                        $elastic->addCreatedIn(1700, 1800);
                        break;
                    case 4:
                        $elastic->addCreatedIn(1800, 1900);
                        break;
                    case 5:
                        $elastic->addCreatedIn(1900, 2000);
                        break;
                    case 6:
                        $elastic->addCreatedIn(2000, null);
                        break;
                    default:
                       //if we get an unexpected result, don't just ignore it, but also remove it from the userSearch
                        unset($userSearch['timeperiod']);
                }
            }
            if ($userSearch['languagefilter']) {
                $elastic->addFilter('in_language', $userSearch['languagefilter']);
            }

            if (count($userSearch['filter']) > 0 &&
                count($userSearch['filterValue']) > 0 &&
                count($userSearch['filter']) == count($userSearch['filterValue'])) {
                for ($i = 0; $i < count($userSearch['filter']); $i++) {
                    $elastic->addFilter($userSearch['filter'][$i], $userSearch['filterValue'][$i]);
                }
            }
            //we have kept these in matched arrays. If they don't match, we can't use them
            if (count($userSearch['rangeField']) > 0 &&
                count($userSearch['rangeMin']) > 0 &&
                count($userSearch['rangeMax']) > 0 &&
                count($userSearch['rangeField']) == count($userSearch['rangeMin']) &&
                count($userSearch['rangeField']) == count($userSearch['rangeMax'])) {
                for ($i = 0; $i < count($userSearch['rangeField']); $i++) {
                    if ($userSearch['rangeField'][$i] && ($userSearch['rangeMin'][$i] || $userSearch['rangeMax'][$i])) {
                        //add each matching set ( a field, and one ore more of min and max ) to our filters
                        $elastic->addRangeFilter($userSearch['rangeField'][$i], $userSearch['rangeMin'][$i], $userSearch['rangeMax'][$i], ($userSearch['rangeDemote'] == 1));
                    }
                }
            } else {
                //if we can't use them, clear them
                unset($userSearch['rangeField']);
                unset($userSearch['rangeMin']);
                unset($userSearch['rangeMax']);
                unset($userSearch['rangeDemote']);
            }

            if ($userSearch['createdFrom'] || $userSearch['createdUntil']) {
                $elastic->addCreatedIn($userSearch['createdFrom'], $userSearch['createdUntil']);
            }

            if ($userSearch['createdDateSearch']) {
                //we are falling back to just searching for that text if we don't have a numeric year for the filter
                if (is_numeric($userSearch['createdDateSearch'])) {
                    $elastic->addFilter('date_created', $userSearch['createdDateSearch']);
                } else {
                    $elastic->addSearchText($userSearch['createdDateSearch']);
                }
            }

            if ($userSearch['format']) {
                $elastic->addFilter('format', strtolower($userSearch['format']));
            }

            if ($userSearch['genre']) {
                $elastic->addFilter('folger_genre.terms', $userSearch['genre']);
            }

            if ($userSearch['address'] || $userSearch['locality']) {
                $elastic->addLocationSearch($userSearch['address'], $userSearch['locality']);
            }

            if ($userSearch['callNumber']) {
                $elastic->addFilter('folger_call_number', $userSearch['callNumber']);
            }

            //currently handling creators for the case of having given and family names,
            // and using each separately for search, despite passing them together
            if ($userSearch['creatorSearch']) {
                $creatorSearchArr = explode('||', $userSearch['creatorSearch']);
                if (isset($creatorSearchArr[0])) { //given name
                    $elastic->addFilter( 'creator.given_name', $creatorSearchArr[0]);
                }
                if (isset($creatorSearchArr[1])) {
                    $elastic->addFilter( 'creator.family_name', $creatorSearchArr[1]);
                }

            }

            //aggregations/facets - this lets us add a facet/aggregation by passing it with the search
            //it will probably be deprecated eventually but is an easy way to try out a facet
            if ($userSearch['facetName'] && $userSearch['facetField']) {
                $elastic->addAggregation($userSearch['facetName'], $userSearch['facetField']);
            }


            //collect the default facets (aggregations) we care about
            $elastic->addDefaultAggregations();

            //page sizing
            //we allow the query from the user to tell us what page we're on and how many items per page
            //in production, we may want to disallow the changing of pagesize
            //the pageNumber is important, as it lets us do paging for our site visitors
            if ($userSearch['pageSize'] || $userSearch['pageNumber']) {
                if ($userSearch['pageSize']) {
                        $elastic->setPageSize((int)$userSearch['pageSize']);
                }
                if ($userSearch['pageNumber']) {
                    $elastic->setPage((int)$userSearch['pageNumber']);
                }
            }


            if ($elastic->doSearch()) { //if search worked

                //Show actual query for debugging
                $a = $elastic->getSearchJSON();
                $outvar .= "<h2>Search Query</h2> <pre>$a</pre>";
                //End show actual query for debugging
                //output facets for debug
                if (count($elastic->facets) > 0) {
                    $outvar .= "<h2>Facets</h2><ul>";
                    foreach ($elastic->facets as $k => $v) {
                        $outvar .= "<li><strong>$k</strong>";
                        for ($j = 0; $j < count($v); $j++) {
                            $outvar .= "<ul>";
                            $outvar .= "<li>facet: " . $v[$j]->facet . "</li>";
                            $outvar .= "<li>key: " . $v[$j]->key . "</li>";
                            $outvar .= "<li>count: " . $v[$j]->count . "<hr></li>";
                            $outvar .= "</ul>";
                        }
                        $outvar .= "</li>";
                    }
                    $outvar .= "</ul>";
                }
                //end output facets for debug


                $outvar .= "<h2>Search Results</h2><pre>" . $this->debugOut($elastic->getDocuments()) . "</pre>";

                $outvar .= "<h2>Raw Results</h2><pre>" . $this->debugOut($elastic->results) . "</pre>";


                //set up result object
                $result = (object)array();
                $result->data = (object)array();
                //push search results into expected places
                $result->data->records = $elastic->getSearchResults();

            } else { //if search command failed
                $outvar .= "<h1>Unable to perform search</h1>";
                $result = (object)array();
                $result->data = (object)array();
                $result->data->records = [];
                return $this->redirectToRoute("dap_client_homepage", array('msg' => 1)); //go back w./ error message

            }


            $outvar .= "<h2>Data Structure</h2>";
            $outvar .= $this->debugOut($result);
            $outvar .= $this->debugOut($result->data->records);
            $outvar .= "<hr><pre>".$elastic->getSearchJSON()."</pre>";

            $querystringback = '?'. $this->userSearchToString($userSearch, 'querystring');
            $hiddeninputback = $this->userSearchToString($userSearch, 'hiddeninput');

            $templateRendered = $this->renderView(
                'DAPClientBundle:Search:results.html.twig',
                array(
                    'result' => $result->data,
                    'facets' => $elastic->facets,
                    'debuginfo' => $outvar,
                    'currentsearch' =>  $querystringback,
                    'currentsearchform' =>  $hiddeninputback,
                    'usersearch' => $userSearch,
                    'searchterm' => $userSearch['searchTerm'],
                    'totalpages' => $elastic->getPageCount(),
                    'currentpage' => ($elastic->pageIndex+1),
                    'formatfilter' => $userSearch['format'],
                    'timeperiodfilter' => $userSearch['timeperiod']
                )
            );
            $response->setContent($templateRendered);
            return $response;
        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }

    /**
     * Redirect result page.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function alternativeResultAction(Request $request)
    {
        try {
            $searchService = $this->get('dap_client.service.search');
            $viewSettings = $searchService->searchSettings['views']['result'];

            $result = $searchService->getContent('record', $viewSettings);

            return $this->render(
                'DAPClientBundle:Search:alternative_results.html.twig',
                array (
                    'result' => $result->data,
                 )
            );

        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }

    /**
     * Redirect detail page.
     *
     * @param $type
     * @param $name
     * @param $dapID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction($type, $name, $dapID)
    {
        try {
            $searchService = $this->get('dap_client.service.search');
            $viewSettings = $searchService->searchSettings['views']['detail'];

            if (!$searchService->validateUUID($dapID)) {
                throw $this->createNotFoundException('Invalid ID.');
            }

            if (array_key_exists('GET_option_value', $viewSettings['record'])) {
                $viewSettings['record']['GET_option_value'] =
                    str_replace('dapIDValue', $dapID, $viewSettings['record']['GET_option_value']);
            }

            $contentResult = $searchService->getContent('record', $viewSettings);
            $record = reset($contentResult->data->records);

            if (empty($record)) {
                throw new \UnexpectedValueException("Record not found or empty");
            }

            //preprocess related images
            $relatedItemsInfo = array();

            if (isset($record->file_location)) {
                //this record has a first-class binary file attached to it


                $t = (object) array();

                //figure out file info
                $pathinfo = pathinfo($record->file_location);
                $t->type = $record->format;
                $t->url = $searchService->searchSettings['views']['detail']['binary_endpoint']. $record->file_location;
                $t->show = $record->name;
                $t->title = $record->name;
                if (isset($record->MPSO)) {
                    $t->order = $record->MPSO;
                }
                $t->filetype = $pathinfo['extension'];
                if (isset($record->size)) {
                    $t->filesize = $record->size;
                }
                if (isset($t->url)) {
                    $t->download = $t->url;
                }
                array_push($relatedItemsInfo, $t);
            }
            if (isset($record->folgerRelatedItems)) {
                foreach ($record->folgerRelatedItems as $k => $v) {
                    $t = (object) array();
                    if ($v->folgerObjectType == 'partofcollection') {
                        //skip this, we'll process separately for now
                    } else {
                        //switch( strtolower($v->folgerRemoteIdentification->folgerRemoteSystemID) ) {
                        switch (strtolower($v->folgerObjectType)) {
                            case 'luna':
                            case 'image': //This will need to change when we have other image sources
                                //this is inefficient n^2 approach
                                $foundImage = false;
                                if (isset($record->images) && !is_null($record->images)) {
                                    foreach ($record->images as $ik => $iv) {
                                        if ($v->folgerRemoteIdentification->folgerRemoteUniqueID == $iv->rootfile) {
                                            if (isset($v->folgerObjectType)) {
                                                $t->type = $v->folgerObjectType;
                                            } else {
                                                $t->type = 'image';
                                            }
                                            $t->url = $viewSettings['images_endpoint'] . $iv->size4jpgURL;
                                            if ($v->description && $v->description != '') {
                                                $t->show = $v->description;
                                            } elseif (isset($v->label)) {
                                                $t->show = $v->label;
                                            }

                                            $t->title = $iv->pageNumber;
                                            if (isset($v->label)) {
                                                $t->title = $v->label;
                                            }
                                            if (isset($v->mpso)) {
                                                $t->order = $v->mpso;
                                            }
                                            $t->root = $v->folgerRemoteIdentification->folgerRemoteUniqueID;

                                            $tempImageInfo = $this->getImageInfo($t->url);

                                            $t->filetype = $tempImageInfo['type'];
                                            $t->filesize = $tempImageInfo['width'] . 'x' . $tempImageInfo['height'] . ' - ' . $tempImageInfo['size'];
                                            $t->download = '/download/image/' . $t->root . '/' . explode('/', $iv->size4jpgURL)[5];

                                            $foundImage = true;
                                        }
                                    }
                                }
                                if (!$foundImage) {
                                    //the image has not been imported, there is no match.
                                    //do a placeholder
                                    $t->type = 'missing_image';
                                    $t->url = null;
                                    $t->show = $v->description;
                                    $t->title = $v->label;
                                    $t->order = $v->mpso;
                                    $t->filetype = '';
                                    $t->filesize = '';
                                }
                                break;
                            case 'oembed':
                                $t->type = 'oembed';
                                if (isset($v->folgerRemoteIdentification->folgerRemoteUniqueID)) {
                                    $t->url = $v->folgerRemoteIdentification->folgerRemoteUniqueID;
                                }
                                if (isset($v->description)) {
                                    $t->show = $v->description;
                                }
                                if (isset($v->title)) {
                                    $t->title = $v->label;
                                }
                                if (isset($v->mpso)) {
                                    $t->order = $v->mpso;
                                }
                                $t->filetype = '';
                                $t->filesize = '';
                                if (isset($t->url)) {
                                    $t->download = $t->url;
                                }
                                break;
                            default:
                                $t->type = $v->folgerObjectType;
                                $t->url = $v->folgerRemoteIdentification->folgerRemoteUniqueID;
                                $t->show = $v->description;
                                $t->title = $v->label;
                                $t->order = $v->mpso;
                                $t->filetype = '';
                                $t->filesize = '';
                                if (isset($t->url)) {
                                    $t->download = $t->url;
                                }
                                break;
                        }
                        if (isset($t->type)) { //make sure we don't push empty items
                            array_push($relatedItemsInfo, $t);
                        }
                    }

                }
                //if we just wanted to pass through the data, but we need to process image info
                //$relatedItemsInfo = $record->folgerRelatedItems;
            }
            $relatedItemsList = \GuzzleHttp\json_encode($relatedItemsInfo);

            $collectionThing = array();
            if ($record->internalRelations) {
                foreach ($record->internalRelations as $k => $v) {
                    array_push($collectionThing, $v);
                }
            }
            $collectionList = \GuzzleHttp\json_encode($collectionThing);

            //prepare open:graph metadata

            $ogMeta = array();
            $ogMeta['og:title'] = $record->name;
            //$ogMeta['og:type']
            $ogMeta['og:url'] = $viewSettings['public_url'].urlencode($type).'/'.urlencode($name).'/'.$dapID;
            //take first related image for og:image field
            //will replace this with reference to thumbnail of this image
            if (count($relatedItemsInfo)>0) {
                foreach ($relatedItemsInfo as $k => $v) {
                    if ($v->type !== 'image') {
                        continue;
                    } else {
                        $ogMeta['og:image'] = $v->url;
                        break;
                    }
                }
            }

            //set human-readable language
            $record->languageDisplay = $this->cheapiso639($record->inLanguage);

            return $this->render(
                'DAPClientBundle:Search:detail.html.twig',
                array(
                    'viewSettings' => $viewSettings,
                    'record' => $record,
                    'relatedItemsList' => $relatedItemsList,
                    'collectionList' => $collectionList,
                    'detailMeta' => $ogMeta
                )
            );
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }

    /*
     * John C convenience method
     */
    public function debugOut($invar)
    {
        if (1==0) { //ensure this always evaluates to false when in production
            ob_start();
            var_dump($invar);
            $tt = ob_get_clean();
            return $tt;
        } else {
            return '';
        }
    }
    /*
     * John C convenience method
     */
    public function userSearchToString($invar, $whichString)
    {
        $outstring = '';
        $isquerystring = false;
        switch ($whichString) {
            case 'querystring':
                $basestring = 'XXXYYYXXX=XXXZZZXXX';
                $isquerystring = true;
                break;
            case 'hiddenform':
            case 'hiddeninput':
                $basestring = '<input type="hidden" name="XXXYYYXXX" value="XXXZZZXXX">';
                break;
            default:
                die($whichString);
        }
        foreach ($invar as $k => $v) {
            if ($k != '' && $v != '' && !is_null($k) && !is_null($v)) {
                if ($k == 'pageNumber' || $k == 'pageSize') {
                    continue;
                }
                if (is_array($v)) {
                    if (count($v) == 0) {
                        continue;
                    }
                    $checkarrforemptyornull = array_filter($v, function ($temp) {
                        if (is_null($temp) || $temp == '') {
                            return false;
                        }
                    });
                    if (count($checkarrforemptyornull) == 0) {
                        continue;
                    }
                }
                if ($isquerystring && $outstring != '') {
                    $outstring .= '&';
                }
                //catch special cases
                if (($k == 'searchTerm' && $k == 'createdFrom' || $k == 'createdUntil') && !$isquerystring) {
                    //do nothing for searchTerm in hidden form
                } elseif (is_array($v)) {
                    $outstring .= str_replace('XXXYYYXXX', strtolower($k), $basestring); //set key
                    $outstring = str_replace('XXXZZZXXX', urlencode(implode('||', $v)), $outstring); //set value
                } else {
                    //standard case
                    $outstring .= str_replace('XXXYYYXXX', strtolower($k), $basestring); //set key
                    $outstring = str_replace('XXXZZZXXX', urlencode($v), $outstring); //set value
                }
            }
        }
        return $outstring;
    }

    /*
     * John C convenience function - is there anything in search object?
     */
    public function anythingatall($invar)
    {
        if (!is_array($invar)) {
            return false;
        }
        foreach ($invar as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vk => $vv) {
                    if ($vv) {
                        return true;
                    }
                }
            } elseif ($v) {
                return true;
            }
        }
        return false;
    }

    /*
     * Convenience function borrowed from twig extension
     * get info about image file
     *
     */
    public function getImageInfo($path)
    {
        try {
            $types = array(
                1 => 'GIF',
                2 => 'JPG',
                3 => 'PNG',
                4 => 'SWF',
                5 => 'PSD',
                6 => 'BMP',
                7 => 'TIFF(intel byte order)',
                8 => 'TIFF(motorola byte order)',
                9 => 'JPC',
                10 => 'JP2',
                11 => 'JPX',
                12 => 'JB2',
                13 => 'SWC',
                14 => 'IFF',
                15 => 'WBMP',
                16 => 'XBM'
            );

            $image = get_headers($path, 1);

            $imageKb = $image["Content-Length"]/1024;



            list($width, $height, $type) = getimagesize($path);

            return array(
                'width' => $width,
                'height' => $height,
                'type' => $types[$type],
                'size' => number_format($imageKb,0) . "kb",
            );
        } catch (\Exception $e) {
            return array (
                'width' => null,
                'height' => null,
                'type' => null,
                'size' => null,
            );
        }
    }

    public function cheapiso639($code)
    {
        switch ($code) {
            case "aar":
                return "Afar";
            case "abk":
                return "Abkhazian";
            case "ace":
                return "Achinese";
            case "ach":
                return "Acoli";
            case "ada":
                return "Adangme";
            case "ady":
                return "Adyghe; Adygei";
            case "afa":
                return "Afro-Asiatic languages";
            case "afh":
                return "Afrihili";
            case "afr":
                return "Afrikaans";
            case "ain":
                return "Ainu";
            case "aka":
                return "Akan";
            case "akk":
                return "Akkadian";
            case "alb":
                return "Albanian";
            case "alb":
                return "Albanian";
            case "ale":
                return "Aleut";
            case "alg":
                return "Algonquian languages";
            case "alt":
                return "Southern Altai";
            case "amh":
                return "Amharic";
            case "ang":
                return "English, Old (ca.450-1100)";
            case "anp":
                return "Angika";
            case "apa":
                return "Apache languages";
            case "ara":
                return "Arabic";
            case "arc":
                return "Official Aramaic (700-300 BCE); Imperial Aramaic (700-300 BCE)";
            case "arg":
                return "Aragonese";
            case "arm":
                return "Armenian";
            case "arn":
                return "Mapudungun; Mapuche";
            case "arp":
                return "Arapaho";
            case "art":
                return "Artificial languages";
            case "arw":
                return "Arawak";
            case "asm":
                return "Assamese";
            case "ast":
                return "Asturian; Bable; Leonese; Asturleonese";
            case "ath":
                return "Athapascan languages";
            case "aus":
                return "Australian languages";
            case "ava":
                return "Avaric";
            case "ave":
                return "Avestan";
            case "awa":
                return "Awadhi";
            case "aym":
                return "Aymara";
            case "aze":
                return "Azerbaijani";
            case "bad":
                return "Banda languages";
            case "bai":
                return "Bamileke languages";
            case "bak":
                return "Bashkir";
            case "bal":
                return "Baluchi";
            case "bam":
                return "Bambara";
            case "ban":
                return "Balinese";
            case "baq":
                return "Basque";
            case "bas":
                return "Basa";
            case "bat":
                return "Baltic languages";
            case "bej":
                return "Beja; Bedawiyet";
            case "bel":
                return "Belarusian";
            case "bem":
                return "Bemba";
            case "ben":
                return "Bengali";
            case "ber":
                return "Berber languages";
            case "bho":
                return "Bhojpuri";
            case "bih":
                return "Bihari languages";
            case "bik":
                return "Bikol";
            case "bin":
                return "Bini; Edo";
            case "bis":
                return "Bislama";
            case "bla":
                return "Siksika";
            case "bnt":
                return "Bantu languages";
            case "bos":
                return "Bosnian";
            case "bra":
                return "Braj";
            case "bre":
                return "Breton";
            case "btk":
                return "Batak languages";
            case "bua":
                return "Buriat";
            case "bug":
                return "Buginese";
            case "bul":
                return "Bulgarian";
            case "bur":
                return "Burmese";
            case "bur":
                return "Burmese";
            case "byn":
                return "Blin; Bilin";
            case "cad":
                return "Caddo";
            case "cai":
                return "Central American Indian languages";
            case "car":
                return "Galibi Carib";
            case "cat":
                return "Catalan; Valencian";
            case "cau":
                return "Caucasian languages";
            case "ceb":
                return "Cebuano";
            case "cel":
                return "Celtic languages";
            case "cha":
                return "Chamorro";
            case "chb":
                return "Chibcha";
            case "che":
                return "Chechen";
            case "chg":
                return "Chagatai";
            case "chi":
                return "Chinese";
            case "chk":
                return "Chuukese";
            case "chm":
                return "Mari";
            case "chn":
                return "Chinook jargon";
            case "cho":
                return "Choctaw";
            case "chp":
                return "Chipewyan; Dene Suline";
            case "chr":
                return "Cherokee";
            case "chu":
                return "Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic";
            case "chv":
                return "Chuvash";
            case "chy":
                return "Cheyenne";
            case "cmc":
                return "Chamic languages";
            case "cop":
                return "Coptic";
            case "cor":
                return "Cornish";
            case "cos":
                return "Corsican";
            case "cpe":
                return "Creoles and pidgins, English based";
            case "cpf":
                return "Creoles and pidgins, French-based";
            case "cpp":
                return "Creoles and pidgins, Portuguese-based";
            case "cre":
                return "Cree";
            case "crh":
                return "Crimean Tatar; Crimean Turkish";
            case "crp":
                return "Creoles and pidgins";
            case "csb":
                return "Kashubian";
            case "cus":
                return "Cushitic languages";
            case "cze":
                return "Czech";
            case "dak":
                return "Dakota";
            case "dan":
                return "Danish";
            case "dar":
                return "Dargwa";
            case "day":
                return "Land Dayak languages";
            case "del":
                return "Delaware";
            case "den":
                return "Slave (Athapascan)";
            case "dgr":
                return "Dogrib";
            case "din":
                return "Dinka";
            case "div":
                return "Divehi; Dhivehi; Maldivian";
            case "doi":
                return "Dogri";
            case "dra":
                return "Dravidian languages";
            case "dsb":
                return "Lower Sorbian";
            case "dua":
                return "Duala";
            case "dum":
                return "Dutch, Middle (ca.1050-1350)";
            case "dut":
                return "Dutch; Flemish";
            case "dyu":
                return "Dyula";
            case "dzo":
                return "Dzongkha";
            case "efi":
                return "Efik";
            case "egy":
                return "Egyptian (Ancient)";
            case "eka":
                return "Ekajuk";
            case "elx":
                return "Elamite";
            case "eng":
                return "English";
            case "enm":
                return "English, Middle (1100-1500)";
            case "epo":
                return "Esperanto";
            case "est":
                return "Estonian";
            case "ewe":
                return "Ewe";
            case "ewo":
                return "Ewondo";
            case "fan":
                return "Fang";
            case "fao":
                return "Faroese";
            case "fat":
                return "Fanti";
            case "fij":
                return "Fijian";
            case "fil":
                return "Filipino; Pilipino";
            case "fin":
                return "Finnish";
            case "fiu":
                return "Finno-Ugrian languages";
            case "fon":
                return "Fon";
            case "fre":
                return "French";
            case "frm":
                return "French, Middle (ca.1400-1600)";
            case "fro":
                return "French, Old (842-ca.1400)";
            case "frr":
                return "Northern Frisian";
            case "frs":
                return "Eastern Frisian";
            case "fry":
                return "Western Frisian";
            case "ful":
                return "Fulah";
            case "fur":
                return "Friulian";
            case "gaa":
                return "Ga";
            case "gay":
                return "Gayo";
            case "gba":
                return "Gbaya";
            case "gem":
                return "Germanic languages";
            case "geo":
                return "Georgian";
            case "ger":
                return "German";
            case "gez":
                return "Geez";
            case "gil":
                return "Gilbertese";
            case "gla":
                return "Gaelic; Scottish Gaelic";
            case "gle":
                return "Irish";
            case "glg":
                return "Galician";
            case "glv":
                return "Manx";
            case "gmh":
                return "German, Middle High (ca.1050-1500)";
            case "goh":
                return "German, Old High (ca.750-1050)";
            case "gon":
                return "Gondi";
            case "gor":
                return "Gorontalo";
            case "got":
                return "Gothic";
            case "grb":
                return "Grebo";
            case "grc":
                return "Greek, Ancient (to 1453)";
            case "gre":
                return "Greek, Modern (1453-)";
            case "grn":
                return "Guarani";
            case "gsw":
                return "Swiss German; Alemannic; Alsatian";
            case "guj":
                return "Gujarati";
            case "gwi":
                return "Gwich'in";
            case "hai":
                return "Haida";
            case "hat":
                return "Haitian; Haitian Creole";
            case "hau":
                return "Hausa";
            case "haw":
                return "Hawaiian";
            case "heb":
                return "Hebrew";
            case "her":
                return "Herero";
            case "hil":
                return "Hiligaynon";
            case "him":
                return "Himachali languages; Western Pahari languages";
            case "hin":
                return "Hindi";
            case "hit":
                return "Hittite";
            case "hmn":
                return "Hmong; Mong";
            case "hmo":
                return "Hiri Motu";
            case "hrv":
                return "Croatian";
            case "hsb":
                return "Upper Sorbian";
            case "hun":
                return "Hungarian";
            case "hup":
                return "Hupa";
            case "iba":
                return "Iban";
            case "ibo":
                return "Igbo";
            case "ice":
                return "Icelandic";
            case "ice":
                return "Icelandic";
            case "ido":
                return "Ido";
            case "iii":
                return "Sichuan Yi; Nuosu";
            case "ijo":
                return "Ijo languages";
            case "iku":
                return "Inuktitut";
            case "ile":
                return "Interlingue; Occidental";
            case "ilo":
                return "Iloko";
            case "ina":
                return "Interlingua (International Auxiliary Language Association)";
            case "inc":
                return "Indic languages";
            case "ind":
                return "Indonesian";
            case "ine":
                return "Indo-European languages";
            case "inh":
                return "Ingush";
            case "ipk":
                return "Inupiaq";
            case "ira":
                return "Iranian languages";
            case "iro":
                return "Iroquoian languages";
            case "ita":
                return "Italian";
            case "jav":
                return "Javanese";
            case "jbo":
                return "Lojban";
            case "jpn":
                return "Japanese";
            case "jpr":
                return "Judeo-Persian";
            case "jrb":
                return "Judeo-Arabic";
            case "kaa":
                return "Kara-Kalpak";
            case "kab":
                return "Kabyle";
            case "kac":
                return "Kachin; Jingpho";
            case "kal":
                return "Kalaallisut; Greenlandic";
            case "kam":
                return "Kamba";
            case "kan":
                return "Kannada";
            case "kar":
                return "Karen languages";
            case "kas":
                return "Kashmiri";
            case "kau":
                return "Kanuri";
            case "kaw":
                return "Kawi";
            case "kaz":
                return "Kazakh";
            case "kbd":
                return "Kabardian";
            case "kha":
                return "Khasi";
            case "khi":
                return "Khoisan languages";
            case "khm":
                return "Central Khmer";
            case "kho":
                return "Khotanese; Sakan";
            case "kik":
                return "Kikuyu; Gikuyu";
            case "kin":
                return "Kinyarwanda";
            case "kir":
                return "Kirghiz; Kyrgyz";
            case "kmb":
                return "Kimbundu";
            case "kok":
                return "Konkani";
            case "kom":
                return "Komi";
            case "kon":
                return "Kongo";
            case "kor":
                return "Korean";
            case "kos":
                return "Kosraean";
            case "kpe":
                return "Kpelle";
            case "krc":
                return "Karachay-Balkar";
            case "krl":
                return "Karelian";
            case "kro":
                return "Kru languages";
            case "kru":
                return "Kurukh";
            case "kua":
                return "Kuanyama; Kwanyama";
            case "kum":
                return "Kumyk";
            case "kur":
                return "Kurdish";
            case "kut":
                return "Kutenai";
            case "lad":
                return "Ladino";
            case "lah":
                return "Lahnda";
            case "lam":
                return "Lamba";
            case "lao":
                return "Lao";
            case "lat":
                return "Latin";
            case "lav":
                return "Latvian";
            case "lez":
                return "Lezghian";
            case "lim":
                return "Limburgan; Limburger; Limburgish";
            case "lin":
                return "Lingala";
            case "lit":
                return "Lithuanian";
            case "lol":
                return "Mongo";
            case "loz":
                return "Lozi";
            case "ltz":
                return "Luxembourgish; Letzeburgesch";
            case "lua":
                return "Luba-Lulua";
            case "lub":
                return "Luba-Katanga";
            case "lug":
                return "Ganda";
            case "lui":
                return "Luiseno";
            case "lun":
                return "Lunda";
            case "luo":
                return "Luo (Kenya and Tanzania)";
            case "lus":
                return "Lushai";
            case "mac":
                return "Macedonian";
            case "mad":
                return "Madurese";
            case "mag":
                return "Magahi";
            case "mah":
                return "Marshallese";
            case "mai":
                return "Maithili";
            case "mak":
                return "Makasar";
            case "mal":
                return "Malayalam";
            case "man":
                return "Mandingo";
            case "mao":
                return "Maori";
            case "map":
                return "Austronesian languages";
            case "mar":
                return "Marathi";
            case "mas":
                return "Masai";
            case "may":
                return "Malay";
            case "mdf":
                return "Moksha";
            case "mdr":
                return "Mandar";
            case "men":
                return "Mende";
            case "mga":
                return "Irish, Middle (900-1200)";
            case "mic":
                return "Mi'kmaq; Micmac";
            case "min":
                return "Minangkabau";
            case "mis":
                return "Uncoded languages";
            case "mkh":
                return "Mon-Khmer languages";
            case "mlg":
                return "Malagasy";
            case "mlt":
                return "Maltese";
            case "mnc":
                return "Manchu";
            case "mni":
                return "Manipuri";
            case "mno":
                return "Manobo languages";
            case "moh":
                return "Mohawk";
            case "mon":
                return "Mongolian";
            case "mos":
                return "Mossi";
            case "mul":
                return "Multiple languages";
            case "mun":
                return "Munda languages";
            case "mus":
                return "Creek";
            case "mwl":
                return "Mirandese";
            case "mwr":
                return "Marwari";
            case "myn":
                return "Mayan languages";
            case "myv":
                return "Erzya";
            case "nah":
                return "Nahuatl languages";
            case "nai":
                return "North American Indian languages";
            case "nap":
                return "Neapolitan";
            case "nau":
                return "Nauru";
            case "nav":
                return "Navajo; Navaho";
            case "nbl":
                return "Ndebele, South; South Ndebele";
            case "nde":
                return "Ndebele, North; North Ndebele";
            case "ndo":
                return "Ndonga";
            case "nds":
                return "Low German; Low Saxon; German, Low; Saxon, Low";
            case "nep":
                return "Nepali";
            case "new":
                return "Nepal Bhasa; Newari";
            case "nia":
                return "Nias";
            case "nic":
                return "Niger-Kordofanian languages";
            case "niu":
                return "Niuean";
            case "nno":
                return "Norwegian Nynorsk; Nynorsk, Norwegian";
            case "nob":
                return "Bokmål, Norwegian; Norwegian Bokmål";
            case "nog":
                return "Nogai";
            case "non":
                return "Norse, Old";
            case "nor":
                return "Norwegian";
            case "nqo":
                return "N'Ko";
            case "nso":
                return "Pedi; Sepedi; Northern Sotho";
            case "nub":
                return "Nubian languages";
            case "nwc":
                return "Classical Newari; Old Newari; Classical Nepal Bhasa";
            case "nya":
                return "Chichewa; Chewa; Nyanja";
            case "nym":
                return "Nyamwezi";
            case "nyn":
                return "Nyankole";
            case "nyo":
                return "Nyoro";
            case "nzi":
                return "Nzima";
            case "oci":
                return "Occitan (post 1500)";
            case "oji":
                return "Ojibwa";
            case "ori":
                return "Oriya";
            case "orm":
                return "Oromo";
            case "osa":
                return "Osage";
            case "oss":
                return "Ossetian; Ossetic";
            case "ota":
                return "Turkish, Ottoman (1500-1928)";
            case "oto":
                return "Otomian languages";
            case "paa":
                return "Papuan languages";
            case "pag":
                return "Pangasinan";
            case "pal":
                return "Pahlavi";
            case "pam":
                return "Pampanga; Kapampangan";
            case "pan":
                return "Panjabi; Punjabi";
            case "pap":
                return "Papiamento";
            case "pau":
                return "Palauan";
            case "peo":
                return "Persian, Old (ca.600-400 B.C.)";
            case "per":
                return "Persian";
            case "phi":
                return "Philippine languages";
            case "phn":
                return "Phoenician";
            case "pli":
                return "Pali";
            case "pol":
                return "Polish";
            case "pon":
                return "Pohnpeian";
            case "por":
                return "Portuguese";
            case "pra":
                return "Prakrit languages";
            case "pro":
                return "Provençal, Old (to 1500);Occitan, Old (to 1500)";
            case "pus":
                return "Pushto; Pashto";
            case "que":
                return "Quechua";
            case "raj":
                return "Rajasthani";
            case "rap":
                return "Rapanui";
            case "rar":
                return "Rarotongan; Cook Islands Maori";
            case "roa":
                return "Romance languages";
            case "roh":
                return "Romansh";
            case "rom":
                return "Romany";
            case "rum":
                return "Romanian; Moldavian; Moldovan";
            case "run":
                return "Rundi";
            case "rup":
                return "Aromanian; Arumanian; Macedo-Romanian";
            case "rus":
                return "Russian";
            case "sad":
                return "Sandawe";
            case "sag":
                return "Sango";
            case "sah":
                return "Yakut";
            case "sai":
                return "South American Indian languages";
            case "sal":
                return "Salishan languages";
            case "sam":
                return "Samaritan Aramaic";
            case "san":
                return "Sanskrit";
            case "sas":
                return "Sasak";
            case "sat":
                return "Santali";
            case "scn":
                return "Sicilian";
            case "sco":
                return "Scots";
            case "sel":
                return "Selkup";
            case "sem":
                return "Semitic languages";
            case "sga":
                return "Irish, Old (to 900)";
            case "sgn":
                return "Sign Languages";
            case "shn":
                return "Shan";
            case "sid":
                return "Sidamo";
            case "sin":
                return "Sinhala; Sinhalese";
            case "sio":
                return "Siouan languages";
            case "sit":
                return "Sino-Tibetan languages";
            case "sla":
                return "Slavic languages";
            case "slo":
                return "Slovak";
            case "slv":
                return "Slovenian";
            case "sma":
                return "Southern Sami";
            case "sme":
                return "Northern Sami";
            case "smi":
                return "Sami languages";
            case "smj":
                return "Lule Sami";
            case "smn":
                return "Inari Sami";
            case "smo":
                return "Samoan";
            case "sms":
                return "Skolt Sami";
            case "sna":
                return "Shona";
            case "snd":
                return "Sindhi";
            case "snk":
                return "Soninke";
            case "sog":
                return "Sogdian";
            case "som":
                return "Somali";
            case "son":
                return "Songhai languages";
            case "sot":
                return "Sotho, Southern";
            case "spa":
                return "Spanish; Castilian";
            case "srd":
                return "Sardinian";
            case "srn":
                return "Sranan Tongo";
            case "srp":
                return "Serbian";
            case "srr":
                return "Serer";
            case "ssa":
                return "Nilo-Saharan languages";
            case "ssw":
                return "Swati";
            case "suk":
                return "Sukuma";
            case "sun":
                return "Sundanese";
            case "sus":
                return "Susu";
            case "sux":
                return "Sumerian";
            case "swa":
                return "Swahili";
            case "swe":
                return "Swedish";
            case "syc":
                return "Classical Syriac";
            case "syr":
                return "Syriac";
            case "tah":
                return "Tahitian";
            case "tai":
                return "Tai languages";
            case "tam":
                return "Tamil";
            case "tat":
                return "Tatar";
            case "tel":
                return "Telugu";
            case "tem":
                return "Timne";
            case "ter":
                return "Tereno";
            case "tet":
                return "Tetum";
            case "tgk":
                return "Tajik";
            case "tgl":
                return "Tagalog";
            case "tha":
                return "Thai";
            case "tib":
                return "Tibetan";
            case "tig":
                return "Tigre";
            case "tir":
                return "Tigrinya";
            case "tiv":
                return "Tiv";
            case "tkl":
                return "Tokelau";
            case "tlh":
                return "Klingon; tlhIngan-Hol";
            case "tli":
                return "Tlingit";
            case "tmh":
                return "Tamashek";
            case "tog":
                return "Tonga (Nyasa)";
            case "ton":
                return "Tonga (Tonga Islands)";
            case "tpi":
                return "Tok Pisin";
            case "tsi":
                return "Tsimshian";
            case "tsn":
                return "Tswana";
            case "tso":
                return "Tsonga";
            case "tuk":
                return "Turkmen";
            case "tum":
                return "Tumbuka";
            case "tup":
                return "Tupi languages";
            case "tur":
                return "Turkish";
            case "tut":
                return "Altaic languages";
            case "tvl":
                return "Tuvalu";
            case "twi":
                return "Twi";
            case "tyv":
                return "Tuvinian";
            case "udm":
                return "Udmurt";
            case "uga":
                return "Ugaritic";
            case "uig":
                return "Uighur; Uyghur";
            case "ukr":
                return "Ukrainian";
            case "umb":
                return "Umbundu";
            case "und":
                return "Undetermined";
            case "urd":
                return "Urdu";
            case "uzb":
                return "Uzbek";
            case "vai":
                return "Vai";
            case "ven":
                return "Venda";
            case "vie":
                return "Vietnamese";
            case "vol":
                return "Volapük";
            case "vot":
                return "Votic";
            case "wak":
                return "Wakashan languages";
            case "wal":
                return "Wolaitta; Wolaytta";
            case "war":
                return "Waray";
            case "was":
                return "Washo";
            case "wel":
                return "Welsh";
            case "wen":
                return "Sorbian languages";
            case "wln":
                return "Walloon";
            case "wol":
                return "Wolof";
            case "xal":
                return "Kalmyk; Oirat";
            case "xho":
                return "Xhosa";
            case "yao":
                return "Yao";
            case "yap":
                return "Yapese";
            case "yid":
                return "Yiddish";
            case "yor":
                return "Yoruba";
            case "ypk":
                return "Yupik languages";
            case "zap":
                return "Zapotec";
            case "zbl":
                return "Blissymbols; Blissymbolics; Bliss";
            case "zen":
                return "Zenaga";
            case "zgh":
                return "Standard Moroccan Tamazight";
            case "zha":
                return "Zhuang; Chuang";
            case "znd":
                return "Zande languages";
            case "zul":
                return "Zulu";
            case "zun":
                return "Zuni";
            case "zxx":
                return "No linguistic content; Not applicable";
            case "zza":
                return "Zaza; Dimili; Dimli; Kirdki; Kirmanjki; Zazaki";
        }
    }

    public function cheapLanguageDropDown($langCodeArray)
    {
        $outvar = '';
        foreach ($langCodeArray as $code) {
            switch ($code) {
                case "aar":
                    $outvar .=  "<option value=\"aar\">Afar</option>";
                    break;
                case "abk":
                    $outvar .=  "<option value=\"abk\">Abkhazian</option>";
                    break;
                case "ace":
                    $outvar .=  "<option value=\"ace\">Achinese</option>";
                    break;
                case "ach":
                    $outvar .=  "<option value=\"ach\">Acoli</option>";
                    break;
                case "ada":
                    $outvar .=  "<option value=\"ada\">Adangme</option>";
                    break;
                case "ady":
                    $outvar .=  "<option value=\"ady\">Adyghe; Adygei</option>";
                    break;
                case "afa":
                    $outvar .=  "<option value=\"afa\">Afro-Asiatic languages</option>";
                    break;
                case "afh":
                    $outvar .=  "<option value=\"afh\">Afrihili</option>";
                    break;
                case "afr":
                    $outvar .=  "<option value=\"afr\">Afrikaans</option>";
                    break;
                case "ain":
                    $outvar .=  "<option value=\"ain\">Ainu</option>";
                    break;
                case "aka":
                    $outvar .=  "<option value=\"aka\">Akan</option>";
                    break;
                case "akk":
                    $outvar .=  "<option value=\"akk\">Akkadian</option>";
                    break;
                case "alb":
                    $outvar .=  "<option value=\"alb\">Albanian</option>";
                    break;
                case "alb":
                    $outvar .=  "<option value=\"alb\">Albanian</option>";
                    break;
                case "ale":
                    $outvar .=  "<option value=\"ale\">Aleut</option>";
                    break;
                case "alg":
                    $outvar .=  "<option value=\"alg\">Algonquian languages</option>";
                    break;
                case "alt":
                    $outvar .=  "<option value=\"alt\">Southern Altai</option>";
                    break;
                case "amh":
                    $outvar .=  "<option value=\"amh\">Amharic</option>";
                    break;
                case "ang":
                    $outvar .=  "<option value=\"ang\">English, Old (ca.450-1100)</option>";
                    break;
                case "anp":
                    $outvar .=  "<option value=\"anp\">Angika</option>";
                    break;
                case "apa":
                    $outvar .=  "<option value=\"apa\">Apache languages</option>";
                    break;
                case "ara":
                    $outvar .=  "<option value=\"ara\">Arabic</option>";
                    break;
                case "arc":
                    $outvar .=  "<option value=\"arc\">Official Aramaic (700-300 BCE); Imperial Aramaic (700-300 BCE)</option>";
                    break;
                case "arg":
                    $outvar .=  "<option value=\"arg\">Aragonese</option>";
                    break;
                case "arm":
                    $outvar .=  "<option value=\"arm\">Armenian</option>";
                    break;
                case "arn":
                    $outvar .=  "<option value=\"arn\">Mapudungun; Mapuche</option>";
                    break;
                case "arp":
                    $outvar .=  "<option value=\"arp\">Arapaho</option>";
                    break;
                case "art":
                    $outvar .=  "<option value=\"art\">Artificial languages</option>";
                    break;
                case "arw":
                    $outvar .=  "<option value=\"arw\">Arawak</option>";
                    break;
                case "asm":
                    $outvar .=  "<option value=\"asm\">Assamese</option>";
                    break;
                case "ast":
                    $outvar .=  "<option value=\"ast\">Asturian; Bable; Leonese; Asturleonese</option>";
                    break;
                case "ath":
                    $outvar .=  "<option value=\"ath\">Athapascan languages</option>";
                    break;
                case "aus":
                    $outvar .=  "<option value=\"aus\">Australian languages</option>";
                    break;
                case "ava":
                    $outvar .=  "<option value=\"ava\">Avaric</option>";
                    break;
                case "ave":
                    $outvar .=  "<option value=\"ave\">Avestan</option>";
                    break;
                case "awa":
                    $outvar .=  "<option value=\"awa\">Awadhi</option>";
                    break;
                case "aym":
                    $outvar .=  "<option value=\"aym\">Aymara</option>";
                    break;
                case "aze":
                    $outvar .=  "<option value=\"aze\">Azerbaijani</option>";
                    break;
                case "bad":
                    $outvar .=  "<option value=\"bad\">Banda languages</option>";
                    break;
                case "bai":
                    $outvar .=  "<option value=\"bai\">Bamileke languages</option>";
                    break;
                case "bak":
                    $outvar .=  "<option value=\"bak\">Bashkir</option>";
                    break;
                case "bal":
                    $outvar .=  "<option value=\"bal\">Baluchi</option>";
                    break;
                case "bam":
                    $outvar .=  "<option value=\"bam\">Bambara</option>";
                    break;
                case "ban":
                    $outvar .=  "<option value=\"ban\">Balinese</option>";
                    break;
                case "baq":
                    $outvar .=  "<option value=\"baq\">Basque</option>";
                    break;
                case "bas":
                    $outvar .=  "<option value=\"bas\">Basa</option>";
                    break;
                case "bat":
                    $outvar .=  "<option value=\"bat\">Baltic languages</option>";
                    break;
                case "bej":
                    $outvar .=  "<option value=\"bej\">Beja; Bedawiyet</option>";
                    break;
                case "bel":
                    $outvar .=  "<option value=\"bel\">Belarusian</option>";
                    break;
                case "bem":
                    $outvar .=  "<option value=\"bem\">Bemba</option>";
                    break;
                case "ben":
                    $outvar .=  "<option value=\"ben\">Bengali</option>";
                    break;
                case "ber":
                    $outvar .=  "<option value=\"ber\">Berber languages</option>";
                    break;
                case "bho":
                    $outvar .=  "<option value=\"bho\">Bhojpuri</option>";
                    break;
                case "bih":
                    $outvar .=  "<option value=\"bih\">Bihari languages</option>";
                    break;
                case "bik":
                    $outvar .=  "<option value=\"bik\">Bikol</option>";
                    break;
                case "bin":
                    $outvar .=  "<option value=\"bin\">Bini; Edo</option>";
                    break;
                case "bis":
                    $outvar .=  "<option value=\"bis\">Bislama</option>";
                    break;
                case "bla":
                    $outvar .=  "<option value=\"bla\">Siksika</option>";
                    break;
                case "bnt":
                    $outvar .=  "<option value=\"bnt\">Bantu languages</option>";
                    break;
                case "bos":
                    $outvar .=  "<option value=\"bos\">Bosnian</option>";
                    break;
                case "bra":
                    $outvar .=  "<option value=\"bra\">Braj</option>";
                    break;
                case "bre":
                    $outvar .=  "<option value=\"bre\">Breton</option>";
                    break;
                case "btk":
                    $outvar .=  "<option value=\"btk\">Batak languages</option>";
                    break;
                case "bua":
                    $outvar .=  "<option value=\"bua\">Buriat</option>";
                    break;
                case "bug":
                    $outvar .=  "<option value=\"bug\">Buginese</option>";
                    break;
                case "bul":
                    $outvar .=  "<option value=\"bul\">Bulgarian</option>";
                    break;
                case "bur":
                    $outvar .=  "<option value=\"bur\">Burmese</option>";
                    break;
                case "bur":
                    $outvar .=  "<option value=\"bur\">Burmese</option>";
                    break;
                case "byn":
                    $outvar .=  "<option value=\"byn\">Blin; Bilin</option>";
                    break;
                case "cad":
                    $outvar .=  "<option value=\"cad\">Caddo</option>";
                    break;
                case "cai":
                    $outvar .=  "<option value=\"cai\">Central American Indian languages</option>";
                    break;
                case "car":
                    $outvar .=  "<option value=\"car\">Galibi Carib</option>";
                    break;
                case "cat":
                    $outvar .=  "<option value=\"cat\">Catalan; Valencian</option>";
                    break;
                case "cau":
                    $outvar .=  "<option value=\"cau\">Caucasian languages</option>";
                    break;
                case "ceb":
                    $outvar .=  "<option value=\"ceb\">Cebuano</option>";
                    break;
                case "cel":
                    $outvar .=  "<option value=\"cel\">Celtic languages</option>";
                    break;
                case "cha":
                    $outvar .=  "<option value=\"cha\">Chamorro</option>";
                    break;
                case "chb":
                    $outvar .=  "<option value=\"chb\">Chibcha</option>";
                    break;
                case "che":
                    $outvar .=  "<option value=\"che\">Chechen</option>";
                    break;
                case "chg":
                    $outvar .=  "<option value=\"chg\">Chagatai</option>";
                    break;
                case "chi":
                    $outvar .=  "<option value=\"chi\">Chinese</option>";
                    break;
                case "chk":
                    $outvar .=  "<option value=\"chk\">Chuukese</option>";
                    break;
                case "chm":
                    $outvar .=  "<option value=\"chm\">Mari</option>";
                    break;
                case "chn":
                    $outvar .=  "<option value=\"chn\">Chinook jargon</option>";
                    break;
                case "cho":
                    $outvar .=  "<option value=\"cho\">Choctaw</option>";
                    break;
                case "chp":
                    $outvar .=  "<option value=\"chp\">Chipewyan; Dene Suline</option>";
                    break;
                case "chr":
                    $outvar .=  "<option value=\"chr\">Cherokee</option>";
                    break;
                case "chu":
                    $outvar .=  "<option value=\"chu\">Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic</option>";
                    break;
                case "chv":
                    $outvar .=  "<option value=\"chv\">Chuvash</option>";
                    break;
                case "chy":
                    $outvar .=  "<option value=\"chy\">Cheyenne</option>";
                    break;
                case "cmc":
                    $outvar .=  "<option value=\"cmc\">Chamic languages</option>";
                    break;
                case "cop":
                    $outvar .=  "<option value=\"cop\">Coptic</option>";
                    break;
                case "cor":
                    $outvar .=  "<option value=\"cor\">Cornish</option>";
                    break;
                case "cos":
                    $outvar .=  "<option value=\"cos\">Corsican</option>";
                    break;
                case "cpe":
                    $outvar .=  "<option value=\"cpe\">Creoles and pidgins, English based</option>";
                    break;
                case "cpf":
                    $outvar .=  "<option value=\"cpf\">Creoles and pidgins, French-based</option>";
                    break;
                case "cpp":
                    $outvar .=  "<option value=\"cpp\">Creoles and pidgins, Portuguese-based</option>";
                    break;
                case "cre":
                    $outvar .=  "<option value=\"cre\">Cree</option>";
                    break;
                case "crh":
                    $outvar .=  "<option value=\"crh\">Crimean Tatar; Crimean Turkish</option>";
                    break;
                case "crp":
                    $outvar .=  "<option value=\"crp\">Creoles and pidgins</option>";
                    break;
                case "csb":
                    $outvar .=  "<option value=\"csb\">Kashubian</option>";
                    break;
                case "cus":
                    $outvar .=  "<option value=\"cus\">Cushitic languages</option>";
                    break;
                case "cze":
                    $outvar .=  "<option value=\"cze\">Czech</option>";
                    break;
                case "dak":
                    $outvar .=  "<option value=\"dak\">Dakota</option>";
                    break;
                case "dan":
                    $outvar .=  "<option value=\"dan\">Danish</option>";
                    break;
                case "dar":
                    $outvar .=  "<option value=\"dar\">Dargwa</option>";
                    break;
                case "day":
                    $outvar .=  "<option value=\"day\">Land Dayak languages</option>";
                    break;
                case "del":
                    $outvar .=  "<option value=\"del\">Delaware</option>";
                    break;
                case "den":
                    $outvar .=  "<option value=\"den\">Slave (Athapascan)</option>";
                    break;
                case "dgr":
                    $outvar .=  "<option value=\"dgr\">Dogrib</option>";
                    break;
                case "din":
                    $outvar .=  "<option value=\"din\">Dinka</option>";
                    break;
                case "div":
                    $outvar .=  "<option value=\"div\">Divehi; Dhivehi; Maldivian</option>";
                    break;
                case "doi":
                    $outvar .=  "<option value=\"doi\">Dogri</option>";
                    break;
                case "dra":
                    $outvar .=  "<option value=\"dra\">Dravidian languages</option>";
                    break;
                case "dsb":
                    $outvar .=  "<option value=\"dsb\">Lower Sorbian</option>";
                    break;
                case "dua":
                    $outvar .=  "<option value=\"dua\">Duala</option>";
                    break;
                case "dum":
                    $outvar .=  "<option value=\"dum\">Dutch, Middle (ca.1050-1350)</option>";
                    break;
                case "dut":
                    $outvar .=  "<option value=\"dut\">Dutch; Flemish</option>";
                    break;
                case "dyu":
                    $outvar .=  "<option value=\"dyu\">Dyula</option>";
                    break;
                case "dzo":
                    $outvar .=  "<option value=\"dzo\">Dzongkha</option>";
                    break;
                case "efi":
                    $outvar .=  "<option value=\"efi\">Efik</option>";
                    break;
                case "egy":
                    $outvar .=  "<option value=\"egy\">Egyptian (Ancient)</option>";
                    break;
                case "eka":
                    $outvar .=  "<option value=\"eka\">Ekajuk</option>";
                    break;
                case "elx":
                    $outvar .=  "<option value=\"elx\">Elamite</option>";
                    break;
                case "eng":
                    $outvar .=  "<option value=\"eng\">English</option>";
                    break;
                case "enm":
                    $outvar .=  "<option value=\"enm\">English, Middle (1100-1500)</option>";
                    break;
                case "epo":
                    $outvar .=  "<option value=\"epo\">Esperanto</option>";
                    break;
                case "est":
                    $outvar .=  "<option value=\"est\">Estonian</option>";
                    break;
                case "ewe":
                    $outvar .=  "<option value=\"ewe\">Ewe</option>";
                    break;
                case "ewo":
                    $outvar .=  "<option value=\"ewo\">Ewondo</option>";
                    break;
                case "fan":
                    $outvar .=  "<option value=\"fan\">Fang</option>";
                    break;
                case "fao":
                    $outvar .=  "<option value=\"fao\">Faroese</option>";
                    break;
                case "fat":
                    $outvar .=  "<option value=\"fat\">Fanti</option>";
                    break;
                case "fij":
                    $outvar .=  "<option value=\"fij\">Fijian</option>";
                    break;
                case "fil":
                    $outvar .=  "<option value=\"fil\">Filipino; Pilipino</option>";
                    break;
                case "fin":
                    $outvar .=  "<option value=\"fin\">Finnish</option>";
                    break;
                case "fiu":
                    $outvar .=  "<option value=\"fiu\">Finno-Ugrian languages</option>";
                    break;
                case "fon":
                    $outvar .=  "<option value=\"fon\">Fon</option>";
                    break;
                case "fre":
                    $outvar .=  "<option value=\"fre\">French</option>";
                    break;
                case "frm":
                    $outvar .=  "<option value=\"frm\">French, Middle (ca.1400-1600)</option>";
                    break;
                case "fro":
                    $outvar .=  "<option value=\"fro\">French, Old (842-ca.1400)</option>";
                    break;
                case "frr":
                    $outvar .=  "<option value=\"frr\">Northern Frisian</option>";
                    break;
                case "frs":
                    $outvar .=  "<option value=\"frs\">Eastern Frisian</option>";
                    break;
                case "fry":
                    $outvar .=  "<option value=\"fry\">Western Frisian</option>";
                    break;
                case "ful":
                    $outvar .=  "<option value=\"ful\">Fulah</option>";
                    break;
                case "fur":
                    $outvar .=  "<option value=\"fur\">Friulian</option>";
                    break;
                case "gaa":
                    $outvar .=  "<option value=\"gaa\">Ga</option>";
                    break;
                case "gay":
                    $outvar .=  "<option value=\"gay\">Gayo</option>";
                    break;
                case "gba":
                    $outvar .=  "<option value=\"gba\">Gbaya</option>";
                    break;
                case "gem":
                    $outvar .=  "<option value=\"gem\">Germanic languages</option>";
                    break;
                case "geo":
                    $outvar .=  "<option value=\"geo\">Georgian</option>";
                    break;
                case "ger":
                    $outvar .=  "<option value=\"ger\">German</option>";
                    break;
                case "gez":
                    $outvar .=  "<option value=\"gez\">Geez</option>";
                    break;
                case "gil":
                    $outvar .=  "<option value=\"gil\">Gilbertese</option>";
                    break;
                case "gla":
                    $outvar .=  "<option value=\"gla\">Gaelic; Scottish Gaelic</option>";
                    break;
                case "gle":
                    $outvar .=  "<option value=\"gle\">Irish</option>";
                    break;
                case "glg":
                    $outvar .=  "<option value=\"glg\">Galician</option>";
                    break;
                case "glv":
                    $outvar .=  "<option value=\"glv\">Manx</option>";
                    break;
                case "gmh":
                    $outvar .=  "<option value=\"gmh\">German, Middle High (ca.1050-1500)</option>";
                    break;
                case "goh":
                    $outvar .=  "<option value=\"goh\">German, Old High (ca.750-1050)</option>";
                    break;
                case "gon":
                    $outvar .=  "<option value=\"gon\">Gondi</option>";
                    break;
                case "gor":
                    $outvar .=  "<option value=\"gor\">Gorontalo</option>";
                    break;
                case "got":
                    $outvar .=  "<option value=\"got\">Gothic</option>";
                    break;
                case "grb":
                    $outvar .=  "<option value=\"grb\">Grebo</option>";
                    break;
                case "grc":
                    $outvar .=  "<option value=\"grc\">Greek, Ancient (to 1453)</option>";
                    break;
                case "gre":
                    $outvar .=  "<option value=\"gre\">Greek, Modern (1453-)</option>";
                    break;
                case "grn":
                    $outvar .=  "<option value=\"grn\">Guarani</option>";
                    break;
                case "gsw":
                    $outvar .=  "<option value=\"gsw\">Swiss German; Alemannic; Alsatian</option>";
                    break;
                case "guj":
                    $outvar .=  "<option value=\"guj\">Gujarati</option>";
                    break;
                case "gwi":
                    $outvar .=  "<option value=\"gwi\">Gwich'in</option>";
                    break;
                case "hai":
                    $outvar .=  "<option value=\"hai\">Haida</option>";
                    break;
                case "hat":
                    $outvar .=  "<option value=\"hat\">Haitian; Haitian Creole</option>";
                    break;
                case "hau":
                    $outvar .=  "<option value=\"hau\">Hausa</option>";
                    break;
                case "haw":
                    $outvar .=  "<option value=\"haw\">Hawaiian</option>";
                    break;
                case "heb":
                    $outvar .=  "<option value=\"heb\">Hebrew</option>";
                    break;
                case "her":
                    $outvar .=  "<option value=\"her\">Herero</option>";
                    break;
                case "hil":
                    $outvar .=  "<option value=\"hil\">Hiligaynon</option>";
                    break;
                case "him":
                    $outvar .=  "<option value=\"him\">Himachali languages; Western Pahari languages</option>";
                    break;
                case "hin":
                    $outvar .=  "<option value=\"hin\">Hindi</option>";
                    break;
                case "hit":
                    $outvar .=  "<option value=\"hit\">Hittite</option>";
                    break;
                case "hmn":
                    $outvar .=  "<option value=\"hmn\">Hmong; Mong</option>";
                    break;
                case "hmo":
                    $outvar .=  "<option value=\"hmo\">Hiri Motu</option>";
                    break;
                case "hrv":
                    $outvar .=  "<option value=\"hrv\">Croatian</option>";
                    break;
                case "hsb":
                    $outvar .=  "<option value=\"hsb\">Upper Sorbian</option>";
                    break;
                case "hun":
                    $outvar .=  "<option value=\"hun\">Hungarian</option>";
                    break;
                case "hup":
                    $outvar .=  "<option value=\"hup\">Hupa</option>";
                    break;
                case "iba":
                    $outvar .=  "<option value=\"iba\">Iban</option>";
                    break;
                case "ibo":
                    $outvar .=  "<option value=\"ibo\">Igbo</option>";
                    break;
                case "ice":
                    $outvar .=  "<option value=\"ice\">Icelandic</option>";
                    break;
                case "ice":
                    $outvar .=  "<option value=\"ice\">Icelandic</option>";
                    break;
                case "ido":
                    $outvar .=  "<option value=\"ido\">Ido</option>";
                    break;
                case "iii":
                    $outvar .=  "<option value=\"iii\">Sichuan Yi; Nuosu</option>";
                    break;
                case "ijo":
                    $outvar .=  "<option value=\"ijo\">Ijo languages</option>";
                    break;
                case "iku":
                    $outvar .=  "<option value=\"iku\">Inuktitut</option>";
                    break;
                case "ile":
                    $outvar .=  "<option value=\"ile\">Interlingue; Occidental</option>";
                    break;
                case "ilo":
                    $outvar .=  "<option value=\"ilo\">Iloko</option>";
                    break;
                case "ina":
                    $outvar .=  "<option value=\"ina\">Interlingua (International Auxiliary Language Association)</option>";
                    break;
                case "inc":
                    $outvar .=  "<option value=\"inc\">Indic languages</option>";
                    break;
                case "ind":
                    $outvar .=  "<option value=\"ind\">Indonesian</option>";
                    break;
                case "ine":
                    $outvar .=  "<option value=\"ine\">Indo-European languages</option>";
                    break;
                case "inh":
                    $outvar .=  "<option value=\"inh\">Ingush</option>";
                    break;
                case "ipk":
                    $outvar .=  "<option value=\"ipk\">Inupiaq</option>";
                    break;
                case "ira":
                    $outvar .=  "<option value=\"ira\">Iranian languages</option>";
                    break;
                case "iro":
                    $outvar .=  "<option value=\"iro\">Iroquoian languages</option>";
                    break;
                case "ita":
                    $outvar .=  "<option value=\"ita\">Italian</option>";
                    break;
                case "jav":
                    $outvar .=  "<option value=\"jav\">Javanese</option>";
                    break;
                case "jbo":
                    $outvar .=  "<option value=\"jbo\">Lojban</option>";
                    break;
                case "jpn":
                    $outvar .=  "<option value=\"jpn\">Japanese</option>";
                    break;
                case "jpr":
                    $outvar .=  "<option value=\"jpr\">Judeo-Persian</option>";
                    break;
                case "jrb":
                    $outvar .=  "<option value=\"jrb\">Judeo-Arabic</option>";
                    break;
                case "kaa":
                    $outvar .=  "<option value=\"kaa\">Kara-Kalpak</option>";
                    break;
                case "kab":
                    $outvar .=  "<option value=\"kab\">Kabyle</option>";
                    break;
                case "kac":
                    $outvar .=  "<option value=\"kac\">Kachin; Jingpho</option>";
                    break;
                case "kal":
                    $outvar .=  "<option value=\"kal\">Kalaallisut; Greenlandic</option>";
                    break;
                case "kam":
                    $outvar .=  "<option value=\"kam\">Kamba</option>";
                    break;
                case "kan":
                    $outvar .=  "<option value=\"kan\">Kannada</option>";
                    break;
                case "kar":
                    $outvar .=  "<option value=\"kar\">Karen languages</option>";
                    break;
                case "kas":
                    $outvar .=  "<option value=\"kas\">Kashmiri</option>";
                    break;
                case "kau":
                    $outvar .=  "<option value=\"kau\">Kanuri</option>";
                    break;
                case "kaw":
                    $outvar .=  "<option value=\"kaw\">Kawi</option>";
                    break;
                case "kaz":
                    $outvar .=  "<option value=\"kaz\">Kazakh</option>";
                    break;
                case "kbd":
                    $outvar .=  "<option value=\"kbd\">Kabardian</option>";
                    break;
                case "kha":
                    $outvar .=  "<option value=\"kha\">Khasi</option>";
                    break;
                case "khi":
                    $outvar .=  "<option value=\"khi\">Khoisan languages</option>";
                    break;
                case "khm":
                    $outvar .=  "<option value=\"khm\">Central Khmer</option>";
                    break;
                case "kho":
                    $outvar .=  "<option value=\"kho\">Khotanese; Sakan</option>";
                    break;
                case "kik":
                    $outvar .=  "<option value=\"kik\">Kikuyu; Gikuyu</option>";
                    break;
                case "kin":
                    $outvar .=  "<option value=\"kin\">Kinyarwanda</option>";
                    break;
                case "kir":
                    $outvar .=  "<option value=\"kir\">Kirghiz; Kyrgyz</option>";
                    break;
                case "kmb":
                    $outvar .=  "<option value=\"kmb\">Kimbundu</option>";
                    break;
                case "kok":
                    $outvar .=  "<option value=\"kok\">Konkani</option>";
                    break;
                case "kom":
                    $outvar .=  "<option value=\"kom\">Komi</option>";
                    break;
                case "kon":
                    $outvar .=  "<option value=\"kon\">Kongo</option>";
                    break;
                case "kor":
                    $outvar .=  "<option value=\"kor\">Korean</option>";
                    break;
                case "kos":
                    $outvar .=  "<option value=\"kos\">Kosraean</option>";
                    break;
                case "kpe":
                    $outvar .=  "<option value=\"kpe\">Kpelle</option>";
                    break;
                case "krc":
                    $outvar .=  "<option value=\"krc\">Karachay-Balkar</option>";
                    break;
                case "krl":
                    $outvar .=  "<option value=\"krl\">Karelian</option>";
                    break;
                case "kro":
                    $outvar .=  "<option value=\"kro\">Kru languages</option>";
                    break;
                case "kru":
                    $outvar .=  "<option value=\"kru\">Kurukh</option>";
                    break;
                case "kua":
                    $outvar .=  "<option value=\"kua\">Kuanyama; Kwanyama</option>";
                    break;
                case "kum":
                    $outvar .=  "<option value=\"kum\">Kumyk</option>";
                    break;
                case "kur":
                    $outvar .=  "<option value=\"kur\">Kurdish</option>";
                    break;
                case "kut":
                    $outvar .=  "<option value=\"kut\">Kutenai</option>";
                    break;
                case "lad":
                    $outvar .=  "<option value=\"lad\">Ladino</option>";
                    break;
                case "lah":
                    $outvar .=  "<option value=\"lah\">Lahnda</option>";
                    break;
                case "lam":
                    $outvar .=  "<option value=\"lam\">Lamba</option>";
                    break;
                case "lao":
                    $outvar .=  "<option value=\"lao\">Lao</option>";
                    break;
                case "lat":
                    $outvar .=  "<option value=\"lat\">Latin</option>";
                    break;
                case "lav":
                    $outvar .=  "<option value=\"lav\">Latvian</option>";
                    break;
                case "lez":
                    $outvar .=  "<option value=\"lez\">Lezghian</option>";
                    break;
                case "lim":
                    $outvar .=  "<option value=\"lim\">Limburgan; Limburger; Limburgish</option>";
                    break;
                case "lin":
                    $outvar .=  "<option value=\"lin\">Lingala</option>";
                    break;
                case "lit":
                    $outvar .=  "<option value=\"lit\">Lithuanian</option>";
                    break;
                case "lol":
                    $outvar .=  "<option value=\"lol\">Mongo</option>";
                    break;
                case "loz":
                    $outvar .=  "<option value=\"loz\">Lozi</option>";
                    break;
                case "ltz":
                    $outvar .=  "<option value=\"ltz\">Luxembourgish; Letzeburgesch</option>";
                    break;
                case "lua":
                    $outvar .=  "<option value=\"lua\">Luba-Lulua</option>";
                    break;
                case "lub":
                    $outvar .=  "<option value=\"lub\">Luba-Katanga</option>";
                    break;
                case "lug":
                    $outvar .=  "<option value=\"lug\">Ganda</option>";
                    break;
                case "lui":
                    $outvar .=  "<option value=\"lui\">Luiseno</option>";
                    break;
                case "lun":
                    $outvar .=  "<option value=\"lun\">Lunda</option>";
                    break;
                case "luo":
                    $outvar .=  "<option value=\"luo\">Luo (Kenya and Tanzania)</option>";
                    break;
                case "lus":
                    $outvar .=  "<option value=\"lus\">Lushai</option>";
                    break;
                case "mac":
                    $outvar .=  "<option value=\"mac\">Macedonian</option>";
                    break;
                case "mad":
                    $outvar .=  "<option value=\"mad\">Madurese</option>";
                    break;
                case "mag":
                    $outvar .=  "<option value=\"mag\">Magahi</option>";
                    break;
                case "mah":
                    $outvar .=  "<option value=\"mah\">Marshallese</option>";
                    break;
                case "mai":
                    $outvar .=  "<option value=\"mai\">Maithili</option>";
                    break;
                case "mak":
                    $outvar .=  "<option value=\"mak\">Makasar</option>";
                    break;
                case "mal":
                    $outvar .=  "<option value=\"mal\">Malayalam</option>";
                    break;
                case "man":
                    $outvar .=  "<option value=\"man\">Mandingo</option>";
                    break;
                case "mao":
                    $outvar .=  "<option value=\"mao\">Maori</option>";
                    break;
                case "map":
                    $outvar .=  "<option value=\"map\">Austronesian languages</option>";
                    break;
                case "mar":
                    $outvar .=  "<option value=\"mar\">Marathi</option>";
                    break;
                case "mas":
                    $outvar .=  "<option value=\"mas\">Masai</option>";
                    break;
                case "may":
                    $outvar .=  "<option value=\"may\">Malay</option>";
                    break;
                case "mdf":
                    $outvar .=  "<option value=\"mdf\">Moksha</option>";
                    break;
                case "mdr":
                    $outvar .=  "<option value=\"mdr\">Mandar</option>";
                    break;
                case "men":
                    $outvar .=  "<option value=\"men\">Mende</option>";
                    break;
                case "mga":
                    $outvar .=  "<option value=\"mga\">Irish, Middle (900-1200)</option>";
                    break;
                case "mic":
                    $outvar .=  "<option value=\"mic\">Mi'kmaq; Micmac</option>";
                    break;
                case "min":
                    $outvar .=  "<option value=\"min\">Minangkabau</option>";
                    break;
                case "mis":
                    $outvar .=  "<option value=\"mis\">Uncoded languages</option>";
                    break;
                case "mkh":
                    $outvar .=  "<option value=\"mkh\">Mon-Khmer languages</option>";
                    break;
                case "mlg":
                    $outvar .=  "<option value=\"mlg\">Malagasy</option>";
                    break;
                case "mlt":
                    $outvar .=  "<option value=\"mlt\">Maltese</option>";
                    break;
                case "mnc":
                    $outvar .=  "<option value=\"mnc\">Manchu</option>";
                    break;
                case "mni":
                    $outvar .=  "<option value=\"mni\">Manipuri</option>";
                    break;
                case "mno":
                    $outvar .=  "<option value=\"mno\">Manobo languages</option>";
                    break;
                case "moh":
                    $outvar .=  "<option value=\"moh\">Mohawk</option>";
                    break;
                case "mon":
                    $outvar .=  "<option value=\"mon\">Mongolian</option>";
                    break;
                case "mos":
                    $outvar .=  "<option value=\"mos\">Mossi</option>";
                    break;
                case "mul":
                    $outvar .=  "<option value=\"mul\">Multiple languages</option>";
                    break;
                case "mun":
                    $outvar .=  "<option value=\"mun\">Munda languages</option>";
                    break;
                case "mus":
                    $outvar .=  "<option value=\"mus\">Creek</option>";
                    break;
                case "mwl":
                    $outvar .=  "<option value=\"mwl\">Mirandese</option>";
                    break;
                case "mwr":
                    $outvar .=  "<option value=\"mwr\">Marwari</option>";
                    break;
                case "myn":
                    $outvar .=  "<option value=\"myn\">Mayan languages</option>";
                    break;
                case "myv":
                    $outvar .=  "<option value=\"myv\">Erzya</option>";
                    break;
                case "nah":
                    $outvar .=  "<option value=\"nah\">Nahuatl languages</option>";
                    break;
                case "nai":
                    $outvar .=  "<option value=\"nai\">North American Indian languages</option>";
                    break;
                case "nap":
                    $outvar .=  "<option value=\"nap\">Neapolitan</option>";
                    break;
                case "nau":
                    $outvar .=  "<option value=\"nau\">Nauru</option>";
                    break;
                case "nav":
                    $outvar .=  "<option value=\"nav\">Navajo; Navaho</option>";
                    break;
                case "nbl":
                    $outvar .=  "<option value=\"nbl\">Ndebele, South; South Ndebele</option>";
                    break;
                case "nde":
                    $outvar .=  "<option value=\"nde\">Ndebele, North; North Ndebele</option>";
                    break;
                case "ndo":
                    $outvar .=  "<option value=\"ndo\">Ndonga</option>";
                    break;
                case "nds":
                    $outvar .=  "<option value=\"nds\">Low German; Low Saxon; German, Low; Saxon, Low</option>";
                    break;
                case "nep":
                    $outvar .=  "<option value=\"nep\">Nepali</option>";
                    break;
                case "new":
                    $outvar .=  "<option value=\"new\">Nepal Bhasa; Newari</option>";
                    break;
                case "nia":
                    $outvar .=  "<option value=\"nia\">Nias</option>";
                    break;
                case "nic":
                    $outvar .=  "<option value=\"nic\">Niger-Kordofanian languages</option>";
                    break;
                case "niu":
                    $outvar .=  "<option value=\"niu\">Niuean</option>";
                    break;
                case "nno":
                    $outvar .=  "<option value=\"nno\">Norwegian Nynorsk; Nynorsk, Norwegian</option>";
                    break;
                case "nob":
                    $outvar .=  "<option value=\"nob\">Bokmål, Norwegian; Norwegian Bokmål</option>";
                    break;
                case "nog":
                    $outvar .=  "<option value=\"nog\">Nogai</option>";
                    break;
                case "non":
                    $outvar .=  "<option value=\"non\">Norse, Old</option>";
                    break;
                case "nor":
                    $outvar .=  "<option value=\"nor\">Norwegian</option>";
                    break;
                case "nqo":
                    $outvar .=  "<option value=\"nqo\">N'Ko</option>";
                    break;
                case "nso":
                    $outvar .=  "<option value=\"nso\">Pedi; Sepedi; Northern Sotho</option>";
                    break;
                case "nub":
                    $outvar .=  "<option value=\"nub\">Nubian languages</option>";
                    break;
                case "nwc":
                    $outvar .=  "<option value=\"nwc\">Classical Newari; Old Newari; Classical Nepal Bhasa</option>";
                    break;
                case "nya":
                    $outvar .=  "<option value=\"nya\">Chichewa; Chewa;  Nyanja</option>";
                    break;
                case "nym":
                    $outvar .=  "<option value=\"nym\">Nyamwezi</option>";
                    break;
                case "nyn":
                    $outvar .=  "<option value=\"nyn\">Nyankole</option>";
                    break;
                case "nyo":
                    $outvar .=  "<option value=\"nyo\">Nyoro</option>";
                    break;
                case "nzi":
                    $outvar .=  "<option value=\"nzi\">Nzima</option>";
                    break;
                case "oci":
                    $outvar .=  "<option value=\"oci\">Occitan (post 1500)</option>";
                    break;
                case "oji":
                    $outvar .=  "<option value=\"oji\">Ojibwa</option>";
                    break;
                case "ori":
                    $outvar .=  "<option value=\"ori\">Oriya</option>";
                    break;
                case "orm":
                    $outvar .=  "<option value=\"orm\">Oromo</option>";
                    break;
                case "osa":
                    $outvar .=  "<option value=\"osa\">Osage</option>";
                    break;
                case "oss":
                    $outvar .=  "<option value=\"oss\">Ossetian; Ossetic</option>";
                    break;
                case "ota":
                    $outvar .=  "<option value=\"ota\">Turkish, Ottoman (1500-1928)</option>";
                    break;
                case "oto":
                    $outvar .=  "<option value=\"oto\">Otomian languages</option>";
                    break;
                case "paa":
                    $outvar .=  "<option value=\"paa\">Papuan languages</option>";
                    break;
                case "pag":
                    $outvar .=  "<option value=\"pag\">Pangasinan</option>";
                    break;
                case "pal":
                    $outvar .=  "<option value=\"pal\">Pahlavi</option>";
                    break;
                case "pam":
                    $outvar .=  "<option value=\"pam\">Pampanga; Kapampangan</option>";
                    break;
                case "pan":
                    $outvar .=  "<option value=\"pan\">Panjabi; Punjabi</option>";
                    break;
                case "pap":
                    $outvar .=  "<option value=\"pap\">Papiamento</option>";
                    break;
                case "pau":
                    $outvar .=  "<option value=\"pau\">Palauan</option>";
                    break;
                case "peo":
                    $outvar .=  "<option value=\"peo\">Persian, Old (ca.600-400 B.C.)</option>";
                    break;
                case "per":
                    $outvar .=  "<option value=\"per\">Persian</option>";
                    break;
                case "phi":
                    $outvar .=  "<option value=\"phi\">Philippine languages</option>";
                    break;
                case "phn":
                    $outvar .=  "<option value=\"phn\">Phoenician</option>";
                    break;
                case "pli":
                    $outvar .=  "<option value=\"pli\">Pali</option>";
                    break;
                case "pol":
                    $outvar .=  "<option value=\"pol\">Polish</option>";
                    break;
                case "pon":
                    $outvar .=  "<option value=\"pon\">Pohnpeian</option>";
                    break;
                case "por":
                    $outvar .=  "<option value=\"por\">Portuguese</option>";
                    break;
                case "pra":
                    $outvar .=  "<option value=\"pra\">Prakrit languages</option>";
                    break;
                case "pro":
                    $outvar .=  "<option value=\"pro\">Provençal, Old (to 1500); Occitan, Old (to 1500)</option>";
                    break;
                case "pus":
                    $outvar .=  "<option value=\"pus\">Pushto; Pashto</option>";
                    break;
                case "que":
                    $outvar .=  "<option value=\"que\">Quechua</option>";
                    break;
                case "raj":
                    $outvar .=  "<option value=\"raj\">Rajasthani</option>";
                    break;
                case "rap":
                    $outvar .=  "<option value=\"rap\">Rapanui</option>";
                    break;
                case "rar":
                    $outvar .=  "<option value=\"rar\">Rarotongan; Cook Islands Maori</option>";
                    break;
                case "roa":
                    $outvar .=  "<option value=\"roa\">Romance languages</option>";
                    break;
                case "roh":
                    $outvar .=  "<option value=\"roh\">Romansh</option>";
                    break;
                case "rom":
                    $outvar .=  "<option value=\"rom\">Romany</option>";
                    break;
                case "rum":
                    $outvar .=  "<option value=\"rum\">Romanian; Moldavian; Moldovan</option>";
                    break;
                case "run":
                    $outvar .=  "<option value=\"run\">Rundi</option>";
                    break;
                case "rup":
                    $outvar .=  "<option value=\"rup\">Aromanian; Arumanian; Macedo-Romanian</option>";
                    break;
                case "rus":
                    $outvar .=  "<option value=\"rus\">Russian</option>";
                    break;
                case "sad":
                    $outvar .=  "<option value=\"sad\">Sandawe</option>";
                    break;
                case "sag":
                    $outvar .=  "<option value=\"sag\">Sango</option>";
                    break;
                case "sah":
                    $outvar .=  "<option value=\"sah\">Yakut</option>";
                    break;
                case "sai":
                    $outvar .=  "<option value=\"sai\">South American Indian languages</option>";
                    break;
                case "sal":
                    $outvar .=  "<option value=\"sal\">Salishan languages</option>";
                    break;
                case "sam":
                    $outvar .=  "<option value=\"sam\">Samaritan Aramaic</option>";
                    break;
                case "san":
                    $outvar .=  "<option value=\"san\">Sanskrit</option>";
                    break;
                case "sas":
                    $outvar .=  "<option value=\"sas\">Sasak</option>";
                    break;
                case "sat":
                    $outvar .=  "<option value=\"sat\">Santali</option>";
                    break;
                case "scn":
                    $outvar .=  "<option value=\"scn\">Sicilian</option>";
                    break;
                case "sco":
                    $outvar .=  "<option value=\"sco\">Scots</option>";
                    break;
                case "sel":
                    $outvar .=  "<option value=\"sel\">Selkup</option>";
                    break;
                case "sem":
                    $outvar .=  "<option value=\"sem\">Semitic languages</option>";
                    break;
                case "sga":
                    $outvar .=  "<option value=\"sga\">Irish, Old (to 900)</option>";
                    break;
                case "sgn":
                    $outvar .=  "<option value=\"sgn\">Sign Languages</option>";
                    break;
                case "shn":
                    $outvar .=  "<option value=\"shn\">Shan</option>";
                    break;
                case "sid":
                    $outvar .=  "<option value=\"sid\">Sidamo</option>";
                    break;
                case "sin":
                    $outvar .=  "<option value=\"sin\">Sinhala; Sinhalese</option>";
                    break;
                case "sio":
                    $outvar .=  "<option value=\"sio\">Siouan languages</option>";
                    break;
                case "sit":
                    $outvar .=  "<option value=\"sit\">Sino-Tibetan languages</option>";
                    break;
                case "sla":
                    $outvar .=  "<option value=\"sla\">Slavic languages</option>";
                    break;
                case "slo":
                    $outvar .=  "<option value=\"slo\">Slovak</option>";
                    break;
                case "slv":
                    $outvar .=  "<option value=\"slv\">Slovenian</option>";
                    break;
                case "sma":
                    $outvar .=  "<option value=\"sma\">Southern Sami</option>";
                    break;
                case "sme":
                    $outvar .=  "<option value=\"sme\">Northern Sami</option>";
                    break;
                case "smi":
                    $outvar .=  "<option value=\"smi\">Sami languages</option>";
                    break;
                case "smj":
                    $outvar .=  "<option value=\"smj\">Lule Sami</option>";
                    break;
                case "smn":
                    $outvar .=  "<option value=\"smn\">Inari Sami</option>";
                    break;
                case "smo":
                    $outvar .=  "<option value=\"smo\">Samoan</option>";
                    break;
                case "sms":
                    $outvar .=  "<option value=\"sms\">Skolt Sami</option>";
                    break;
                case "sna":
                    $outvar .=  "<option value=\"sna\">Shona</option>";
                    break;
                case "snd":
                    $outvar .=  "<option value=\"snd\">Sindhi</option>";
                    break;
                case "snk":
                    $outvar .=  "<option value=\"snk\">Soninke</option>";
                    break;
                case "sog":
                    $outvar .=  "<option value=\"sog\">Sogdian</option>";
                    break;
                case "som":
                    $outvar .=  "<option value=\"som\">Somali</option>";
                    break;
                case "son":
                    $outvar .=  "<option value=\"son\">Songhai languages</option>";
                    break;
                case "sot":
                    $outvar .=  "<option value=\"sot\">Sotho, Southern</option>";
                    break;
                case "spa":
                    $outvar .=  "<option value=\"spa\">Spanish; Castilian</option>";
                    break;
                case "srd":
                    $outvar .=  "<option value=\"srd\">Sardinian</option>";
                    break;
                case "srn":
                    $outvar .=  "<option value=\"srn\">Sranan Tongo</option>";
                    break;
                case "srp":
                    $outvar .=  "<option value=\"srp\">Serbian</option>";
                    break;
                case "srr":
                    $outvar .=  "<option value=\"srr\">Serer</option>";
                    break;
                case "ssa":
                    $outvar .=  "<option value=\"ssa\">Nilo-Saharan languages</option>";
                    break;
                case "ssw":
                    $outvar .=  "<option value=\"ssw\">Swati</option>";
                    break;
                case "suk":
                    $outvar .=  "<option value=\"suk\">Sukuma</option>";
                    break;
                case "sun":
                    $outvar .=  "<option value=\"sun\">Sundanese</option>";
                    break;
                case "sus":
                    $outvar .=  "<option value=\"sus\">Susu</option>";
                    break;
                case "sux":
                    $outvar .=  "<option value=\"sux\">Sumerian</option>";
                    break;
                case "swa":
                    $outvar .=  "<option value=\"swa\">Swahili</option>";
                    break;
                case "swe":
                    $outvar .=  "<option value=\"swe\">Swedish</option>";
                    break;
                case "syc":
                    $outvar .=  "<option value=\"syc\">Classical Syriac</option>";
                    break;
                case "syr":
                    $outvar .=  "<option value=\"syr\">Syriac</option>";
                    break;
                case "tah":
                    $outvar .=  "<option value=\"tah\">Tahitian</option>";
                    break;
                case "tai":
                    $outvar .=  "<option value=\"tai\">Tai languages</option>";
                    break;
                case "tam":
                    $outvar .=  "<option value=\"tam\">Tamil</option>";
                    break;
                case "tat":
                    $outvar .=  "<option value=\"tat\">Tatar</option>";
                    break;
                case "tel":
                    $outvar .=  "<option value=\"tel\">Telugu</option>";
                    break;
                case "tem":
                    $outvar .=  "<option value=\"tem\">Timne</option>";
                    break;
                case "ter":
                    $outvar .=  "<option value=\"ter\">Tereno</option>";
                    break;
                case "tet":
                    $outvar .=  "<option value=\"tet\">Tetum</option>";
                    break;
                case "tgk":
                    $outvar .=  "<option value=\"tgk\">Tajik</option>";
                    break;
                case "tgl":
                    $outvar .=  "<option value=\"tgl\">Tagalog</option>";
                    break;
                case "tha":
                    $outvar .=  "<option value=\"tha\">Thai</option>";
                    break;
                case "tib":
                    $outvar .=  "<option value=\"tib\">Tibetan</option>";
                    break;
                case "tig":
                    $outvar .=  "<option value=\"tig\">Tigre</option>";
                    break;
                case "tir":
                    $outvar .=  "<option value=\"tir\">Tigrinya</option>";
                    break;
                case "tiv":
                    $outvar .=  "<option value=\"tiv\">Tiv</option>";
                    break;
                case "tkl":
                    $outvar .=  "<option value=\"tkl\">Tokelau</option>";
                    break;
                case "tlh":
                    $outvar .=  "<option value=\"tlh\">Klingon; tlhIngan-Hol</option>";
                    break;
                case "tli":
                    $outvar .=  "<option value=\"tli\">Tlingit</option>";
                    break;
                case "tmh":
                    $outvar .=  "<option value=\"tmh\">Tamashek</option>";
                    break;
                case "tog":
                    $outvar .=  "<option value=\"tog\">Tonga (Nyasa)</option>";
                    break;
                case "ton":
                    $outvar .=  "<option value=\"ton\">Tonga (Tonga Islands)</option>";
                    break;
                case "tpi":
                    $outvar .=  "<option value=\"tpi\">Tok Pisin</option>";
                    break;
                case "tsi":
                    $outvar .=  "<option value=\"tsi\">Tsimshian</option>";
                    break;
                case "tsn":
                    $outvar .=  "<option value=\"tsn\">Tswana</option>";
                    break;
                case "tso":
                    $outvar .=  "<option value=\"tso\">Tsonga</option>";
                    break;
                case "tuk":
                    $outvar .=  "<option value=\"tuk\">Turkmen</option>";
                    break;
                case "tum":
                    $outvar .=  "<option value=\"tum\">Tumbuka</option>";
                    break;
                case "tup":
                    $outvar .=  "<option value=\"tup\">Tupi languages</option>";
                    break;
                case "tur":
                    $outvar .=  "<option value=\"tur\">Turkish</option>";
                    break;
                case "tut":
                    $outvar .=  "<option value=\"tut\">Altaic languages</option>";
                    break;
                case "tvl":
                    $outvar .=  "<option value=\"tvl\">Tuvalu</option>";
                    break;
                case "twi":
                    $outvar .=  "<option value=\"twi\">Twi</option>";
                    break;
                case "tyv":
                    $outvar .=  "<option value=\"tyv\">Tuvinian</option>";
                    break;
                case "udm":
                    $outvar .=  "<option value=\"udm\">Udmurt</option>";
                    break;
                case "uga":
                    $outvar .=  "<option value=\"uga\">Ugaritic</option>";
                    break;
                case "uig":
                    $outvar .=  "<option value=\"uig\">Uighur; Uyghur</option>";
                    break;
                case "ukr":
                    $outvar .=  "<option value=\"ukr\">Ukrainian</option>";
                    break;
                case "umb":
                    $outvar .=  "<option value=\"umb\">Umbundu</option>";
                    break;
                case "und":
                    $outvar .=  "<option value=\"und\">Undetermined</option>";
                    break;
                case "urd":
                    $outvar .=  "<option value=\"urd\">Urdu</option>";
                    break;
                case "uzb":
                    $outvar .=  "<option value=\"uzb\">Uzbek</option>";
                    break;
                case "vai":
                    $outvar .=  "<option value=\"vai\">Vai</option>";
                    break;
                case "ven":
                    $outvar .=  "<option value=\"ven\">Venda</option>";
                    break;
                case "vie":
                    $outvar .=  "<option value=\"vie\">Vietnamese</option>";
                    break;
                case "vol":
                    $outvar .=  "<option value=\"vol\">Volapük</option>";
                    break;
                case "vot":
                    $outvar .=  "<option value=\"vot\">Votic</option>";
                    break;
                case "wak":
                    $outvar .=  "<option value=\"wak\">Wakashan languages</option>";
                    break;
                case "wal":
                    $outvar .=  "<option value=\"wal\">Wolaitta; Wolaytta</option>";
                    break;
                case "war":
                    $outvar .=  "<option value=\"war\">Waray</option>";
                    break;
                case "was":
                    $outvar .=  "<option value=\"was\">Washo</option>";
                    break;
                case "wel":
                    $outvar .=  "<option value=\"wel\">Welsh</option>";
                    break;
                case "wen":
                    $outvar .=  "<option value=\"wen\">Sorbian languages</option>";
                    break;
                case "wln":
                    $outvar .=  "<option value=\"wln\">Walloon</option>";
                    break;
                case "wol":
                    $outvar .=  "<option value=\"wol\">Wolof</option>";
                    break;
                case "xal":
                    $outvar .=  "<option value=\"xal\">Kalmyk; Oirat</option>";
                    break;
                case "xho":
                    $outvar .=  "<option value=\"xho\">Xhosa</option>";
                    break;
                case "yao":
                    $outvar .=  "<option value=\"yao\">Yao</option>";
                    break;
                case "yap":
                    $outvar .=  "<option value=\"yap\">Yapese</option>";
                    break;
                case "yid":
                    $outvar .=  "<option value=\"yid\">Yiddish</option>";
                    break;
                case "yor":
                    $outvar .=  "<option value=\"yor\">Yoruba</option>";
                    break;
                case "ypk":
                    $outvar .=  "<option value=\"ypk\">Yupik languages</option>";
                    break;
                case "zap":
                    $outvar .=  "<option value=\"zap\">Zapotec</option>";
                    break;
                case "zbl":
                    $outvar .=  "<option value=\"zbl\">Blissymbols; Blissymbolics; Bliss</option>";
                    break;
                case "zen":
                    $outvar .=  "<option value=\"zen\">Zenaga</option>";
                    break;
                case "zgh":
                    $outvar .=  "<option value=\"zgh\">Standard Moroccan Tamazight</option>";
                    break;
                case "zha":
                    $outvar .=  "<option value=\"zha\">Zhuang; Chuang</option>";
                    break;
                case "znd":
                    $outvar .=  "<option value=\"znd\">Zande languages</option>";
                    break;
                case "zul":
                    $outvar .=  "<option value=\"zul\">Zulu</option>";
                    break;
                case "zun":
                    $outvar .=  "<option value=\"zun\">Zuni</option>";
                    break;
                case "zxx":
                    $outvar .=  "<option value=\"zxx\">No linguistic content;
                    break; Not applicable</option>";
                    break;
                case "zza":
                    $outvar .=  "<option value=\"zza\">Zaza; Dimili; Dimli; Kirdki; Kirmanjki; Zazaki</option>";
                    break;
            }
        }
        return $outvar;
    }
}
