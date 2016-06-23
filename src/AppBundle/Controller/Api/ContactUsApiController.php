<?php

namespace AppBundle\Controller\Api;

use AppBundle\Validator\Constraints\UsernameConstraints;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Type\UserType;
use AppBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Validator\ConstraintViolation;
use Lsw\ApiCallerBundle\Call\HttpGetHtml;

class ContactUsApiController extends Controller
{


    /**
     * Send Contact Message
     */
    public function sendMessageAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if(array_key_exists('key',$data)){

            $captchaApiInfo = $this->container->getParameter('google_re_captcha_info');

            $host = $captchaApiInfo['host'];
            $secret = $captchaApiInfo['secret'];

            $url= $host."?secret=".$secret."&response=".$data['key'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $jsonOutput = curl_exec($ch);
            curl_close($ch);
//            var_dump("Hello");
//            die();
//            $jsonOutput = $this->container->get('api_caller')->call(new HttpGetHtml($url, null, null));
            $captchaResponse = json_decode($jsonOutput,true);

            if($captchaResponse['success']){

                $this->get('fos_user.mailer')->sendContactUsEmail($data);

                return $this->_createJsonResponse('success',array(
                    'successTitle'=>"Your message has been sent",
                    'successDescription'=>"We will contact you as soon as possible"
                ),201);
            }else{
                return $this->_createJsonResponse('error',array(
                    'errorTitle'=>"Emails not Sent",
                    'errorDescription'=>"Captcha was Wrong. Reload and try again."
                ),400);
            }
        }else{
            return $this->_createJsonResponse('error',array(
                'errorTitle'=>"Message not Sent",
                'errorDescription'=>"Sorry we were unable to Send the message. FillUp the form and try again."
            ),400);
        }
    }

    /**
     * Send Mails To Friends
     */
    public function sendMailsToFriendsAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if(array_key_exists('key',$data)){

            $captchaApiInfo = $this->container->getParameter('google_re_captcha_info');

            $host = $captchaApiInfo['host'];
            $secret = $captchaApiInfo['secret'];

            $url= $host."?secret=".$secret."&response=".$data['key'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $jsonOutput = curl_exec($ch);
            curl_close($ch);
//            var_dump("Hello");
//            die();
//            $jsonOutput = $this->container->get('api_caller')->call(new HttpGetHtml($url, null, null));
            $captchaResponse = json_decode($jsonOutput,true);
            if($captchaResponse['success']){

                $this->get('fos_user.mailer')->sendFriendsEmail($data);

                return $this->_createJsonResponse('success',array(
                    'successTitle'=>"Emails have successfully sent to your Friends",
                    'successDescription'=>"Thank you for sharing our website."
                ),201);
            }else{
                return $this->_createJsonResponse('error',array(
                    'errorTitle'=>"Emails not Sent",
                    'errorDescription'=>"Captcha was Wrong. Reload and try again."
                ),400);
            }

        }else{
            return $this->_createJsonResponse('error',array(
                'errorTitle'=>"Emails not Sent",
                'errorDescription'=>"Sorry we were unable to Send the Emails. FillUp the form and try again."
            ),400);
        }



    }


    public function _createJsonResponse($key, $data,$code)
    {
        $serializer = $this->container->get('jms_serializer');
        $json = $serializer->serialize([$key => $data], 'json');
        $response = new Response($json, $code);
        return $response;
    }
}
