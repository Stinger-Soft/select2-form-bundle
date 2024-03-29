<?php
/*
 * This file is part of the PEC Platform Select2FormBundle.
 *
 * (c) PEC project engineers &amp; consultants
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\Select2FormBundle\Form;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2CountryType extends Select2BaseType implements ChoiceLoaderInterface {

	/**
	 * Country loaded choice list.
	 *
	 * The choices are lazy loaded and generated from the Intl component.
	 *
	 * {@link \Symfony\Component\Intl\Intl::getRegionBundle()}.
	 *
	 * @var ArrayChoiceList
	 */
	private $choiceList;

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'choice_loader' => function (Options $options) {
				if($options['choices']) {
					@trigger_error(sprintf('Using the "choices" option in %s has been deprecated since version 3.3 and will be ignored in 4.0. Override the "choice_loader" option instead or set it to null.', __CLASS__), E_USER_DEPRECATED);

					return null;
				}

				return $this;
			},
			'choice_translation_domain' => false,
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix() {
		return 'stinger_soft_select2_form_country';
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadChoiceList($value = null) {
		if(null !== $this->choiceList) {
			return $this->choiceList;
		}

		return $this->choiceList = new ArrayChoiceList(array_flip(Intl::getRegionBundle()->getCountryNames()), $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadChoicesForValues(array $values, $value = null) {
		// Optimize
		$values = array_filter($values);
		if(empty($values)) {
			return array();
		}

		// If no callable is set, values are the same as choices
		if(null === $value) {
			return $values;
		}

		return $this->loadChoiceList($value)->getChoicesForValues($values);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadValuesForChoices(array $choices, $value = null) {
		// Optimize
		$choices = array_filter($choices);
		if(empty($choices)) {
			return array();
		}

		// If no callable is set, choices are the same as values
		if(null === $value) {
			return $choices;
		}

		return $this->loadChoiceList($value)->getValuesForChoices($choices);
	}

	public function getParent() {
		return Select2ChoiceType::class;
	}

}