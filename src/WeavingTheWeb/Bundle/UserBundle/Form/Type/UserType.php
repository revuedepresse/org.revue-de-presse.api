<?php

namespace WeavingTheWeb\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class UserType
 * @package WeavingTheWeb\Bundle\UserBundle\Form\Type
 */
class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('twitter_username', 'text', ['required' => false])
            ->add('username', 'text')
            ->add('email', 'text')
            ->add('currentPassword', 'password', ['label' => 'field_current_password', 'mapped' => false])
            ->add('plainPassword', 'repeated', [
                'type' => 'password',
                'first_options' => [ 'label' => 'field_new_password'],
                'second_options' => [ 'label' => 'field_password_again'],
                'required' => false
            ])
            ->add('save', 'submit');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'WTW\UserBundle\Entity\User',
            'translation_domain' => 'user'
        ]);
    }
}
