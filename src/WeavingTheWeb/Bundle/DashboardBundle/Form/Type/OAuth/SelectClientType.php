<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type\OAuth;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use WeavingTheWeb\Bundle\DashboardBundle\Form\Type\AbstractUserAwareType;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SelectClientType extends AbstractUserAwareType
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
                    'class'         =>  'WeavingTheWebDashboardBundle:OAuth\Client',
                    'label'         =>  'oauth.label.oauth_clients',
                    'required'      =>  false,
                    'choice_label'  =>  'clientId',
                    'placeholder'   =>  'oauth.placeholder.empty_selection',
                    'query_builder' =>  function (EntityRepository $repository) {
                        if (is_null($this->user)) {
                            throw new \LogicException('An OAuth client should be selected on a per-user basis.');
                        }

                        return $repository->createQueryBuilder('c')
                            ->andWhere('c.user = :user')
                            ->setParameter('user', $this->user);
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
