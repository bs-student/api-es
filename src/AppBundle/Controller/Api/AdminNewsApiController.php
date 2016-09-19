<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\Book;
use AppBundle\Entity\BookImage;
use AppBundle\Entity\Campus;
use AppBundle\Entity\Contact;
use AppBundle\Entity\News;
use AppBundle\Entity\Quote;
use AppBundle\Form\Type\BookDealType;
use AppBundle\Form\Type\ContactType;
use AppBundle\Form\Type\NewsType;
use AppBundle\Form\Type\QuoteType;
use AppBundle\Form\Type\UniversityType;
use Doctrine\Common\Collections\ArrayCollection;


use FOS\UserBundle\Entity\User;
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
use AppBundle\Form\Type\BookType;
use Symfony\Component\HttpFoundation\FileBag;

class AdminNewsApiController extends Controller
{


    /**
     * Get News for Admin
     */
    public function getNewsAction(Request $request){

        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        if(in_array('ROLE_ADMIN_USER',$user->getRoles(),true)){

            $content = $request->getContent();
            $data = json_decode($content, true);
            $em = $this->getDoctrine()->getManager();
            $newsRepo=$em->getRepository('AppBundle:News');

            $pageSize = $data["pageSize"];
            $searchQuery = filter_var($data["searchQuery"], FILTER_SANITIZE_STRING);
            $pageNumber = $data["pageNumber"];
            $sort = $data["sort"];

            $totalNumber = $newsRepo->getAllNewsSearchNumber($searchQuery);
            $searchResults= $newsRepo->getAllNewsSearchResult($searchQuery, $pageNumber, $pageSize,$sort);

            $newsData = array();
            foreach($searchResults as $news){
                $news['newsDateTime']=$news['newsDateTime']->format('d M Y');
                $images = $newsRepo->findOneById($news['newsId'])->getNewsImages();

                $news['newsImages']=array();
                foreach($images as $image){
                    array_push($news['newsImages'], array(
                        'imageId'=>$image->getId(),
                        'image'=>$image->getNewsImageUrl()
                    ));
                }

                array_push($newsData,$news);
            }


            $data = array(
                'totalNews' => $newsData ,
                'totalNumber' => $totalNumber
            );

            return $this->_createJsonResponse('success', array('successData'=>array('news'=>$data)), 200);
        }else{
            return $this->_createJsonResponse('error', array('errorTitle'=>"You are not authorized to see this page."), 400);
        }
    }


    /**
     * Update News
     */
    public function updateNewsAction(Request $request){
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        if(in_array('ROLE_ADMIN_USER',$user->getRoles(),true)){

            $content = $request->getContent();
            $data = json_decode($content, true);
            $em = $this->getDoctrine()->getManager();
            $newsRepo=$em->getRepository('AppBundle:News');

            $news = $newsRepo->findOneById($data['newsId']);

            if($news!=null){
                $newsForm = $this->createForm(new NewsType(), $news);
                $newsForm->remove('newsImages');
                $data['newsDateTime']=gmdate('Y-m-d H:i:s');
                $newsForm->submit($data);

                if ($newsForm->isValid()) {
                    $em->persist($news);
                    $em->flush();
                    return $this->_createJsonResponse('success', array(
                        'successTitle' => "News has been updated"
                    ), 200);
                } else {
                    return $this->_createJsonResponse('error', array("errorTitle"=>"Could Not update news","errorData" => $newsForm), 400);
                }
            }else{
                return $this->_createJsonResponse('error', array("errorTitle"=>"News was not found"), 400);
            }


        }else{
            return $this->_createJsonResponse('error', array('errorTitle'=>"You are not authorized to see this page."), 400);
        }
    }

    /**
     * Add News
     */
    public function addNewsAction(Request $request){
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        if(in_array('ROLE_ADMIN_USER',$user->getRoles(),true)){

            $content = $request->get('news');
            $data = json_decode($content, true);
            $em = $this->getDoctrine()->getManager();

            //Prepare File
            $fileDirHost = $this->container->getParameter('kernel.root_dir');
            $fileDir = '/../web/newsImages/';
            $fileNameDir = '/newsImages/';
            $files = $request->files;

            //Return Error if image not found
            if(count($files)==0){
                return $this->_createJsonResponse('error', array('errorTitle' => "Cannot Add News", 'errorDescription' => "Image not Found"), 400);
            }

            //Upload Image
            $fileUploadError = false;
            $data['newsImages']=array();
            foreach ($files as $file) {
                if ((($file->getSize()) / 1024) <= 200) {
                    $fileSaveName = gmdate("Y-d-m_h_i_s_") . rand(0, 99999999) . "." . 'jpg';
                    $file->move($fileDirHost . $fileDir, $fileSaveName);

//                    $this->_smart_resize_image($fileDirHost.$fileDir.$fileSaveName , null, 780 , 490 , false , $fileDirHost.$fileDir.$fileSaveName , false , false ,100 );
                    $this->_resize(780,490,$fileDirHost.$fileDir.$fileSaveName,$fileDirHost.$fileDir.$fileSaveName);
                    array_push($data['newsImages'],array(
                        'newsImageUrl'=>$fileNameDir . $fileSaveName
                    ));
                } else {
                    $fileUploadError = true;
                }
            }
            //If Error Occurs than Return Error Message
            if($fileUploadError)return $this->_createJsonResponse('error', array('errorTitle' => "Cannot Add News", 'errorDescription' => "Image is more than 200 KB"), 400);




            $news = new News();

            $data['newsStatus']='Activated';
            $data['newsDateTime']=gmdate('Y-m-d H:i:s');


            $newsForm = $this->createForm(new NewsType(), $news);

            $newsForm->submit($data);


            if ($newsForm->isValid()) {
                $em->persist($news);
                $em->flush();

                $images = $news->getNewsImages();
                $imageData=array();
                foreach($images as $image){
                    array_push($imageData,array(
                        'imageId'=>$image->getId(),
                        'image'=>$image->getNewsImageUrl()
                    ));
                }

                return $this->_createJsonResponse('success', array(
                    'successTitle' => "News has been created",
                    'successData'=>array(
                        'newsId'=>$news->getId(),
                        'newsTitle'=>$news->getNewsTitle(),
                        'newsDescription'=>$news->getNewsDescription(),
                        'newsDateTime'=>$news->getNewsDateTime()->format('d M Y'),
                        'newsStatus'=>$news->getNewsStatus(),
                        'newsImages'=>$imageData
                    )
                ), 201);

            } else {
                return $this->_createJsonResponse('error', array("errorTitle"=>"Could Not create news","errorData" => $newsForm), 400);
            }


        }else{
            return $this->_createJsonResponse('error', array('errorTitle'=>"You are not authorized to see this page."), 400);
        }
    }


    // New Image  Resize function
    public function _resize($newWidth , $newHeight, $targetFile, $originalFile) {

        $info = getimagesize($originalFile);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image_create_func = 'imagecreatefromjpeg';
                $image_save_func = 'imagejpeg';
//                $new_image_ext = 'jpg';
                break;

            case 'image/png':
                $image_create_func = 'imagecreatefrompng';
                $image_save_func = 'imagepng';
//                $new_image_ext = 'png';
                break;

            case 'image/gif':
                $image_create_func = 'imagecreatefromgif';
                $image_save_func = 'imagegif';
//                $new_image_ext = 'gif';
                break;

            default:
                throw new Exception('Unknown image type.');
        }

        $img = $image_create_func($originalFile);
        list($width, $height) = getimagesize($originalFile);

//        $newHeight = ($height / $width) * $newWidth;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        $image_save_func($tmp, "$targetFile");
    }


    public function _createJsonResponse($key, $data, $code)
    {
        $serializer = $this->container->get('jms_serializer');
        $json = $serializer->serialize([$key => $data], 'json');
        $response = new Response($json, $code);
        return $response;
    }


}
