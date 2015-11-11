<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type\OAuth;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class RegisterClientType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'client_id',
                'text',
                [
                    'attr'              => [
                        'placeholder'   => 'oauth.placeholder.client_id'
                    ],
                    'constraints'       => [new NotBlank()],
                    'error_bubbling'    => false,
                    'label'             => 'oauth.label.client_id',
                    'required'          => true,
                ]
            )->add(
                'client_secret',
                'text',
                [
                    'attr'              => [
                        'placeholder'   => 'oauth.placeholder.client_secret'
                    ],
                    'constraints'       => [new NotBlank()],
                    'error_bubbling'    => false,
                    'label'             => 'oauth.label.client_secret',
                    'required'          => true,
                ]
            )
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
        return 'register_oauth_client';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection'       => true,
            'intention'             => 'register_oauth_client',
            'method'                => 'POST',
            'translation_domain'    => 'oauth',
            'attr'                  => ['id' => 'register-oauth-client']
        ]);
    }
}
