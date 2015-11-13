<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SelectRemoteType extends AbstractUserAwareType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'remotes',
                'entity',
                [
                    'class'         =>  'WeavingTheWebDashboardBundle:Remote',
                    'label'         =>  'remote.label.remotes',
                    'required'      =>  false,
                    'choice_label'  =>  'host',
                    'placeholder'   =>  'remote.placeholder.empty_selection',
                    'query_builder' =>  function (EntityRepository $repository) {
                        if (is_null($this->user)) {
                            throw new \LogicException('A remote should be selected on a per-user basis.');
                        }

                        return $repository->createQueryBuilder('c')
                            ->andWhere('c.user = :user')
                            ->setParameter('user', $this->user);
                    }
                ]
            )
            ->add('submit', 'submit', ['label' => 'remote.label.select']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'select_remote';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection'       => true,
            'intention'             => 'select_remote',
            'method'                => 'POST',
            'translation_domain'    => 'remote',
            'attr'                  => ['id' => 'select-remote']
        ]);
    }
}
