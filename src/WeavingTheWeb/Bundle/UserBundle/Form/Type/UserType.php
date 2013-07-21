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
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('twitter_username', 'text')
            ->add('username', 'text')
            ->add('email', 'text')
            ->add('firstName', 'text', ['required' => false])
            ->add('lastName', 'text', ['required' => false])
            ->add('disabled', 'checkbox', ['mapped' => false]) // TODO As a weaver, I can prevent my data from being collected
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
        $resolver->setDefaults(['data_class' => 'WTW\UserBundle\Entity\User']);
    }
}