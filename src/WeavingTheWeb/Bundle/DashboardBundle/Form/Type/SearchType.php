<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SearchType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('keywords', 'text', ['label' => 'Keywords'])
            ->add('submit', 'submit', ['label' => 'Search']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'elasticsearch';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'            => 'WeavingTheWeb\Bundle\DashboardBundle\Entity\Search',
            'csrf_protection'       => true,
            'translation_domain'    => 'search',
            'intention'             => 'search',
        ]);
    }
}
