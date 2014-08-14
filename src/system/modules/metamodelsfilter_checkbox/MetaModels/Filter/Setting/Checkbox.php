<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage FilterCheckbox
 * @author     Christian de la Haye <service@delahaye.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\FrontendIntegration\FrontendFilterOptions;

/**
 * Filter "checkbox" for FE-filtering, based on filters by the meta models team.
 *
 * @package    MetaModels
 * @subpackage FilterCheckbox
 * @author     Christian de la Haye <service@delahaye.de>
 */
class Checkbox extends SimpleLookup
{
	/**
	 * {@inheritdoc}
	 */
	public function prepareRules(IFilter $objFilter, $arrFilterUrl)
	{
		$objMetaModel = $this->getMetaModel();
		$arrLanguages = ($objMetaModel->isTranslated() && $this->get('all_langs'))
			? $objMetaModel->getAvailableLanguages()
			: array($objMetaModel->getActiveLanguage());
		$objAttribute = $objMetaModel->getAttributeById($this->get('attr_id'));

		$strParamName = $this->getParamName();

		// If is a checkbox defined as "no", 1 has to become -1 like with radio fields.
		$arrFilterUrl[$strParamName] =
			($arrFilterUrl[$strParamName] == '1' && $this->get('ynmode') == 'no' ? '-1' : $arrFilterUrl[$strParamName]);

		if ($objAttribute && $strParamName && $arrFilterUrl[$strParamName])
		{
			// Param -1 has to be '' meaning 'really empty'.
			$arrFilterUrl[$strParamName] = ($arrFilterUrl[$strParamName] == '-1' ? '' : $arrFilterUrl[$strParamName]);

			$objFilterRule = new SearchAttribute($objAttribute, $arrFilterUrl[$strParamName], $arrLanguages);
			$objFilter->addFilterRule($objFilterRule);
			return;
		}

		$objFilter->addFilterRule(new StaticIdList(null));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParameterDCA()
	{
		// If defined as static, return nothing as not to be manipulated via editors.
		if (!$this->get('predef_param'))
		{
			return array();
		}

		$objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));

		return array(
			$this->getParamName() => array
			(
				'label'     => array(
					($this->get('label') ? $this->get('label') : $objAttribute->getName()),
					'GET: '.$this->get('urlparam')
				),
				'inputType' => 'checkbox',
			)
		);
	}

	/**
	 * Overrides the parent implementation to always return true, as this setting is always optional.
	 *
	 * @return bool true if all matches shall be returned, false otherwise.
	 */
	public function allowEmpty()
	{
		return true;
	}

	/**
	 * Overrides the parent implementation to always return true, as this setting is always available for FE filtering.
	 *
	 * @return bool true as this setting is always available.
	 */
	public function enableFEFilterWidget()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParameterFilterWidgets(
		$arrIds,
		$arrFilterUrl,
		$arrJumpTo,
		FrontendFilterOptions $objFrontendFilterOptions
	)
	{

		$objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));

		$arrWidget = array
		(
			'label'     => ($this->get('ynmode') == 'radio' || $this->get('ynfield') ?
				array(
					($this->get('label') ?: $objAttribute->getName()),
					($this->get('ynmode') == 'yes'
						? $GLOBALS['TL_LANG']['MSC']['yes']
						: $GLOBALS['TL_LANG']['MSC']['no']
					)
				)
				:
				array(
					($this->get('label') ?: $objAttribute->getName()),
					($this->get('ynmode') == 'no'
						? sprintf(
							$GLOBALS['TL_LANG']['MSC']['extended_no'],
							($this->get('label') ?: $objAttribute->getName())
						)
						: ($this->get('label') ?: $objAttribute->getName())
					)
				)
			),
			'inputType' => ($this->get('ynmode') == 'radio' ?: 'checkbox'),
			'eval'      => array(
				'colname'            => $objAttribute->getColname(),
				'urlparam'           => $this->getParamName(),
				'ynmode'             => $this->get('ynmode'),
				'ynfield'            => $this->get('ynfield'),
				'template'           => $this->get('template'),
				'includeBlankOption' => ($this->get('ynmode') == 'radio' && $this->get('blankoption') ? true : false),
			)
		);

		if ($this->get('ynmode') == 'radio')
		{
			$arrWidget['options']   = array
			(
				0    => '-1',
				1    => '1'
			);
			$arrWidget['reference'] = array
			(
				'-1' => $GLOBALS['TL_LANG']['MSC']['no'],
				'1'  => $GLOBALS['TL_LANG']['MSC']['yes']
			);
		}

		$GLOBALS['MM_FILTER_PARAMS'][] = $this->getParamName();

		return array
		(
			$this->getParamName() => $this->prepareFrontendFilterWidget(
				$arrWidget,
				$arrFilterUrl,
				$arrJumpTo,
				$objFrontendFilterOptions
			)
		);
	}
}