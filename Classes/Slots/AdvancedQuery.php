<?php
namespace Slub\SlubFindExtend\Slots;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Slub\SlubFindExtend\Backend\Solr\SearchHandler;
use Solarium\QueryType\Select\Query\Query;


/**
 * Slot implementation before the
 *
 * @category    Slots
 * @package     TYPO3
 */
class AdvancedQuery {

    /**
     * @var \Slub\SlubFindExtend\Services\StopWordService
     * @inject
     */
    protected $stopWordService;

    /**
     * Contains the settings of the current extension
     *
     * @var array
     * @api
     */
    protected $settings;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
        $this->configurationManager = $configurationManager;
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
    }

    /**
     * Special handling for pure numeric queries
     *
     * @param $parameter
     */
    private function handleNumeric($parameter) {

        if(is_numeric($parameter)) {
            $parameter = sprintf($this->settings['queryModifier']['numeric'], $parameter);
        }

        return $parameter;
    }

    /**
     * @param string $querystring
     * @param string $originalQuerystring
     * @return string
     */
    public function handlePhraseMatch($querystring, $originalQuerystring) {

        if(preg_match('/^".*"$/', trim($originalQuerystring))) { return $querystring; }

        return $querystring . ' "'.$originalQuerystring.'"';

    }

    /**
     * Slot to build the advanced query
     *
     * @param Query &$query
     * @param array $arguments request arguments
     */
    public function build(&$query, $arguments) {

        $originalQueryParameter = $queryParameter = is_array($arguments['q']['default']) ? $arguments['q']['default'][0] : $arguments['q']['default'];

        if(strlen($queryParameter) > 0) {

            if($this->settings['queryModifier']) {

                if($this->settings['queryModifier']['stopwords']) {
                    $queryParameter = $this->stopWordService->cleanQueryString($queryParameter);
                }

                if($this->settings['queryModifier']['numeric']) {
                    $queryParameter = $this->handleNumeric($queryParameter);
                }

                if($this->settings['queryModifier']['phraseMatch']) {
                    $queryParameter = $this->handlePhraseMatch($queryParameter, $originalQueryParameter);
                }

            }

            $settings = $this->settings['components'];

            $searchHandler = new SearchHandler($settings);

            $boostquery = $searchHandler->createBoostQueryString($queryParameter);

            $querystring = $searchHandler->createAdvancedQueryString($queryParameter);

            $query->setQuery($querystring);

            /** @var \Solarium\QueryType\Select\Query\Component\EdisMax $edismax */
            $edismax = $query->getEDisMax();

        }

        // Needs to be dicussed if activated or not
        //$edismax->setBoostQuery($boostquery);

        //$edismax->setBoostFunctions("ord(publishDateSort)^10");

        //$edismax->setBoostQuery('mega_collection:"Qucosa"^10.0');

        //$edismax->setBoostQuery('(mega_collection:"Verbunddaten SWB")^100.0 OR (mega_collection:"SLUB/Deutsche Fotothek")^0.01');



    }


}
