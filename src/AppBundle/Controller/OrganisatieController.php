<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;
use Symfony\Component\Validator\Constraints\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Security("has_role('ROLE_ORGANISATIE')")
 */
class OrganisatieController extends BaseController
{
    /**
     * @Route("/organisatie/{page}/", name="organisatieGetContent")
     * @Method("GET")
     */
    public function getOrganisatiePage($page)
    {
        $this->setBasicPageData(true);
        switch ($page) {
            case 'Home':
                return $this->getOrganisatieHomePage();
            case 'To-do lijst':
                return $this->getOrganisatieHomePage();
            case 'Instellingen':
                return $this->getOrganisatieHomePage();
            case 'Mails':
                return $this->getOrganisatieHomePage();
            case 'Inschrijvingen':
                return $this->getOrganisatieHomePage();
            case 'Juryzaken':
                return $this->getOrganisatieHomePage();
            case 'Financieel':
                return $this->getOrganisatieHomePage();
            case 'Mijn gegevens':
                return $this->getOrganisatieGegevensPage();
        }
    }

    private function getGegevens()
    {
        $userObject = $this->getUser();
        return $userObject->getAll();
    }

    private function getOrganisatieGegevensPage()
    {
        $gegevens = $this->getGegevens();
        return $this->render('organisatie/organisatieGegevens.html.twig', array(
            'menuItems' => $this->menuItems,
            'gegevens' => $gegevens,
        ));
    }

    private function getOrganisatieHomePage()
    {
        return $this->render('organisatie/organisatieIndex.html.twig', array(
            'menuItems' => $this->menuItems,
        ));
    }

    /**
     * @Route("/organisatie/edit/{fieldName}/{data}/", name="editGegevens", options={"expose"=true})
     * @Method("GET")
     */
    public function editGegevens($fieldName, $data)
    {
        /** @var User $userObject */
        $userObject = $this->getUser();
        $returnData = 'error';
        switch ($fieldName) {
            case 'username':
                $userObject->setUsername($data);
                $returnData = $userObject->getUsername();
                break;
            case 'voornaam':
                $userObject->setVoornaam($data);
                $returnData = $userObject->getVoornaam();
                break;
            case 'achternaam':
                $userObject->setAchternaam($data);
                $returnData = $userObject->getAchternaam();
                break;
            case 'email':
                $userObject->setEmail($data);
                $returnData = $userObject->getEmail();
                break;
            case 'verantwoordelijkheid':
                $userObject->setVerantwoordelijkheid($data);
                $returnData = $userObject->getVerantwoordelijkheid();
                break;
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($userObject);
        $em->flush();
        $response = new Response(json_encode(array($fieldName => $returnData)));
        return $response;
    }

}