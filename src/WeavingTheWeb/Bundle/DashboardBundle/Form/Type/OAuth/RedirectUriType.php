<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type\OAuth;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank,
    Symfony\Component\Validator\Constraints\Url;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class RedirectUriType extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'redirect_uri';
    }

    public function getParent()
    {
        return 'url';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(            [
            'constraints'       => [new Url(), new NotBlank()],
            'error_bubbling'    => false,
            'label'             => 'oauth.label.redirect_uri',
            'required'          => true
        ]);
    }
}
