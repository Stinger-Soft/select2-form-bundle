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

use Pec\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 * @internal
 *
 */
class DemoType extends AbstractType {

	public static $testArray = array(
		'Die Testoption Nummer 1' => 1,
		'Lorem ipsum' => 2,
		'Lorem ipsum dolor sit amet' => 3,
		'Scelerisque felis ullamcorper sit amet' => 4
	);

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::buildForm()
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('choice_single', Select2ChoiceType::class, array(
			'choices' => self::$testArray,
			'multiple' => false,
			'mapped' => false,
			'required' => false,
			'select2-placeholder' => 'stinger_soft_form.demo.placeholder',
			'translation_domain' => 'PecSelect2FormBundle',
			'searchMethod' => Select2BaseType::SEARCH_METHOD_AND,
			'choice_translation_domain' => false,
			'width' => 'resolve'
		));

		$builder->add('choice_single_required', Select2ChoiceType::class, array(
			'choices' => self::$testArray,
			'multiple' => false,
			'mapped' => false,
			'required' => true,
			'choice_translation_domain' => false,
			'searchMethod' => Select2BaseType::SEARCH_METHOD_OR,
			'searchMethod' => Select2BaseType::SEARCH_METHOD_AND,
			'width' => 'resolve'
		));

		$builder->add('choice_multi', Select2ChoiceType::class, array(
			'choices' => self::$testArray,
			'multiple' => true,
			'mapped' => false,
			'required' => false,
			'maximumSelectionSize' => 2,
			'searchMethod' => Select2BaseType::SEARCH_METHOD_AND,
			'choice_translation_domain' => false,
			'width' => 'resolve'
		));

		$builder->add('choice_single_ajax', Select2ChoiceType::class, array(
			'choices' => array(),
			'multiple' => false,
			'mapped' => false,
			'required' => false,
			'route' => 'stinger_soft_form_demo_json',
			'width' => '50%'
		));

		$builder->add('choice_multi_ajax', Select2ChoiceType::class, array(
			'choices' => array(),
			'multiple' => true,
			'mapped' => false,
			'required' => false,
			'route' => 'stinger_soft_form_demo_json',
			'width' => 'resolve'
		));

		$builder->add('entity_single_required', Select2EntityType::class, array(
			'class' => 'PecPlatformBundle:Group',
			'property_path' => 'name',
			'multiple' => false,
			'mapped' => false,
			'required' => true,
			'searchMethod' => Select2BaseType::SEARCH_METHOD_OR,
			'width' => 'resolve'
		));

		$builder->add('user_ajax', Select2EntityType::class, array(
			'required' => false,
			'class' => 'Pec\Bundle\UserBundle\Entity\User',
			'multiple' => false,
			'route' => 'pec_platform_user_ajax_list',
			'query_builder' => function ($er) {
				return $er->createQueryBuilder('u')
					->distinct('u')
					->setMaxResults(1)
					->orderBy('u.username', 'ASC');
			},
			'dataMapper' => 'PecPlatform.select2.ajax.dataMapper.labelToText',
			'width' => 'resolve'
		));

//		$builder->add('hierarchical_data', Select2HierarchicalType::class, array(
//			'required' => false,
//			'class' => 'Pec\Bundle\MtuEngineBundle\Entity\EngineSystemNode',
//			'multiple' => false,
//			'searchMethod' => 'PecPlatform.select2.matcher.hierarchical_and',
//			'width' => 'resolve',
//			'select_leafs_only' => false
//		));

//		$builder->add('hierarchical_data_empty', Select2HierarchicalType::class, array(
//			'required' => false,
//			'class' => 'Pec\Bundle\MtuEngineBundle\Entity\EngineSystemNode',
//			'query_builder' => function (EngineSystemNodeRepository $repos) {
//				return $repos->createQueryBuilder('node')->where('node.id < 0');
//			},
//			'multiple' => false,
//			'searchMethod' => 'PecPlatform.select2.matcher.hierarchical_and',
//			'width' => 'resolve'
//		));

//		$builder->add('hierarchical_data_leafs', Select2HierarchicalType::class, array(
//			'required' => false,
//			'class' => 'Pec\Bundle\MtuEngineBundle\Entity\EngineSystemNode',
//			'multiple' => false,
//			'searchMethod' => 'PecPlatform.select2.matcher.hierarchical_and',
//			'width' => 'resolve'
//		));

		$builder->add('username', Select2SyncTagType::class, array(
			'search_class' => User::class,
			'search_property' => 'username',
			'multiple' => true,
			'width' => 'resolve',
			'choice_translation_domain' => false
		));

		$builder->add('username_single', Select2SyncTagType::class, array(
			'search_class' => User::class,
			'search_property' => 'username',
			'multiple' => false,
			'width' => 'resolve',
			'choice_translation_domain' => false
		));

		$builder->add('username_ajax', Select2AsyncTagType::class, array(
			'route' => 'pec_platform_user_ajax_list',
			'dataMapper' => 'PecPlatform.select2.ajax.dataMapper.labelOnly',
			'multiple' => true,
			'width' => 'resolve',
			'choice_translation_domain' => false
		));

		$builder->add('username_ajax_single', Select2AsyncTagType::class, array(
			'route' => 'pec_platform_user_ajax_list',
			'dataMapper' => 'PecPlatform.select2.ajax.dataMapper.labelOnly',
			'multiple' => false,
			'width' => 'resolve',
			'choice_translation_domain' => false
		));

		$builder->add('save', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefault('translation_domain', 'PecSelect2FormBundle');
		$resolver->setDefault('label_format', 'stinger_soft_form.demo.%name%');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
	 */
	public function getBlockPrefix() {
		return 'stinger_soft_form_demo';
	}
}