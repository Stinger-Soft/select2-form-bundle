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

use StingerSoft\Select2FormBundle\ChoiceLoader\EntityFieldChoiceLoader;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 */
class Select2SyncTagType extends Select2BaseType {

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\Select2FormBundle\Form\Select2BaseType::buildForm()
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$delimiter = $options['delimiter'];
		if($options['multiple']) {
			$builder->addModelTransformer(new CallbackTransformer(static function ($tagsAsString) use ($delimiter) {
				// transform the string back to an array
				return $tagsAsString ? explode($delimiter, $tagsAsString) : [];
			}, static function ($tagsAsArray) use ($delimiter) {
				// transform the array to a string
				return $tagsAsArray ? implode($delimiter, $tagsAsArray) : '';
			}));
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::buildView()
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		parent::buildView($view, $form, $options);
		$view->vars['multiple'] = $options['multiple'];
		$view->vars['delimiter'] = $options['delimiter'];
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\Select2FormBundle\Form\Select2BaseType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefault('search_class', null);
		$resolver->setDefault('search_property', null);
		$resolver->setDefault('search_property_delimiter', false);
		$resolver->setDefault('query_builder_modifier', null);
		$resolver->setDefault('multiple', false);
		$resolver->setDefault('delimiter', ',');
		$resolver->setRequired([
			'search_class',
			'search_property',
		]);
		$resolver->setDefault('choice_translation_domain', false);

		$doctrine = $this->getDoctrine();

		$choiceLoader = static function (Options $options) use ($doctrine) {
			$searchClass = $options['search_class'];
			$searchProperty = $options['search_property'];

			return new EntityFieldChoiceLoader($doctrine, $searchClass, $searchProperty, $options['query_builder_modifier'], $options['search_property_delimiter']);
		};

		$resolver->setDefault('choice_loader', $choiceLoader);
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
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
	 */
	public function getBlockPrefix() {
		return 'stinger_soft_select2_form_sync_tag';
	}
}