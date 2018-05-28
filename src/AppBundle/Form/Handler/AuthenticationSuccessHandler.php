<?php
/**
 * Created by PhpStorm.
 * User: ManiaC
 * Date: 8/21/17
 * Time: 9:59 AM
 */

namespace AppBundle\Form\Handler;


use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Log;
use AppBundle\Form\Type\LogType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{

    protected $entityManager;
    protected $formFactory;
    protected $JMSSerializerBundle;

    public function __construct(EntityManagerInterface $entityManager, FormFactory $formFactory, Serializer $JMSSerializerBundle)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->JMSSerializerBundle = $JMSSerializerBundle;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->onAuthenticationSuccess($event->getRequest(), $event->getAuthenticationToken());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();

        if ($user->getRegistrationStatus() == "incomplete") {
            if (filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
                //Mail Exists
                $email = "false";
            } else {
                //Mail NOT Exists
                $email = "true";
            }
            $userId = null;
            if ($user->getGoogleId() != null) {
                $userId = $user->getGoogleId();
            }
            if ($user->getFacebookId() != null) {
                $userId = $user->getFacebookId();
            }

            $user_data = array(
                'username' => $user->getUsername(),
                'email_needed' => $email,
                'userId' => $userId

            );

            return $this->_createJsonResponse('success', array(
                'successTitle' => "Login Successful",
//                'successDescription'=>"Please Complete your registration process.",
                'successDescription' => "Por favor completa el formulario.",
                'successData' => $user_data
            ), 200);


        } elseif ($user->getRegistrationStatus() == "complete") {

            if($user->getAdminApproved()==="No"){
                return $this->_createJsonResponse('error',array(
//                                'errorTitle'=>"Account is Blocked by Admin",
//                                'errorDescription'=>"Your account is blocked by the admin. Please contact support or try with another Google account.",
                        'errorTitle'=>"La cuenta está bloqueada por el administrador",
                        'errorDescription'=>"Tu cuenta ha sido bloqueada por el administrador. Por favor, póngase en contacto con soporte o cree una nueva cuenta."
                    )
                    ,400);
            }elseif ($user->getAdminApproved()==="Yes") {
                $logData = array(
                    'user' => $user->getId(),
                    'logType' => "Login",
                    'logDateTime' => gmdate('Y-m-d H:i:s'),
                    'logDescription' => $user->getUsername() . " has Logged In",
                    'userIpAddress' => $request->getClientIp(),
                    'logUserType' => in_array("ROLE_ADMIN_USER", $user->getRoles()) ? "Admin User" : "Normal User"
                );
                $this->_saveLog($logData);

                return $this->_createJsonResponse('success', array(
                    'successTitle' => "Hola, bienvenido " . $user->getUsername(),
                    'successDescription' => "Usted ha iniciado sesión",
                    'successData' => array(
                        'username' => $user->getUsername(),
                        'registrationStatus' => $user->getRegistrationStatus()
                    )
                ), 200);
            }

        } else {
            return $this->_createJsonResponse('error', array(
//                'errorTitle' => "Login Unsuccessful",
//                'errorDescription' => "Please try to Login again."
                'errorTitle' => "Iniciar sesión sin éxito",
                'errorDescription' => "Intenta iniciar sesión de nuevo."
            ), 400);
        }
    }

    public function _saveLog($logData)
    {
        $em = $this->entityManager;
        $log = new Log();
        $logForm = $this->formFactory->create(new LogType(), $log);

        $logForm->submit($logData);
        if ($logForm->isValid()) {
            $em->persist($log);
            $em->flush();
        }
    }

    public function _createJsonResponse($key, $data, $code)
    {
        $serializer = $this->JMSSerializerBundle;
        $json = $serializer->serialize([$key => $data], 'json');
        $response = new Response($json, $code);
        return $response;
    }

} 