<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\Book;
use AppBundle\Entity\BookImage;
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
use AppBundle\Form\Type\BookType;
use Symfony\Component\HttpFoundation\FileBag;
class BookManagementApiController extends Controller
{


    /**
     * @Route("/api/book/search_by_keyword_amazon", name="books_search_by_keyword_amazon")
     *
     * @Method({"POST"})
     *
     */
    public function searchByKeywordAmazonApi(Request $request)
    {

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (array_key_exists('keyword', $data)) {
            $keyword = $data['keyword'];
        } else {
            $keyword = null;
        }
        if (array_key_exists('page', $data)) {
            $page = $data['page'];
        } else {
            $page = null;
        }
        return $this->getBooksByKeywordAmazon($keyword, $page);

    }


    /**
     * @Route("/api/book/search_by_asin_amazon", name="books_search_by_asin_amazon")
     *
     * @Method({"POST"})
     *
     */
    public function searchByAsinAmazonApi(Request $request)
    {

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (array_key_exists('asin', $data)) {
            $asin = $data['asin'];
        } else {
            $asin = "";
        }

        return $this->getBooksByAsinAmazon($asin);

    }

    /**
     * @Route("/api/book/search_by_isbn_amazon", name="books_search_by_isbn_amazon")
     *
     * @Method({"POST"})
     *
     */
    public function searchByIsbnAmazonApi(Request $request)
    {

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (array_key_exists('isbn', $data)) {
            $isbn = $data['isbn'];
        } else {
            $isbn = "";
        }

        return $this->getBooksByIsbnAmazon($isbn);

    }


    /**
     * @Route("/api/book/search_by_isbn_campus_books", name="books_search_by_isbn_campus_books")
     *
     * @Method({"POST"})
     *
     */
    public function searchByIsbnCampusBooksApi(Request $request)
    {

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (array_key_exists('isbn', $data)) {
            $isbn = $data['isbn'];
        } else {
            $isbn = "";
        }

        return $this->getBooksByIsbnCampusBooks($isbn);

    }

    /**
     * @Route("/api/book/get_amazon_cart_create_url", name="get_amazon_cart_create_url")
     *
     * @Method({"POST"})
     *
     */
    public function getAmazonCartCreateUrl(Request $request)
    {

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (array_key_exists('bookOfferId', $data)) {
            $bookOfferId = $data['bookOfferId'];
        } else {
            $bookOfferId= "";
        }

        $addToCartAmazonUrl = $this->addToCartAmazonUrl($bookOfferId);
        $xmlOutput = $this->get('api_caller')->call(new HttpGetHtml($addToCartAmazonUrl, null, null));


        $fileContents = str_replace(array("\n", "\r", "\t"), '', $xmlOutput);

        $fileContents = trim(str_replace('"', "'", $fileContents));

        $simpleXml = simplexml_load_string($fileContents);



        return $this->createJsonResponse('cartUrl',(string)$simpleXml->Cart->PurchaseURL);

    }
    /**
     * @Route("/api/book/add_new_sell_book", name="add_new_sell_book")
     *
     * @Method({"POST"})
     *
     */
    public function addNewSellBookAction(Request $request)
    {




//        var_dump($imageOutput);
//        die();

        $serializer = $this->container->get('jms_serializer');
        $userId = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $fileDirHost = $this->container->getParameter('kernel.root_dir');
        $fileDir = '/../web/bookImages/';
//        $bookRepo = $em->getRepository("AppBundle:Book");

        $content = $request->get('book');
        $bookData = json_decode($content, true);
//        $bookData['bookImages'] = new ArrayCollection();


        $titleImageDone=false;

        $files = $request->files;
        $fileUploadError= false;
        $book = new Book();

        if(array_key_exists('bookLargeImageUrl',$bookData)){
            $imageOutput = $this->get('api_caller')->call(new HttpGetHtml(json_decode($request->get('book'),true)['bookLargeImageUrl'], null, null));

            $fileSaveName = gmdate("Y-d-m_h_i_s_").rand(0,99999999).".png";
            $fp = fopen($fileDirHost.$fileDir.$fileSaveName,'x');
            fwrite($fp, $imageOutput);
            fclose($fp);

            $bookImage = new BookImage();

            $bookImage->setImageName("Amazon Book Image");
            $bookImage->setImageUrl($fileDir.$fileSaveName);
            $bookImage->setTitleImage(true);
            $titleImageDone = true;

//            $bookData['bookImages'][]=$bookImage;
            $book->addBookImage($bookImage);



        }

        $i=0;
        foreach($files as $file){
            if((($file->getSize())/1204)<=200){

                $fileName = substr($file->getClientOriginalName(),0,strpos($file->getClientOriginalName(), pathinfo($file->getClientOriginalName())['extension']));
                $fileSaveName = gmdate("Y-d-m_h_i_s_").rand(0,99999999).".".pathinfo($file->getClientOriginalName())['extension'];


                $file->move($fileDirHost.$fileDir, $fileSaveName);
                $bookImage = new BookImage();

                $bookImage->setImageName($fileName);
                $bookImage->setImageUrl($fileDir.$fileSaveName);

                if(array_key_exists('bookTitleImage',$bookData) && !$titleImageDone){
                    if($bookData['bookTitleImage']==null){
                        $bookImage->setTitleImage(false);
                    }elseif($i==$bookData['bookTitleImage']){
                        $bookImage->setTitleImage(true);
                    }
                }else{
                    $bookImage->setTitleImage(false);
                }

//                $bookData['bookImages'][]=$bookImage;
                $book->addBookImage($bookImage);

            }else{
                $fileUploadError = true;
            }
            $i++;
        }





        if(!$fileUploadError){


            if(array_key_exists('bookPublishDate',$bookData)){
                $publishDate = new \DateTime($bookData['bookPublishDate']);
                $bookData['bookPublishDate'] =$publishDate;
            }
            if(array_key_exists('bookAvailableDate',$bookData)){
                $availableDate = new \DateTime($bookData['bookAvailableDate']);
                $bookData['bookAvailableDate'] =$availableDate;
            }

            $bookData['bookSeller']=$userId;



            $bookForm = $this->createForm(new BookType(), $book);


            var_dump($bookForm->getData()->getBookImages());
//            var_dump($bookData);
            $bookForm->submit($bookData);//todo
            var_dump($bookForm->getData()->getBookImages());
//            var_dump($bookData);

            if($bookForm->isValid()){
                $em->persist($book);
                $em->flush();
                return $this->createJsonResponse('success',array('successTitle'=>"Book Successfully added to sell List"));
            }else{
//                var_dump($bookForm->getErrors(true));
                $error= $serializer->serialize($bookForm,'json');
                return new Response($error,200);
            }
        }else{
            return $this->createJsonResponse('error',array('errorTitle'=>"Book was not Successfully Uploaded",'errorDescription'=>"Please select images less than or equal 200KB."));
        }



    }

//    public function addNewSellBookAction(Request $request)
//    {
//
//        $serializer = $this->container->get('jms_serializer');
//        $userId = $this->get('security.token_storage')->getToken()->getUser()->getId();
//        $em = $this->getDoctrine()->getManager();
//
//        $content = $request->get('book');
//        $bookData = json_decode($content, true);
//
//        $book = new Book();
//
//
//        $bookImage1 = new BookImage();
//        $bookImage1->setImageName("Amazon Book Image");
//        $bookImage1->setImageUrl("http://url1.com");
//
//        $book->addBookImage($bookImage1);
//
//        $bookImage2 = new BookImage();
//        $bookImage2->setImageName("Amazon Book Image");
//        $bookImage2->setImageUrl("http://url1.com");
//
//        $book->addBookImage($bookImage2);
//
//
//
//        $bookData['bookSeller']=$userId;
//
//
//
//        $bookForm = $this->createForm(new BookType(), $book);
//
//
//        var_dump($bookForm->getData()->getBookImages());
//        $bookForm->submit($bookData);
//        var_dump($bookForm->getData()->getBookImages());
//
//        if($bookForm->isValid()){
//            $em->persist($book);
//            $em->flush();
//            return $this->createJsonResponse('success',array('successTitle'=>"Book Successfully added to sell List"));
//        }else{
//            $error= $serializer->serialize($bookForm,'json');
//            return new Response($error,200);
//        }
//
//
//
//    }

    function getBooksByKeywordAmazon($keyword, $page)
    {


        $amazonCredentials = $this->getAmazonSearchParams();

        $amazonCredentials['params']['Operation'] = "ItemSearch";
        $amazonCredentials['params']["ItemPage"] = $page;
        $amazonCredentials['params']["Keywords"] = $keyword;
        $amazonCredentials['params']["SearchIndex"] = "Books";
        $amazonCredentials['params']["ResponseGroup"] = "Medium,Offers";
        $getUrl = $this->getUrlWithSignature($amazonCredentials);


        $xmlOutput = $this->get('api_caller')->call(new HttpGetHtml($getUrl, null, null));

        $booksArray = $this->parseMultipleBooksAmazonXmlResponse($xmlOutput);


        return $this->createJsonResponse('result', $booksArray);

    }

    function getBooksByAsinAmazon($asin)
    {


        $amazonCredentials = $this->getAmazonSearchParams();

        $amazonCredentials['params']['Operation'] = "ItemLookup";
        $amazonCredentials['params']["ItemId"] = $asin;
        $amazonCredentials['params']["ResponseGroup"] = "Medium,Offers";
        $getUrl = $this->getUrlWithSignature($amazonCredentials);
        $xmlOutput = $this->get('api_caller')->call(new HttpGetHtml($getUrl, null, null));

        $booksArray = $this->parseMultipleBooksAmazonXmlResponse($xmlOutput);

        return $this->createJsonResponse('result', $booksArray);

    }

    function getBooksByIsbnAmazon($isbn)
    {


        $amazonCredentials = $this->getAmazonSearchParams();

        $amazonCredentials['params']['Operation'] = "ItemLookup";
        $amazonCredentials['params']["ItemId"] = $isbn;
        $amazonCredentials['params']["ResponseGroup"] = "Medium,Offers";
        $amazonCredentials['params']["IdType"]="ISBN";
        $amazonCredentials['params']["SearchIndex"]="All";

        $getUrl = $this->getUrlWithSignature($amazonCredentials);
//        var_dump($getUrl);
//        die();

        $xmlOutput = $this->get('api_caller')->call(new HttpGetHtml($getUrl, null, null));

        $booksArray = $this->parseMultipleBooksAmazonXmlResponse($xmlOutput);

        return $this->createJsonResponse('result', $booksArray);

    }

    public function addToCartAmazonUrl($bookOfferId){
        $amazonSearchParams = $this->getAmazonSearchParams();
        $amazonSearchParams['params']['Operation'] = "CartCreate";
        $amazonSearchParams['params']['Item.1.OfferListingId'] = $bookOfferId;
        $amazonSearchParams['params']['Item.1.Quantity'] = "1";

        $cartUrl = $this->getUrlWithSignature($amazonSearchParams);
        return $cartUrl;
    }

    public function getBooksByIsbnCampusBooks($isbn)
    {
        $campusBooksApiInfo = $this->getParameter('campus_books_api_info');
        $apiKey = $campusBooksApiInfo['api_key'];
        $host = $campusBooksApiInfo['host'];
        $uri = $campusBooksApiInfo['uri'];

        $url= $host.$uri."?key=".$apiKey."&isbn=".$isbn."&format=json";

        $jsonOutput = $this->get('api_caller')->call(new HttpGetHtml($url, null, null));
        $response = new Response($jsonOutput, 200);
        return $response;


    }

    public function getUrlWithSignature($amazonCredentials)
    {
        // sort the parameters
        ksort($amazonCredentials['params']);
        // create the canonicalization  query
        $canonicalizedQuery = array();
        foreach ($amazonCredentials['params'] as $param => $value) {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));
            $canonicalizedQuery[] = $param . "=" . $value;
        }
        $canonicalizedQuery = implode("&", $canonicalizedQuery);

        // create the string to sign
        $string_to_sign = $amazonCredentials['apiInfo']['method'] . "\n" . $amazonCredentials['apiInfo']['host'] . "\n" . $amazonCredentials['apiInfo']['uri'] . "\n" . $canonicalizedQuery;

        // calculate HMAC with SHA256 and base64-encoding
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $amazonCredentials['apiInfo']['privateKey'], true));

        // encode the signature for the request
        $signature = str_replace("%7E", "~", rawurlencode($signature));
        $url = "http://" . $amazonCredentials['apiInfo']['host'] . $amazonCredentials['apiInfo']['uri'] . "?" . $canonicalizedQuery . "&Signature=" . $signature;

        return $url;
    }

    public function getAmazonSearchParams()
    {


        $amazonApiInfo = $this->getParameter('amazon_api_info');

        $apiInfo = array();
        $apiInfo['method'] = $amazonApiInfo['method'];
        $apiInfo['host'] = $amazonApiInfo['host'];
        $apiInfo['uri'] = $amazonApiInfo['uri'];
        $apiInfo['privateKey'] = $amazonApiInfo['private_key'];

//        $time = time();
//        $date = new  \DateTime();
//        $date->setTimestamp($time);

        $params = array();

        $params["AWSAccessKeyId"] = $amazonApiInfo['aws_access_key_id'];
        $params["AssociateTag"] = $amazonApiInfo['associate_tag'];
        $params["Service"] = "AWSECommerceService";
        $params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
        $params["Version"] = $amazonApiInfo['version'];
        $params["Power"] = "binding:hardcover or library or paperback";



        return array(
            'apiInfo' => $apiInfo,
            'params' => $params
        );

    }

    public function parseMultipleBooksAmazonXmlResponse($xml)
    {

        $fileContents = str_replace(array("\n", "\r", "\t"), '', $xml);

        $fileContents = trim(str_replace('"', "'", $fileContents));

        $simpleXml = simplexml_load_string($fileContents);

        $booksArray = array();
        foreach ($simpleXml->Items->Item as $item) {
            $booksArray[] = $this->createJsonFromItemAmazon($item);
        }


        return array(
            'books' => $booksArray,
            'totalSearchResults' => (string)$simpleXml->Items->TotalResults
        );

    }


    public function createJsonFromItemAmazon($item)
    {

        if (!empty($item->Offers->Offer->OfferListing->Price->FormattedPrice)) {
            $price = (string)$item->Offers->Offer->OfferListing->Price->FormattedPrice;
        } elseif (!empty($item->ListPrice->FormattedPrice)) {
            $price = (string)$item->ListPrice->FormattedPrice;
        } else {
            $price = "Not Found";
        }

        if (isset($item->ItemAttributes->Director)) {
            $book_director_author_artist = (string)$item->ItemAttributes->Director;
        } elseif (isset($item->ItemAttributes->Author)) {
            $book_director_author_artist = (string)$item->ItemAttributes->Author;
        } elseif (isset($item->ItemAttributes->Artist)) {
            $book_director_author_artist = (string)$item->ItemAttributes->Artist;
        } else {
            $book_director_author_artist = 'No Author Found';
        }

        if(!empty($item->Offers->Offer->OfferListing->OfferListingId)){
            $offerId = (string)$item->Offers->Offer->OfferListing->OfferListingId;
        }else{
            $offerId = "";
        }


        if (!empty($item->MediumImage->URL)) {
            $book_image_medium_url = (string)$item->MediumImage->URL;
        } else {
            $book_image_medium_url = './images/misc/no_picture_100x125.jpg';
        }

        if (!empty($item->LargeImage->URL)) {
            $book_image_large_url = (string)$item->LargeImage->URL;
        } else {
            $book_image_large_url  = './images/misc/no_picture_100x125.jpg';
        }

        return array(
            'bookAsin' => (string)$item->ASIN,
            'bookTitle' => (string)$item->ItemAttributes->Title,
            'bookDirectorAuthorArtist' => $book_director_author_artist,
            'bookPriceAmazon' => $price,
            'bookIsbn' => (string)$item->ItemAttributes->ISBN,
            'bookEan' => (string)$item->ItemAttributes->EAN,
            'bookEdition' => (string)$item->ItemAttributes->Edition,
            'bookPublisher' => (string)$item->ItemAttributes->Publisher,
            'bookPublisherDate' => (string)$item->ItemAttributes->PublicationDate,
            'bookBinding' => (string)$item->ItemAttributes->Binding,
            'bookMediumImageUrl' => $book_image_medium_url,
            'bookLargeImageUrl' => $book_image_large_url,
            'bookDescription' => (string)$item->EditorialReviews->EditorialReview->Content,
            'bookPages' => (string)$item->ItemAttributes->NumberOfPages,
            'bookOfferId'=>$offerId,
            'bookLanguage'=> (string)$item->ItemAttributes->Languages->Language->Name,
        );
    }


    public function createJsonResponse($key, $data)
    {
        $serializer = $this->container->get('jms_serializer');
        $json = $serializer->serialize([$key => $data], 'json');
        $response = new Response($json, 200);
        return $response;
    }

}
