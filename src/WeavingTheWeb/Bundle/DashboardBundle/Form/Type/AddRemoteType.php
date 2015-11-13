<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank,
    Symfony\Component\Validator\Constraints\Url;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AddRemoteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'remote',
                'url', [
                    'label'         => 'remote.label.remote',
                    'constraints'   => [new NotBlank(), new Url()],
                    'attr'          => ['placeholder' => 'remote.placeholder.remote'],
                ]
            )
            ->add(
                'access_token',
                'text',
                [
                    'label'         => 'remote.label.access_token',
                    'constraints'   => [new NotBlank()],
                    'attr'          => ['placeholder' => 'remote.placeholder.access_token'],

                ]
            )
            ->add('submit', 'submit', ['label' => 'remote.label.submit']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'add_remote';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'attr'                  => ['id' => 'add-remote-form'],
            'csrf_protection'       => true,
            'intention'             => 'remote',
            'method'                => 'POST',
            'translation_domain'    => 'remote',
        ]);
    }
}
