<?php
/**
 * PCI Search API Interface for VuFind
 *
 * PHP version 5
 *
 * Copyright (C) Andrew Nagy 2009.
 * Copyright (C) The National Library of Finland 2012-2013.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Kalle Pyykkönen <kalle.pyykkonen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.exlibrisgroup.org/display/PCIOI/X-Services
 */

require_once 'sys/ConfigArray.php';
require_once 'sys/SolrUtils.php';
require_once 'services/MyResearch/lib/Resource.php';

/**
 * PCI SOAP API Interface
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Kalle Pyykkönen <kalle.pyykkonen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.exlibrisgroup.org/display/PCIOI/X-Services
 */
class PCI
{
    /**
     * A boolean value determining whether to print debug information
     * @var bool
     */
     public $debug = false;

    /**
     * The URL of the PCI API WSDL
     * @var string
     */
    protected $wsdl;

    /**
     * PCI institution code
     * @var string
     */
    protected $institution;

    /**
     *
     * Configuration settings from web/conf/PCI.ini
     * @var array
     */
    protected $config;

    /**
     * Should boolean operators in the search string be treated as
     * case-insensitive (false), or must they be ALL UPPERCASE (true)?
     */
    protected $caseSensitiveBooleans = true;

    /**
     * Will we highlight text in responses?
     * @var bool
     */
    protected $highlight = true;

    /**
     * PCI Settings
     */
    protected $params = array();

    /**
     * Constructor
     *
     * Sets up the PCI API Client
     *
     * @access public
     */
    public function __construct()
    {
        global $configArray;

        if ($configArray['System']['debug']) {
            $this->debug = true;
        }

        $this->config = getExtraConfigArray('PCI');

        // Store preferred boolean behavior:
        if (isset($this->config['General']['case_sensitive_bools'])) {
            $this->caseSensitiveBooleans
                = $this->config['General']['case_sensitive_bools'];
        }

        // Store highlighting/snippet behavior:
        if (isset($this->config['General']['highlighting'])) {
            $this->highlight = $this->config['General']['highlighting'];
        }
        if (isset($this->config['General']['snippets'])) {
            $this->snippets = $this->config['General']['snippets'];
        }

        $this->params['wsdl'] = $this->config['General']['wsdl'];
        $this->params['institution'] = $this->config['General']['institution'];
        $this->params['onCampus'] = UserAccount::isAuthorized();
        $this->params['db'] = isset($this->config['General']['db']) ? $this->config['General']['db'] : null;


    }

    /**
     * Check if PCI is configured and available
     *
     * @return bool Whether PCI is available
     */
    public function available()
    {
        return isset($this->config['General']);
    }

    /**
     * Retrieves a document specified by the ID.
     *
     * @param string $id The document to retrieve from the PCI API
     *
     * @throws object    PEAR Error
     * @return string    The requested resource
     * @access public
     */
    public function getRecord($id)
    {
        // Check from database, this could be in a favorite list
        $resource = new Resource();
        $resource->record_id = $id;
        $resource->source = 'PCI';
        if ($resource->find(true)) {
            return unserialize($resource->data);
        } else {
            $id = preg_replace('/^pci\./', '', $id);
            $params = array(array('group' => array(array('lookfor' => addcslashes($id, '()\\'), 'field' => 'rid'))));
            $result = $this->query($params);
            return $result['response']['docs'][0];
        }
    }

    /**
     * Build Query string from search parameters
     *
     * @param array $searchTerms An array of search parameters
     * @param array $filterList  An array of filters
     * @param int   $startRec    First record in recordset
     * @param array $limit       Number of records in recordset
     * @param array $sortBy      Sort by column
     *
     * @return string            The query XML
     * @access protected
     */
    protected function buildQuery($searchTerms, $filterList, $startRec, $limit, $sortBy)
    {
        // 'includeValue' changed to 'Value' due to searches not working otherwise
        $facetXml = <<<EOE
        <QueryTerm>
            <IndexField>%s</IndexField>
            <PrecisionOperator>exact</PrecisionOperator>
            <Value>%s</Value>
        </QueryTerm>
EOE;

        $filterTerms = '';
        foreach ($filterList as $key => $values) {
            $includeValues = '';
            foreach ($values as $value) {
                if ($key == 'creationdate') {
                    // Do we have a single value or range
                    if (strpos($value, 'TO') !== false) {
                        $value = $value;
                    } else {
                        $value = "[$value TO $value]";
                    }
                }
                $facetKey = 'facet_' . $key;
                $filterTerms .= sprintf($facetXml, htmlspecialchars($facetKey, ENT_COMPAT, 'UTF-8'), $this->u8ncr(htmlspecialchars($value, ENT_COMPAT, 'UTF-8')));
            }
        }

        $queryTermXml = <<<EOF
        <QueryTerm>
            <IndexField>%s</IndexField>
            <PrecisionOperator>%s</PrecisionOperator>
            <Value>%s</Value>
        </QueryTerm>
EOF;
        // handle queryterms
        $queryTerms = '';
        foreach ($searchTerms as $params) {
            // Advanced Search
            if (isset($params['group'])) {
                // Process each search group
                foreach ($params['group'] as $group) {
                    $queryTerms .= sprintf($queryTermXml, $group['field'], 'contains', $this->u8ncr(htmlspecialchars(addcslashes($group['lookfor'], '()\\'), ENT_COMPAT, 'UTF-8')));
                }
            }
            // Basic Search
            if (isset($params['lookfor']) && $params['lookfor'] != '') {
                $queryTerms .= sprintf($queryTermXml, 'any', 'contains', $this->u8ncr(htmlspecialchars(addcslashes($params['lookfor'], '()\\'), ENT_COMPAT, 'UTF-8')));
            }
        }

        $searchXml = <<<EOD
        <searchRequest xmlns="http://www.exlibris.com/primo/xsd/wsRequest"
               xmlns:uic="http://www.exlibris.com/primo/xsd/primoview/uicomponents">
           <PrimoSearchRequest xmlns="http://www.exlibris.com/primo/xsd/search/request">
        <RequestParams>
        <RequestParam key="pc_availability_ind">true</RequestParam>
        </RequestParams>
           <QueryTerms>
             <BoolOpeator>%s</BoolOpeator>
             %s
           </QueryTerms>
           <StartIndex>%d</StartIndex>
           <BulkSize>%d</BulkSize>
           <DidUMeanEnabled>true</DidUMeanEnabled>
           <HighlightingEnabled>true</HighlightingEnabled>
           <Languages>
             <Language>eng</Language>
           </Languages>
           <SortByList>
              <SortField>%s</SortField>
           </SortByList>
           <Locations>
             <uic:Location type="adaptor" value="primo_central_multiple_fe"/>
           </Locations>
          </PrimoSearchRequest>
             <institution>%s</institution>
             <onCampus>%s</onCampus>
          </searchRequest>
EOD;

        $boolOperator = isset($searchTerms[0]['group'][0]['bool']) ? $searchTerms[0]['group'][0]['bool'] : 'AND';
        $onCampus = $this->params['onCampus'] ? 'true' : 'false';
        $queryParameter = sprintf($searchXml, $boolOperator, $queryTerms . $filterTerms, $startRec, $limit, $sortBy, $this->params['institution'], $onCampus);
        return $queryParameter;
    }

    /**
     * Execute a search.
     *
     * @param array  $query      The search terms from the Search Object
     * @param array  $filterList The fields and values to filter results on
     * @param string $startRec   The record to start with
     * @param string $limit      The number of records to return
     * @param string $sortBy     The value to be used by for sorting
     *
     * @throws object            PEAR Error
     * @return array             An array of query results
     * @access public
     */
    public function query($query,
        $filterList = array(),
        $startRec = 1,
        $limit = 10,
        $sortBy = null
    ) {
        $queryStr = $this->buildQuery($query, $filterList, $startRec, $limit, $sortBy);
        if (!$queryStr) {
            PEAR::raiseError(new PEAR_Error('Search terms are required'));
        }
        $xmlResult = $this->soapCall($queryStr);
        $xml = simplexml_load_string($xmlResult);
        $xml->registerXPathNamespace('prim', 'http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');
        $xml->registerXPathNamespace('sear', 'http://www.exlibrisgroup.com/xsd/jaguar/search');
        $ds = $xml->xpath('//sear:DOCSET');
        $hits = isset($ds[0]) ? (int)$ds[0]->attributes()->TOTALHITS : 0;
        $records = array();
        foreach ($xml->xpath('//sear:DOC') as $doc) {
            $records[] = $this->process($doc);
        }
        // Get facets
        $facets = array();
        foreach ($xml->xpath('//sear:FACET') as $item) {
            $tag = (string) $item->attributes()->NAME;
            $values = array();
            foreach ($item->xpath('.//sear:FACET_VALUES') as $facetValue) {
                $key = (string) $facetValue->attributes()->KEY;
                $count = (string) $facetValue->attributes()->VALUE;
                $values[] = array('value'=> $key, 'count' => $count);
            }

            $facets[] = array('id' => $tag, 'tag' => $tag, 'values' => $values);
        }
        // Reorder facets by document count
        foreach ($facets as &$facet) {
            usort(
                $facet['values'],
                function ($a, $b) {
                    return $b['count'] - $a['count'];
                }
            );
        }
        return array('recordCount' => $hits, 'response' => array('numFound' => $hits, 'start' => 0, 'docs' => $records), 'facetFields' => $facets);
    }

    /**
     * Call PCI Web Services API
     *
     * @param array $searchString XML parameters as a string
     *
     * @return string XML returned from server
     * @access protected
     */
    protected function soapCall($searchString)
    {
        global $configArray;
        $conf = getExtraConfigArray("PCI");
        $wsdl = $conf['General']['wsdl'];
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true
        );
        $client = new SoapClient($wsdl, $options);
        try {
            $xml = $client->searchBrief($searchString);
        } catch (Exception $e) {
            PEAR::raiseError(new PEAR_Error($e->getMessage()));
        }

        return $xml;
    }

    /**
     * Perform normalization and analysis of PCI return value
     * (a single record)
     *
     * @param simplexml $item The xml record from PCI
     *
     * @return array The processed record array
     * @access protected
     */
    protected function process($item)
    {
        global $configArray;
        $primoRecord = $item->children('http://www.exlibrisgroup.com/xsd/primo/primo_nm_bib');
        $links = array();
        $backlink = '';
        foreach ((array) $primoRecord->PrimoNMBib->record->links->linktorsrc as $val) {
            $linkString = (string)$val;
            $ini = strripos($linkString, '$$U');
            if ($ini === false) {
                continue;
            }
            $ini +=3;
            $linkArray = explode('$$', substr($linkString, $ini));
            if ($linkArray[0]) {
                $links [] = $linkArray[0];
            }
        }

        // Back links sometimes contain non-valid XML and thus cannot
        // be treated like source links

        $backlinkString = (string)$primoRecord->PrimoNMBib->record->links->backlink;
        $ini = strripos($backlinkString, '$$U');

        if ($ini !== false) {

            $ini +=3;

            $backlinkArray = explode('$$', substr($backlinkString, $ini));

            if ($backlinkArray[0]) {

                $backlink = $backlinkArray[0];

            }
        }

        $openurl = '';
        if (isset($configArray['OpenURL']['url']) && $configArray['OpenURL']['url']) {
            // Parse the OpenURL and extract parameters
            $link = $item->xpath('.//sear:LINKS/sear:openurl');
            if (!$link) {
                $link = $item->xpath('.//sear:GETIT/@GetIt2');
                if (!$link || $link[0] == "") {
                    $link = $item->xpath('.//sear:GETIT/@GetIt1');
                }
            }
            if ($link && strpos($link[0], 'url_ver=Z39.88-2004') !== false) {
                $params = explode('&', substr($link[0], strpos($link[0], '?') + 1));
                $openurl = 'rfr_id=' . urlencode($configArray['OpenURL']['rfr_id']);
                foreach ($params as $param) {
                    if (substr($param, 0, 7) != 'rfr_id=') {
                        $openurl .= '&' . $param;
                    }
                }
            }
        }

        $fulltext = (string)$primoRecord->PrimoNMBib->record->delivery->fulltext;

        $title = $this->hiLite((string)$primoRecord->PrimoNMBib->record->display->title);

        $authors = explode(' ; ', (string) $primoRecord->PrimoNMBib->record->display->creator);
        foreach ($authors as &$author) {
            $author = $this->hiLite($author);
        }

        return array(
            'title' => $title,
            'author' => $authors,
            'AdditionalAuthors' => (array)$primoRecord->PrimoNMBib->record->search->creatorcontrib,
            'source' => (string)$primoRecord->PrimoNMBib->record->display->source,
            'publicationDate' => (string)$primoRecord->PrimoNMBib->record->search->creationdate,
            'publicationTitle' => (string)$primoRecord->PrimoNMBib->record->display->ispartof,
            'openUrl' => !empty($openurl) ? $openurl : null,
            'url' => $links,
            'backlink' => $backlink,
            'fulltext' => $fulltext,
            'fullrecord' => $item->asXML(),
            'id' => 'pci.' . (string)$primoRecord->PrimoNMBib->record->search->recordid,
            'format' => (string)$primoRecord->PrimoNMBib->record->display->type,
            'ISSN' => (string)$primoRecord->PrimoNMBib->record->search->issn,
            'language' => (string)$primoRecord->PrimoNMBib->record->facets->language,
            'subjectTerms' => (array)$primoRecord->PrimoNMBib->record->search->subject,
            'snippet' => $this->hiLite((string)$primoRecord->PrimoNMBib->record->display->description),
            'volume' => (string)$primoRecord->PrimoNMBib->record->addata->volume,
            'issue' => (string)$primoRecord->PrimoNMBib->record->addata->issue,
            'startPage' => '',
            'endPage' => ''
        );
    }

    /**
     * Fix highlighting to VuFind compatible mode
     *
     * @param string $str source
     *
     * @return string formatted string
     * @access protected
     */
    protected function hiLite($str)
    {
        return preg_replace('/<span class="searchword">(.*?)<\/span>/', '{{{{START_HILITE}}}}$1{{{{END_HILITE}}}}', $str);
    }

    /**
     * Primo may have trouble interpreting wide UTF-8 characters,
     * so encode them with numeric character references.
     *
     * @param string $str UTF-8 string
     *
     * @return string encoded string
     * @access protected
     */
    protected function u8ncr($str)
    {
        return mb_encode_numericentity(
            $str, array(0x0080,0xffff,0,0xffff), "UTF-8"
        );
    }
}
