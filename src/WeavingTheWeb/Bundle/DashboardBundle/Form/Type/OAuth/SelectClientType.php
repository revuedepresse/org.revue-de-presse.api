<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type\OAuth;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\CallbackTransformer;

use Symfony\Component\Form\FormInterface,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SelectClientType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oauth_clients',
                'entity',
                [
                    'attr'          =>  [
                        'placeholder' => 'oauth.placeholder.oauth_clients'
                    ],
                    'class'         =>  'WeavingTheWebDashboardBundle:OAuth\Client',
                    'label'         =>  'oauth.label.oauth_clients',
                    'required'      =>  true,
                    'choice_label'  =>  'clientId',
                    'query_builder' => function (EntityRepository $repository) {
                        return $repository->createQueryBuilder('c');
                    }
                ]
            )
            ->add('submit', 'submit', ['label' => 'oauth.label.select']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'select_oauth_client';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection'       => true,
            'intention'             => 'select_oauth_client',
            'method'                => 'POST',
            'translation_domain'    => 'oauth',
            'attr'                  => ['id' => 'select-oauth-client']
        ]);
    }
}
