<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Repository\ReferralRepository;
use AppBundle\Repository\CampusRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;


use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('emailNotification','text',array(
            'constraints' => array(
                new NotBlank(),
            )
        ));

    }


    public function getName()
    {
        return 'app_email_notification_update';
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User',
            'csrf_protection' => false,
//            'validation_groups' => false,
            'allow_extra_fields' => true,
//            'error_mapping' => array(
//                'usernameAlreadyExist' => 'username',
//            ),

        ));
    }

}