<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Repository\ReferralRepository;
use AppBundle\Repository\CampusRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;


use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('newsImageUrl','text',array(
            'constraints' => array(
                new NotBlank(),
            )
        ));
    }


    public function getName()
    {
        return 'appbundle_news_image';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\NewsImage',
            'csrf_protection' => false,
            'allow_extra_fields' => true,

        ));
    }

}