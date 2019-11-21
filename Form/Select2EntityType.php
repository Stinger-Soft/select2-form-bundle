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

use StingerSoft\Select2FormBundle\DataTransformer\EntitiesToArrayTransformer;
use StingerSoft\Select2FormBundle\DataTransformer\OneEntityToIdTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2EntityType extends Select2BaseType {

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Bridge\Doctrine\Form\Type\DoctrineType::buildForm()
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		if($options['multiple']) {
			$builder->resetViewTransformers();
			$builder->addViewTransformer(new EntitiesToArrayTransformer($options['choice_loader'], $this->getDoctrine()->getManager(), $options['class']));
		} else {
			$builder->resetViewTransformers();
			$builder->addViewTransformer(new OneEntityToIdTransformer($this->getDoctrine()->getManager(), $options['class'], $options['query_builder'], null));
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\Select2FormBundle\Form\Select2BaseType::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getParent()
	 */
	public function getParent() {
		return EntityType::class;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\FormTypeInterface::getName()
	 */
	public function getName() {
		return $this->getBlockPrefix();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
	 */
	public function getBlockPrefix() {
		return 'stinger_soft_select2_form_entity';
	}
}