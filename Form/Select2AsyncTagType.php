<?php

/*
 * This file is part of the PEC Platform Development.
 *
 * (c) PEC project engineers & consultants
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\Select2FormBundle\Form;

use StingerSoft\Select2FormBundle\ChoiceLoader\AjaxValueOnlyChoiceLoader;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 */
class Select2AsyncTagType extends Select2BaseType {

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
				$tagArray = $tagsAsString ? explode($delimiter, $tagsAsString) : array();
				return array_combine($tagArray, $tagArray);
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
		$view->vars['delimiter'] = $options['delimiter'];
		$view->vars['multiple'] = $options['multiple'];
		parent::buildView($view, $form, $options);
		$view->vars['select2Options']['tags'] = true;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\Select2FormBundle\Form\Select2BaseType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefault('multiple', true);
		$resolver->setDefault('delimiter', ',');
		$resolver->setDefault('choice_loader', static function (Options $options) {
			return new AjaxValueOnlyChoiceLoader();
		});
		$resolver->setRequired('route');
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
		return 'stinger_soft_select2_form_async_tag';
	}
}