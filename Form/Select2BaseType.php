<?php

/*
 * This file is part of the Stinger Soft Select2 Form Bundle.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\Select2FormBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 */
abstract class Select2BaseType extends AbstractType {

	/**
	 *
	 * @var integer
	 */
	public const SEARCH_METHOD_EXACT = 1;

	/**
	 *
	 * @var integer
	 */
	public const SEARCH_METHOD_AND = 2;

	/**
	 *
	 * @var integer
	 */
	public const SEARCH_METHOD_OR = 3;

	/**
	 *
	 * @deprecated
	 *
	 * @var integer
	 */
	public const MARKMATCH_DEFAULT = 1;

	/**
	 *
	 * @deprecated
	 *
	 * @var integer
	 */
	public const MARKMATCH_WHOLE = 2;

	/**
	 *
	 * @deprecated
	 *
	 * @var integer
	 */
	public const MARKMATCH_MULTIPLE = 3;

	/**
	 *
	 * @var string
	 */
	public const DATA_MAPPER_NOOP = 'StingerSoftSelect2.ajax.dataMapper.noop';

	/**
	 *
	 * @var string
	 */
	public const DATA_MAPPER_LABEL_TO_TEXT = 'StingerSoftSelect2.ajax.dataMapper.labelToText';

	/** @var RouterInterface */
	protected $router;

	/** @var TranslatorInterface */
	protected $translator;

	/** @var ManagerRegistry */
	protected $managerRegistry;

	public function __construct(RouterInterface $router, TranslatorInterface $translator, ManagerRegistry $managerRegistry) {
		$this->router = $router;
		$this->translator = $translator;
		$this->managerRegistry = $managerRegistry;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::buildForm()
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->setAttribute('searchMethod', $options['searchMethod']);
		$builder->setAttribute('maximumSelectionSize', $options['maximumSelectionSize']);

		// depreacted width = off
		$builder->setAttribute('width', $options['width'] !== 'off' ? $options['width'] : 'resolve');
		$builder->setAttribute('dropdownAutoWidth', $options['dropdownAutoWidth']);
		$builder->setAttribute('dropdownCssClass', $options['dropdownCssClass']);
		$builder->setAttribute('containerCssClass', $options['containerCssClass']);
		$builder->setAttribute('route', $options['route']);
		$builder->setAttribute('routeParams', $options['routeParams']);
		$builder->setAttribute('minimumInputLength', $options['minimumInputLength']);
		$builder->setAttribute('delay', $options['delay']);
		$builder->setAttribute('closeOnSelect', $options['closeOnSelect']);
		$builder->setAttribute('hideSelected', $options['hideSelected']);

		// deprecated formatResult
		if(isset($options['formatResult'])) {
			trigger_error('Please use the templateResult option to format the result', E_USER_DEPRECATED);
			$builder->setAttribute('templateResult', $options['formatResult']);
		}
		$builder->setAttribute('templateResult', $options['templateResult']);
		$builder->setAttribute('templateSelection', $options['templateSelection']);

		$builder->setAttribute('multiple', $options['multiple']);
		$builder->setAttribute('dataMapper', $options['dataMapper']);
		$builder->setAttribute('showTooltip', $options['showTooltip']);
		$builder->setAttribute('showSelectionTooltip', $options['showSelectionTooltip']);
		$builder->setAttribute('renderTitle', $options['renderTitle']);
		$builder->setAttribute('select2-placeholder', $options['select2-placeholder']);
		// if($options['translation_domain'] !== null) {
		// $builder->setAttribute('translation_domain', $options['translation_domain']);
		// }
		$builder->setAttribute('theme', $options['theme']);

		$builder->setAttribute('escapeMarkup', $options['escapeMarkup']);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::buildView()
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		parent::buildView($view, $form, $options);

		$translationDomain = null;
		if(isset($options['translation_domain'])) {
			$translationDomain = $options['translation_domain'];
		} else if(isset($view->vars['translation_domain'])) {
			$translationDomain = $view->vars['translation_domain'];
		}

		$view->vars['select2OptionsJavaScript'] = [];
		$view->vars['select2Options'] = [];
		switch($form->getConfig()->getAttribute('templateResult')) {
			case self::MARKMATCH_DEFAULT:
				trigger_error('MARKMATCH_DEFAULT is not supported anymore, because the select2 developers are morons', E_USER_DEPRECATED);
				break;
			case self::MARKMATCH_WHOLE:
				trigger_error('MARKMATCH_WHOLE is not supported anymore, because the select2 developers are morons', E_USER_DEPRECATED);
				break;
			case self::MARKMATCH_MULTIPLE:
				trigger_error('MARKMATCH_MULTIPLE is not supported anymore, because the select2 developers are morons', E_USER_DEPRECATED);
				break;
		}
		if(is_string($form->getConfig()->getAttribute('templateResult'))) {
			$view->vars['select2OptionsJavaScript']['templateResult'] = $form->getConfig()->getAttribute('templateResult');
		}
		if(is_string($form->getConfig()->getAttribute('templateSelection'))) {
			$view->vars['select2OptionsJavaScript']['templateSelection'] = $form->getConfig()->getAttribute('templateSelection');
		}

		switch($form->getConfig()->getAttribute('searchMethod')) {
			case self::SEARCH_METHOD_AND:
				$view->vars['select2OptionsJavaScript']['matcher'] = 'StingerSoftSelect2.matcher.and';
				break;
			case self::SEARCH_METHOD_OR:
				$view->vars['select2OptionsJavaScript']['matcher'] = 'StingerSoftSelect2.matcher.or';
				break;
			case self::SEARCH_METHOD_EXACT:
				break;
			default:
				if($form->getConfig()->getAttribute('searchMethod')) {
					$view->vars['select2OptionsJavaScript']['matcher'] = $form->getConfig()->getAttribute('searchMethod');
				}
				break;
		}

		$view->vars['select2Options']['closeOnSelect'] = $form->getConfig()->getAttribute('closeOnSelect');
		$view->vars['select2Options']['showTooltip'] = $form->getConfig()->getAttribute('showTooltip');
		$view->vars['select2Options']['showSelectionTooltip'] = $form->getConfig()->getAttribute('showSelectionTooltip');
		$view->vars['select2Options']['renderTitle'] = $form->getConfig()->getAttribute('renderTitle');
		$view->vars['select2Options']['maximumSelectionSize'] = $form->getConfig()->getAttribute('maximumSelectionSize');
		$view->vars['select2Options']['width'] = $form->getConfig()->getAttribute('width');
		$view->vars['select2Options']['dropdownAutoWidth'] = $form->getConfig()->getAttribute('dropdownAutoWidth');
		$view->vars['select2Options']['dropdownCssClass'] = $form->getConfig()->getAttribute('dropdownCssClass');
		$view->vars['select2Options']['containerCssClass'] = $form->getConfig()->getAttribute('containerCssClass');
		if($form->getConfig()->getAttribute('hideSelected')) {
			$view->vars['select2Options']['dropdownCssClass'] .= ' hide_selected';
		}
		if($form->getConfig()->getAttribute('select2-placeholder')) {
			$placeholder = $form->getConfig()->getAttribute('select2-placeholder');
			$placeholder = $translationDomain === false ? $placeholder : $this->translate($placeholder, [], $translationDomain);
			$view->vars['select2Options']['placeholder'] = $placeholder;
		}
		$view->vars['select2Options']['allowClear'] = $form->getConfig()->getRequired() ? false : true;
		if(!isset($view->vars['select2Options']['placeholder']) && !$form->getConfig()->getRequired()) {
			$view->vars['select2Options']['placeholder'] = '---';
		}
		if($form->getConfig()->getAttribute('route')) {
			$view->vars['select2Options']['ajax'] = [];
			$view->vars['select2Options']['multiple'] = $form->getConfig()->getAttribute('multiple');
			$view->vars['select2Options']['minimumInputLength'] = $form->getConfig()->getAttribute('minimumInputLength');
			$view->vars['select2OptionsJavaScript']['ajax'] = [];
			$view->vars['select2Options']['ajax']['dataType'] = 'json';
			$view->vars['select2Options']['ajax']['delay'] = $form->getConfig()->getAttribute('delay');
			$view->vars['select2Options']['ajax']['url'] = $this->generateUrl($form->getConfig()->getAttribute('route'), $form->getConfig()->getAttribute('routeParams'));
			$view->vars['select2OptionsJavaScript']['ajax']['processResults'] = $form->getConfig()->getAttribute('dataMapper');
			$view->vars['select2OptionsJavaScript']['ajax']['data'] = 'function (params) {
				return {
					term: params.term, 
					page: params.page
				};
			}';
		}
		if($form->getConfig()->getAttribute('theme')) {
			$view->vars['select2Options']['theme'] = $form->getConfig()->getAttribute('theme');
		}

		if(is_string($form->getConfig()->getAttribute('escapeMarkup'))) {
			$view->vars['select2OptionsJavaScript']['escapeMarkup'] = $form->getConfig()->getAttribute('escapeMarkup');
		} else {
			$view->vars['select2OptionsJavaScript']['escapeMarkup'] = 'StingerSoftSelect2.escapeMarkup.raw';
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefault('theme', 'bootstrap');
		$resolver->addAllowedTypes('theme', [
			'null',
			'string',
		]);

		$resolver->setDefault('searchMethod', self::SEARCH_METHOD_EXACT);
		$resolver->addAllowedTypes('searchMethod', [
			'integer',
			'string',
		]);
		$resolver->setAllowedValues('searchMethod', static function ($value) {
			if(is_string($value)) {
				return true;
			}
			return in_array($value, [
				self::SEARCH_METHOD_OR,
				self::SEARCH_METHOD_AND,
				self::SEARCH_METHOD_EXACT,
			], true);
		});

		// deprecated
		$resolver->setDefault('formatResult', null);
		$resolver->addAllowedTypes('formatResult', [
			'null',
			'integer',
			'string',
		]);
		$resolver->setAllowedValues('formatResult', static function ($value) {
			if(!$value) {
				return true;
			}
			if(is_string($value)) {
				return true;
			}
			return in_array($value, [
				self::MARKMATCH_DEFAULT,
				self::MARKMATCH_MULTIPLE,
				self::MARKMATCH_WHOLE,
			], true);
		});

		$resolver->setDefault('templateResult', null);
		$resolver->addAllowedTypes('templateResult', [
			'null',
			'string',
		]);

		$resolver->setDefault('templateSelection', null);
		$resolver->addAllowedTypes('templateSelection', [
			'null',
			'string',
		]);

		$resolver->setDefault('maximumSelectionSize', 0);
		$resolver->addAllowedTypes('maximumSelectionSize', 'integer');

		$resolver->setDefault('attr', static function (Options $options, $definedValue) {
			if($definedValue) {
				return $definedValue;
			}
			return ['style' => 'width: 100%'];
		});
		$resolver->setDefault('closeOnSelect', true);
		$resolver->addAllowedTypes('closeOnSelect', 'bool');
		$resolver->setDefault('hideSelected', static function (Options $options, $previousValue) {
			if($previousValue !== null) {
				return $previousValue;
			}
			return $options['multiple'] ? true : false;
		});
		$resolver->addAllowedTypes('hideSelected', 'bool');

		$resolver->setDefault('dropdownAutoWidth', true);
		$resolver->addAllowedTypes('dropdownAutoWidth', 'bool');
		$resolver->setDefault('dropdownCssClass', '');
		$resolver->addAllowedTypes('dropdownCssClass', 'string');
		$resolver->setDefault('containerCssClass', '');
		$resolver->addAllowedTypes('containerCssClass', 'string');

		$resolver->setDefault('width', 'resolve');
		$resolver->setAllowedValues('width', static function ($value) {
			if(in_array($value, [
				'element',
				'style',
				'resolve',
				'copy',
			])) {
				return true;
			}
			if($value === 'off') {
				trigger_error('Option off is not supported anymore', E_USER_DEPRECATED);
				return true;
			}
			$widthRegex = '/^(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/i';
			return preg_match($widthRegex, $value) === 1;
		});

		$resolver->setDefault('multiple', false);
		$resolver->addAllowedTypes('multiple', [
			'bool',
		]);

		$resolver->setDefault('minimumInputLength', 1);
		$resolver->addAllowedTypes('minimumInputLength', 'int');

		$resolver->setDefault('delay', 500);
		$resolver->addAllowedTypes('delay', 'int');

		$resolver->setDefault('route', false);
		$resolver->addAllowedTypes('route', [
			'string',
			'bool',
		]);
		$resolver->setDefault('routeParams', []);
		$resolver->addAllowedTypes('routeParams', 'array');

		$resolver->setDefault('dataMapper', 'StingerSoftSelect2.ajax.dataMapper.noop');
		$resolver->addAllowedTypes('dataMapper', [
			'string',
		]);

		$allowedTooltipValues = [
			true,
			false,
			'auto',
			'right',
			'left',
			'top',
			'bottom',
			'auto right',
			'auto left',
			'auto top',
			'auto bottom',
		];
		$resolver->setDefault('showTooltip', false);
		$resolver->addAllowedValues('showTooltip', $allowedTooltipValues);

		$resolver->setDefault('showSelectionTooltip', false);
		$resolver->addAllowedValues('showSelectionTooltip', $allowedTooltipValues);

		$resolver->setDefault('renderTitle', true);
		$resolver->addAllowedTypes('renderTitle', ['bool']);

		$resolver->setDefault('select2-placeholder', false);

		$resolver->setDefault('escapeMarkup', null);
		$resolver->addAllowedTypes('escapeMarkup', [
			'null',
			'string',
		]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getParent()
	 */
	public function getParent() {
		return ChoiceType::class;
	}

	/**
	 * Generates a URL from the given parameters.
	 *
	 * @param string      $route
	 *            The name of the route
	 * @param mixed       $parameters
	 *            An array of parameters
	 * @param bool|string $referenceType
	 *            The type of reference (one of the constants in UrlGeneratorInterface)
	 *
	 * @return string The generated URL
	 *
	 * @see UrlGeneratorInterface
	 */
	protected function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string {
		return $this->router->generate($route, $parameters, $referenceType);
	}

	/**
	 * Shortcut to return the Doctrine Registry service.
	 *
	 * @return ManagerRegistry
	 *
	 * @throws \LogicException If DoctrineBundle is not available
	 */
	protected function getDoctrine(): ManagerRegistry {
		return $this->managerRegistry;
	}

	/**
	 *
	 * @param string $id
	 * @param array  $parameters
	 * @param string $domain
	 * @return string
	 */
	protected function translate($id, array $parameters, $domain = 'messages'): string {
		return $this->translator->trans($id, $parameters, $domain);
	}
}

