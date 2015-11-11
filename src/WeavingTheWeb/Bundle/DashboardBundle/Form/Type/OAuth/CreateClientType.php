<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type\OAuth;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\CallbackTransformer;

use Symfony\Component\Form\FormInterface,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank,
    Symfony\Component\Validator\Constraints\Url;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class CreateClientType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'redirect_uri',
                'redirect_uri'
            )
            ->add('submit', 'submit', ['label' => 'oauth.label.submit']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'create_oauth_client';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection'       => true,
            'intention'             => 'create_oauth_client',
            'method'                => 'POST',
            'translation_domain'    => 'oauth',
            'attr'                  => ['id' => 'create-oauth-client']
        ]);
    }
}
