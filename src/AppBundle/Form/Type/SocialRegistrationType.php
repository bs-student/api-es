<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Repository\ReferralRepository;
use AppBundle\Repository\CampusRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;


use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder->add('fullName','text',array(
            'constraints' => array(
                new NotBlank(),

            )
        ));

        $builder->add('username','text',array(
            'constraints' => array(
                new NotBlank(),

            )
        ));


        $builder->add('email','email',array(
            'constraints' => array(
                new NotBlank(),
                new Email(),
            )
        ));




        $builder->add('enabled','boolean',array(
            'constraints' => array(
                new NotBlank(),

            )
        ));
        $builder->add('adminApproved','text',array(
            'constraints' => array(
                new NotBlank(),

            )
        ));


        $builder->add('googleId','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('facebookId','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('registrationStatus','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('googleEmail','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('googleToken','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('facebookEmail','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('facebookToken','text',array(
            'constraints' => array(
                new NotBlank(),

            ),
//            'error_bubbling'=>true
        ));

        $builder->add('referral', 'entity', array(
            'class' => "AppBundle:Referral",
            'property' => 'referralName',
            'constraints' => array(
                new NotBlank(),

            )

        ));

        $builder->add('campus', 'entity', array(
            'class' => "AppBundle:Campus",
            'property' => 'campusName',
            'constraints' => array(
                new NotBlank(),

            )
        ));

    }


    public function getName()
    {
        return 'app_social_user';
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