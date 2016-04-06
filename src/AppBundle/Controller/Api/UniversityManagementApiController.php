<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\Campus;
use AppBundle\Form\Type\UniversityType;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\Type\CampusType;
use AppBundle\Entity\University;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Lsw\ApiCallerBundle\Call\HttpGetJson;
use Lsw\ApiCallerBundle\Call\HttpGetHtml;

class UniversityManagementApiController extends Controller
{


    /**
     * Activated Universities When Under Search
     */
    public function universityAutocompleteActivatedSearchListAction(Request $request)
    {


        $query = $request->request->get('query');
        $em = $this->getDoctrine()->getManager();

        if ($query == null||$query == "") {

            $responseData= array(
                'university' => ""
            );
            return $this->_createJsonResponse('success',array('successData'=>$responseData),200);
        }

        $universities = $em->getRepository('AppBundle:University')->getActivatedUniversitySearchResults($query);
        $data = array();
        foreach ($universities as $university) {
            array_push($data, array(
                'display' => $university['universityName'] . ", " . $university['campusName'] . ", " . $university['stateShortName'] . ", " . $university['countryName'],
                'value' => $university['campusId']
            ));

        }

        return $this->_createJsonResponse('success',array('successData'=>$data),200);

    }

    /**
     * University Search Autocomplete When Search By Name
     */
    public function universityAutocompleteNameSearchListAction(Request $request)
    {
        $query = $request->request->get('query');
        $em = $this->getDoctrine()->getManager();


        if ($query == null) {

            return $this->_createJsonResponse('success',array('successData'=>array('university' => "")),200);

        } else {
            $universities = $em->getRepository('AppBundle:University')->getUniversitySearchResults($query);

            return $this->_createJsonResponse('success',array('successData'=>$universities),200);

        }

    }

    /**
     * University Search for Admin
     */
    public function universitySearchAdminAction(Request $request)
    {

        $content = $request->getContent();
        $data = json_decode($content, true);
        $searchQuery = $data["searchQuery"];
        $pageSize = $data["pageSize"];
        $pageNumber = $data["pageNumber"];

        $em = $this->getDoctrine()->getManager();

//        var_dump($request);
        $totalNumber = $em->getRepository('AppBundle:University')->getUniversitySearchResultNumberAdmin($searchQuery);
        $universities = $em->getRepository('AppBundle:University')->getUniversitySearchResultAdmin($searchQuery, $pageNumber, $pageSize);


        return $this->_createJsonResponse('success',array('successData'=>array(
            'universities' => $universities,
            'totalNumber' => $totalNumber
        )),200);


    }

    /**
     * Update University
     */
    public function updateUniversityAction(Request $request)
    {
        //Initialize Repositories
        $em = $this->getDoctrine()->getManager();
        $universityRepo = $em->getRepository('AppBundle:University');
        $serializer = $this->container->get('jms_serializer');




        //Getting Request Data
        $data = null;
        $content = $request->getContent();
        if (!empty($content)) {
            $data = json_decode($content, true);
        }

        if (count($data) > 0) {

            $message_array = array();

            foreach ($data as $request_data) {
                //Initializing Variables
                $oldUniversityUrl = "";
                $oldUniversityName = "";
                $oldUniversityStatus = "";


                if (array_key_exists("universityId", $request_data)) {


                    $university = $universityRepo->findOneBy(array(
                        'id' => $request_data['universityId']
                    ));

                    $oldUniversityName = $university->getUniversityName();
                    $oldUniversityUrl = $university->getUniversityUrl();
                    $oldUniversityStatus = $university->getUniversityStatus();

                    $university_update_form = $this->createForm(new UniversityType(), $university);
                    $university_update_form->remove('campuses');
                    $university_update_form->remove('referral');

                    $university_submitted_data = array();

                    if (array_key_exists("universityUrl", $request_data))
                        $university_submitted_data['universityUrl'] = $request_data['universityUrl'];

                    if (array_key_exists("universityName", $request_data))
                        $university_submitted_data['universityName'] = $request_data['universityName'];

                    if (array_key_exists("universityStatus", $request_data))
                        $university_submitted_data['universityStatus'] = $request_data['universityStatus'];

//                    var_dump($university_submitted_data);

                    $university_update_form->submit($university_submitted_data);


                    if ($university_update_form->isValid()) {
//
                        $em->persist($university);
                        $em->flush();
                        array_push($message_array, array(
                            'success' => "University Updated Successfully",
                            'universityId' => $request_data['universityId']
                        ));
                        $university_form_decode['children']['universityId']['value'] = "University Updated Successfully";

                    } else {
                        $em->clear();
                        $university_form = $serializer->serialize($university_update_form, 'json');
                        $university_form_decode = json_decode($university_form, true);
                        $university_form_decode['children']['universityName']['value'] = $oldUniversityName;
                        $university_form_decode['children']['universityStatus']['value'] = $oldUniversityStatus;
                        $university_form_decode['children']['universityUrl']['value'] = $oldUniversityUrl;
                        $university_form_decode['children']['universityId']['value'] = $request_data['universityId'];

                        array_push($message_array, $university_form_decode);
                    }


                }

            }

            return $this->_createJsonResponse('success',array(
                'successTitle'=>'University Updated Successfully',
                'successData'=>$message_array
            ),200);

        } else {

            return $this->_createJsonResponse('error',array(
                'errorTitle'=>'University was not Updated',
                'errorDescription'=>'Please Check the form and submit again'
            ),400);
        }


    }

    /**
     * Save new Universities.
     */
    public function saveNewUniversityAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');
        /*$stateRepo = $em->getRepository("AppBundle:State");*/

        $request_data = json_decode($request->getContent(), true);

        if(array_key_exists('key',$request_data)){

            $captchaApiInfo = $this->getParameter('google_re_captcha_info');

            $host = $captchaApiInfo['host'];
            $secret = $captchaApiInfo['secret'];

            $url= $host."?secret=".$secret."&response=".$request_data['key'];

            $jsonOutput = $this->container->get('api_caller')->call(new HttpGetHtml($url, null, null));
            $captchaResponse = json_decode($jsonOutput,true);

            if($captchaResponse['success']){
                $universityData=array();



                $universityData['universityName']=$request_data['universityName'];
                $universityData['universityStatus']='Activated';
                $universityData['universityUrl']=$request_data['universityUrl'];
                $universityData['referral']=$request_data['referral'];
                $universityData['campuses']=array();

                for($i=0; $i<count($request_data['campuses']);$i++){

                    array_push($universityData['campuses'],array(
                        'campusName'=>$request_data['campuses'][$i]['campusName'],
                        'state'=>$request_data['campuses'][$i]['state'],
                        'campusStatus'=>'Activated'
                    ));

                }


                $universityEntity = new University();

                $universityForm = $this->createForm(new UniversityType(), $universityEntity);

                $universityForm->submit($universityData);

                if ($universityForm->isValid()) {

                    $em->persist($universityEntity);
                    $em->flush();

                    return $this->_createJsonResponse('success',array(
                        'successTitle'=>"University Successfully Created"
                    ),201);

                } else {

                    $formErrorData = json_decode($serializer->serialize($universityForm, 'json'),true);

                    return $this->_createJsonResponse('error',array(
                        'errorTitle'=>"University Creation Unsuccessful",
                        'errorDescription'=>"Please fill up the form again & submit.",
                        'errorData'=>$formErrorData
                    ),400);

                }


            }else{
                return $this->_createJsonResponse('error',array(
                    'errorTitle'=>"University Creation Unsuccessful",
                    'errorDescription'=>"Captcha was Wrong. Reload and try again."
                ),400);
            }
        }else{
            return $this->_createJsonResponse('error',array(
                'errorTitle'=>"University Creation Unsuccessful",
                'errorDescription'=>"Sorry we were unable to create university. FillUp the form and try again."
            ),400);
        }


    }

    /**
     * Save new Universities Admin Api.
     */
    public function saveNewUniversityAdminAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');
        $stateRepo = $em->getRepository("AppBundle:State");
        $request_data = json_decode($request->getContent(), true);

        $message_array=array();
        foreach ($request_data as $university) {

            $university['universityStatus']="Activated";
            $universityEntity = new University();

            foreach($university['campuses'] as $campus){
                $campusName = null;
                $state = null;
                $campusEntity = new Campus();
                if(array_key_exists('campusName',$campus))$campusEntity->setCampusName($campus['campusName']);
                if(array_key_exists('state',$campus))$campusEntity->setState($stateRepo->findOneById($campus['state']));
                $campusEntity->setCampusStatus('Activated');     //TODO Its not working
                $universityEntity->addCampus($campusEntity);
            }

            $universityForm = $this->createForm(new UniversityType(), $universityEntity);

            //TODO work on response
            $universityForm->submit($university);

            if ($universityForm->isValid()) {
                $em->persist($universityEntity);
                $em->flush();

                array_push($message_array,array(
                    'success'=>'University Successfully Created'
                ));
            } else {
                $em->clear();
                $universityFormErrorJson = $serializer->serialize($universityForm, 'json');
                array_push($message_array,json_decode($universityFormErrorJson,true));
            }

        }

        $json = $serializer->serialize($message_array, 'json');
        $response = new Response($json , 200);
        return $response;



    }

    /**
     * Displays a form to update an Just Created User entity.
     *
     * @Route("/api/university/delete", name="delete_university")
     * @Method({"POST"})
     */
    public function deleteUniversityAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->container->get('jms_serializer');
        $universityRepo = $em->getRepository("AppBundle:University");
        $request_data = json_decode($request->getContent(), true);

        $university = $universityRepo->findOneById($request_data['deleteId']);

        $message_array = null;
        if (!$university) {
            $message_array  = array(
                'errorTitle'=> 'University cannot be deleted',
                'errorDescription'=>'No University was found.'
            );

            return $this->_createJsonResponse('error',$message_array,400);

        }else{
            $em->remove($university);
            $em->flush();
            $message_array  = array(
                'successTitle'=>'University has been removed.'
            );
            return $this->_createJsonResponse('success',$message_array,200);
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
