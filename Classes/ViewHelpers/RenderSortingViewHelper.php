<?php
/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Renders the sorting.
 *
 * @version $Id: GravatarViewHelper.php 1356 2009-09-23 21:22:38Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Solr_ViewHelpers_RenderSortingViewHelper extends Tx_Solr_ViewHelpers_AbstractViewHelper {
	/**
	 * Render the gravatar image
	 *
	 * @param string $fieldName
	 * @return string The rendered image tag
	 */
	public function render($fieldName) {
		$query = $this->search->getQuery();
		if ($this->controllerContext->getRequest()->hasArgument('sort')) {
			$urlSortingParameter = $this->controllerContext->getRequest()->getArgument('sort');
			list($currentSortByField, $currentSortDirection) = explode(' ', $urlSortingParameter);
		}

		$sortDirection = $this->settings['search']['sorting']['defaultOrder'];
		$sortIndicator = $sortDirection;
		$sortParameter = $fieldName . ' ' . $sortDirection;

		// toggle sorting direction for the current sorting field
		if ($currentSortByField == $fieldName) {
			switch ($currentSortDirection) {
				case 'asc':
					$sortDirection = 'desc';
					$sortIndicator = 'asc';
					break;
				case 'desc':
					$sortDirection = 'asc';
					$sortIndicator = 'desc';
					break;
			}

			$sortParameter = $fieldName . ' ' . $sortDirection;
		}
/*					// special case relevancy: just reset the search to normal behavior
		if ($fieldName == 'relevancy') {
			$temp['link'] = $query->getQueryLink(
				'###LLL:' . $configuredSortingFields[$fieldName . '.']['label'] . '###',
				array('sort' => null)
			);
			unset($temp['direction'], $temp['indicator']);
		}

		$sortingFields[] = $temp;
*/

		$url = $query->getQueryUrl(array('sort' => $sortParameter));
		$this->templateVariableContainer->add('sortUrl', $url);
		$this->templateVariableContainer->add('sortDirection', $sortDirection);
		$this->templateVariableContainer->add('sortIndicator', $sortIndicator);
		$output = $this->renderChildren();
		$this->templateVariableContainer->remove('sortUrl');
		$this->templateVariableContainer->remove('sortDirection');
		$this->templateVariableContainer->remove('sortIndicator');

		return $output;
	}
}


?>