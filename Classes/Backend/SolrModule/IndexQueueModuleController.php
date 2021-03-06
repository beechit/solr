<?php
namespace ApacheSolrForTypo3\Solr\Backend\SolrModule;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Ingo Renner <ingo@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Index Queue Module
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class IndexQueueModuleController extends AbstractModuleController {

	/**
	 * Module name, used to identify a module f.e. in URL parameters.
	 *
	 * @var string
	 */
	protected $moduleName = 'IndexQueue';

	/**
	 * Module title, shows up in the module menu.
	 *
	 * @var string
	 */
	protected $moduleTitle = 'Index Queue';


	/**
	 * Lists the available indexing configurations
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('indexQueueInitializationSelector', $this->getIndexQueueInitializationSelector());
		$this->view->assign('indexqueue_stats', json_encode($this->getIndexQueueStats()));
		$this->view->assign('indexqueue_errors', $this->getIndexQueueErrors());
	}

	/**
	 * Initializes the Index Queue for selected indexing configurations
	 *
	 * @return void
	 */
	public function initializeIndexQueueAction() {
		$initializedIndexingConfigurations = array();

		$itemIndexQueue                     = GeneralUtility::makeInstance('Tx_Solr_IndexQueue_Queue');
		$indexingConfigurationsToInitialize = GeneralUtility::_POST('tx_solr-index-queue-initialization');
		if (!empty($indexingConfigurationsToInitialize)) {
				// initialize selected indexing configuration
			foreach ($indexingConfigurationsToInitialize as $indexingConfigurationName) {
				$initializedIndexingConfiguration = $itemIndexQueue->initialize(
					$this->site,
					$indexingConfigurationName
				);

					// track initialized indexing configurations for the flash message
				$initializedIndexingConfigurations = array_merge(
					$initializedIndexingConfigurations,
					$initializedIndexingConfiguration
				);
			}
		} else {
			$this->addFlashMessage(
				'No indexing configurations selected.',
				'Index Queue not initialized',
				FlashMessage::WARNING
			);
		}

		$messagesForConfigurations = array();
		foreach (array_keys($initializedIndexingConfigurations) as $indexingConfigurationName) {
			$itemCount = $itemIndexQueue->getItemsCountBySite($this->site, $indexingConfigurationName);
			if (!is_int($itemCount)) {
				$itemCount = 0;
			}
			$messagesForConfigurations[] = $indexingConfigurationName . ' (' . $itemCount . ' records)';
		}

		if (!empty($initializedIndexingConfigurations)) {
			$this->addFlashMessage(
				'Initialized indexing configurations: ' . implode(', ', $messagesForConfigurations),
				'Index Queue initialized',
				FlashMessage::OK
			);
		}

		$this->forward('index');
	}

	/**
	 * Renders a field to select which indexing configurations to initialize.
	 *
	 * Uses TCEforms.
	 *
	 *  @return string Markup for the select field
	 */
	protected function getIndexQueueInitializationSelector() {
		$selector = GeneralUtility::makeInstance('Tx_Solr_Backend_IndexingConfigurationSelectorField', $this->site);
		$selector->setFormElementName('tx_solr-index-queue-initialization');

		return $selector->render();
	}

	/**
	 * Extracts the number of pending, indexed and erroneous items from the
	 * index queue.
	 * @return array
	 */
	protected function getIndexQueueStats() {
		$indexQueueStats = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'indexed < changed as pending,'
				. '(errors not like "") as erroneous,'
				. 'COUNT(*) as count',
			'tx_solr_indexqueue_item',
			'',
			'pending, erroneous'
		);

		$stats = array();
		foreach($indexQueueStats as $row) {
			if($row['erroneous'] == 1) {
				$stats['erroneous'] = $row['count'];
			} elseif($row['pending'] == 1) {
				$stats['pending'] = $row['count'];
			} else {
				$stats['indexed'] = $row['count'];
			}
		}

		return $stats;
	}

	/**
	 * @return array Index queue with an associated error
	 */
	protected function getIndexQueueErrors() {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, item_type, item_uid, errors',
			'tx_solr_indexqueue_item',
			'errors NOT LIKE ""'
		);
	}
}

?>
